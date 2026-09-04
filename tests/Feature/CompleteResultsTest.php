<?php

use App\Assistant\Tools\EnergyBalanceTool;
use App\Assistant\Tools\HealthSummaryTool;
use App\Assistant\Tools\SearchRecordsTool;
use App\Assistant\Tools\SearchTransactionsTool;
use App\Assistant\Tools\SpendingSummaryTool;
use App\Health\Energy;
use App\Models\Account;
use App\Models\BodyMetric;
use App\Models\Category;
use App\Models\Meal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->oggi = CarbonImmutable::parse('2026-09-04');
});

/*
 * Un piano tagliato è peggio di un piano assente.
 *
 * Il modello non distingue una stringa troncata da una completa: risponde con
 * la stessa sicurezza su metà del testo. E siccome può anche SCRIVERE — con
 * modifica_pasto passa una descrizione — un taglio fatto per stare in una
 * riga torna indietro come valore vero, e la parte mancante è persa per
 * sempre. Un limite di visualizzazione che arriva a un modello che scrive non
 * è cosmetico: è una strada verso la perdita di dati.
 */
$PIANO = 'petto di pollo 150 g, riso basmati 80 g, zucchine grigliate 200 g, olio evo 10 g, mandorle 15 g';

it('non taglia la descrizione di un pasto previsto', function () use ($PIANO) {
    Meal::create(['kind' => 'planned', 'eaten_on' => $this->oggi, 'moment' => 'lunch', 'description' => $PIANO, 'calories' => 700]);

    $esito = (new SearchRecordsTool)->run(['dal' => $this->oggi->toDateString()]);

    expect(strlen($PIANO))->toBeGreaterThan(50)
        ->and($esito->content)->toContain($PIANO)
        ->not->toContain('...');
});

it('non taglia la descrizione nel riepilogo', function () use ($PIANO) {
    Meal::create(['kind' => 'eaten', 'eaten_on' => $this->oggi, 'moment' => 'lunch', 'description' => $PIANO, 'calories' => 700]);

    $esito = (new HealthSummaryTool)->run(['dal' => $this->oggi->toDateString(), 'al' => $this->oggi->toDateString()]);

    expect($esito->content)->toContain($PIANO);
});

/* Stessa regola nell'altra chat: la descrizione di un movimento è un dato. */
it('non taglia la descrizione di un movimento', function () {
    $lunga = 'PAGAMENTO POS 12/34 CARTA 5678 ESERCENTE FARMACIA COMUNALE 7 VIA ROMA BRESCIA IT';
    $conto = Account::create(['name' => 'Conto', 'kind' => 'bank']);
    Transaction::factory()->create([
        'account_id' => $conto->id, 'booked_on' => $this->oggi, 'amount' => -21.50,
        'description' => $lunga,
    ]);

    $esito = (new SearchTransactionsTool)->run(['testo' => 'FARMACIA']);

    expect(strlen($lunga))->toBeGreaterThan(45)
        ->and($esito->content)->toContain($lunga);
});

/*
 * Il gemello mancante di plannedWithoutCalories.
 *
 * `activityBurn()` salta chi non ha né calorie né minuti — giustamente, perché
 * senza durata non c'è niente da calcolare. Ma la seduta resta nell'elenco
 * degli allenamenti del giorno, quindi sembra contata mentre vale zero, e il
 * fabbisogno esce più basso del vero senza che niente lo dica.
 */
it('dice quali allenamenti non hanno potuto contare niente', function () {
    $this->user->forceFill(['birth_date' => '1990-01-01', 'height_cm' => 180, 'sex' => 'male', 'activity_factor' => 1.4])->save();
    BodyMetric::create(['measured_on' => $this->oggi->subDay(), 'weight_kg' => 80.0]);

    Workout::create(['performed_on' => $this->oggi, 'activity' => 'corsa', 'minutes' => 40]);
    Workout::create(['performed_on' => $this->oggi, 'activity' => 'palestra']);

    expect(Energy::workoutsWithoutDuration($this->oggi))->toBe(['palestra']);

    $esito = (new EnergyBalanceTool)->run(['giorno' => $this->oggi->toDateString()]);

    expect($esito->content)->toContain('«palestra»')
        ->toContain('non è stata contata')
        ->toContain('Chiedi quanto è durata');
});

it('non avverte quando tutte le sedute hanno una durata', function () {
    Workout::create(['performed_on' => $this->oggi, 'activity' => 'corsa', 'minutes' => 40]);
    Workout::create(['performed_on' => $this->oggi, 'activity' => 'palestra', 'calories' => 300]);

    expect(Energy::workoutsWithoutDuration($this->oggi))->toBe([]);
});

/*
 * La ripartizione per categoria si fermava alle prime quindici senza dirlo: le
 * voci mostrate non sommavano al totale delle uscite, e nessuno poteva
 * accorgersene. La coda ora si accorpa, così i conti tornano.
 */
it('accorpa le categorie oltre la quindicesima invece di perderle', function () {
    $conto = Account::create(['name' => 'Conto', 'kind' => 'bank']);

    foreach (range(1, 18) as $i) {
        $cat = Category::create(['name' => "Categoria {$i}", 'kind' => 'expense']);
        Transaction::factory()->create([
            'account_id' => $conto->id, 'category_id' => $cat->id,
            'booked_on' => $this->oggi, 'amount' => -($i * 10),
        ]);
    }

    $esito = (new SpendingSummaryTool)->run([
        'dal' => $this->oggi->subDay()->toDateString(),
        'al' => $this->oggi->addDay()->toDateString(),
    ]);

    // 18 categorie: 15 per esteso, le 3 più piccole accorpate (10+20+30 = 60 €).
    expect($esito->content)->toContain('Altre 3 categorie')
        ->toContain('3 movimenti')
        ->and($esito->content)->toContain('60,00');
});
