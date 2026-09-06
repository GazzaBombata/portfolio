<?php

use App\Assistant\Tools\LogMealTool;
use App\Assistant\Tools\LogWorkoutTool;
use App\Assistant\Tools\UpdateMealTool;
use App\Health\Diary;
use App\Models\Meal;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'app_authentication_secret' => 'PROVA',
        'birth_date' => '1994-11-23', 'height_cm' => 191, 'sex' => 'male', 'activity_factor' => 1.20,
    ]);
    $this->actingAs($this->user);
});

/*
 * Il pranzo del 25/08/2026: 640 kcal stimate in un numero solo, con dentro tre
 * cucchiai d'olio che da soli fanno i 36 g di grassi dichiarati per tutto il
 * piatto. Smontato, lo stesso pasto ne fa circa 700 — e l'errore si legge
 * invece di doverlo sospettare.
 */
it('somma il totale del pasto dagli ingredienti, invece di crederlo', function () {
    $risultato = (new LogMealTool)->run([
        'giorno' => '2026-03-01',
        'momento' => 'lunch',
        'descrizione' => 'Secondo magro, verdure, pane e olio',
        // Il numero che il modello avrebbe dato guardando la frase: viene
        // soprascritto dalla somma delle righe.
        'calorie' => 640,
        'ingredienti' => [
            ['nome' => 'Secondo magro', 'quantita' => '200 g', 'calorie' => 260, 'proteine_g' => 50, 'grassi_g' => 8],
            ['nome' => 'Verdure cotte', 'quantita' => '200 g', 'calorie' => 60, 'carboidrati_g' => 10],
            ['nome' => 'Pane integrale', 'quantita' => '40 g', 'calorie' => 105, 'carboidrati_g' => 20],
            ['nome' => 'Olio extravergine', 'quantita' => '3 cucchiai', 'calorie' => 270, 'grassi_g' => 30],
        ],
    ]);

    $meal = Meal::sole();

    expect($risultato->isError)->toBeFalse()
        ->and($meal->calories)->toBe(695)
        ->and($meal->fat_g)->toBe(38)
        ->and($meal->protein_g)->toBe(50)
        ->and($meal->carbs_g)->toBe(30)
        ->and($meal->items)->toHaveCount(4)
        ->and($meal->items->first()->summary())->toBe('Secondo magro · 200 g · 260 kcal');
});

it('tiene l\'ordine in cui gli ingredienti sono stati elencati', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-03-02', 'momento' => 'dinner', 'descrizione' => 'Cena',
        'ingredienti' => [['nome' => 'Primo'], ['nome' => 'Secondo'], ['nome' => 'Terzo']],
    ]);

    expect(Meal::sole()->items->pluck('name')->all())->toBe(['Primo', 'Secondo', 'Terzo']);
});

/*
 * Zero calorie e calorie sconosciute sono due cose diverse, e solo una delle
 * due abbassa il totale della giornata.
 */
it('lascia il totale a null se nessun ingrediente ha le calorie', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-03-03', 'momento' => 'snack', 'descrizione' => 'Qualcosa al bar',
        'ingredienti' => [['nome' => 'Brioche'], ['nome' => 'Cappuccino']],
    ]);

    expect(Meal::sole()->calories)->toBeNull();
});

it('segnala nel diario gli ingredienti senza calorie', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-03-04', 'momento' => 'lunch', 'descrizione' => 'Pranzo',
        'ingredienti' => [
            ['nome' => 'Pasta', 'quantita' => '80 g', 'calorie' => 280],
            ['nome' => 'Condimento', 'quantita' => 'q.b.'],
        ],
    ]);

    $riga = Diary::between($this->user, CarbonImmutable::parse('2026-03-04'), CarbonImmutable::parse('2026-03-04'))[0];

    expect($riga['calorie']['mangiate'])->toBe(280)
        ->and(implode(' | ', $riga['avvisi']))->toContain('1 ingrediente senza calorie');
});

/*
 * Correggere sostituisce le righe: aggiungerle lascerebbe in tabella anche la
 * versione sbagliata, e il totale le sommerebbe tutte e due.
 */
it('sostituisce gli ingredienti quando si corregge un pasto', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-03-05', 'momento' => 'lunch', 'descrizione' => 'Pranzo',
        'ingredienti' => [['nome' => 'Riso', 'calorie' => 300]],
    ]);

    (new UpdateMealTool)->run([
        'id' => Meal::sole()->id,
        'ingredienti' => [
            ['nome' => 'Riso', 'calorie' => 300],
            ['nome' => 'Olio', 'quantita' => '2 cucchiaini', 'calorie' => 90],
        ],
    ]);

    $meal = Meal::sole();

    expect($meal->items)->toHaveCount(2)->and($meal->calories)->toBe(390);
});

it('lascia in pace un pasto scritto di corsa, senza ingredienti', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-03-06', 'momento' => 'snack', 'descrizione' => 'Una mela', 'calorie' => 80,
    ]);

    expect(Meal::sole()->calories)->toBe(80)->and(Meal::sole()->items)->toBeEmpty();
});

/*
 * I passi finiti in una seduta non li legge nessuno: `Energy::stepsBurn()`
 * guarda `daily_logs.steps`. È già successo il 25/08/2026, con un allenamento
 * chiamato «Passi giornalieri (non un allenamento)» da zero calorie.
 */
it('rifiuta di registrare i passi come allenamento', function () {
    $risultato = (new LogWorkoutTool)->run([
        'giorno' => '2026-03-07',
        'attivita' => 'Passi giornalieri',
        'tipo' => 'fatta',
        'proposta_da' => 'giorgio',
    ]);

    expect($risultato->isError)->toBeTrue()
        ->and($risultato->content)->toContain('registra_giornata')
        ->and(Workout::count())->toBe(0);
});

it('lascia passare una camminata vera', function () {
    $risultato = (new LogWorkoutTool)->run([
        'giorno' => '2026-03-08', 'attivita' => 'Camminata in montagna',
        'tipo' => 'fatta', 'proposta_da' => 'giorgio', 'minuti' => 90,
    ]);

    expect($risultato->isError)->toBeFalse()->and(Workout::count())->toBe(1);
});
