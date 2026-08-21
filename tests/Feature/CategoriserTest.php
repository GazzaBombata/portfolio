<?php

use App\Finance\Categoriser;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    Auth::setUser(User::factory()->create());
    $this->account = Account::factory()->create();
    $this->bar = Category::create(['name' => 'Bar', 'kind' => 'expense']);
    $this->spesa = Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
});

function spesa(Account $account, string $description, ?int $categoryId = null, bool $locked = false): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $account->id,
        'description' => $description,
        'raw_description' => $description,
        'category_id' => $categoryId,
        'category_locked' => $locked,
    ]);
}

it('classifica con la regola che corrisponde', function () {
    CategoryRule::create(['category_id' => $this->bar->id, 'pattern' => 'CAFFE IL PARADISO', 'match_type' => 'contains']);
    $movimento = spesa($this->account, 'CAFFE IL PARADISO PEZZAZE');

    app(Categoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBe($this->bar->id);
});

/*
 * È la ragione per cui vale la pena correggere: una correzione fatta a mano
 * resta. Se la passata automatica la sovrascrivesse, classificare a mano
 * sarebbe fatica buttata al primo import successivo.
 */
it('non sovrascrive una categoria scelta da una persona', function () {
    CategoryRule::create(['category_id' => $this->bar->id, 'pattern' => 'MERCATO', 'match_type' => 'contains']);
    $movimento = spesa($this->account, 'MERCATO RIONALE', $this->spesa->id, locked: true);

    app(Categoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBe($this->spesa->id);
});

it('applica prima la regola più specifica', function () {
    CategoryRule::create(['category_id' => $this->spesa->id, 'pattern' => 'MERCATO', 'match_type' => 'contains', 'priority' => 100]);
    CategoryRule::create(['category_id' => $this->bar->id, 'pattern' => 'MERCATO DEL CAFFE', 'match_type' => 'contains', 'priority' => 10]);
    $movimento = spesa($this->account, 'MERCATO DEL CAFFE BRESCIA');

    app(Categoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBe($this->bar->id);
});

it('cerca anche nella descrizione grezza della banca', function () {
    CategoryRule::create(['category_id' => $this->bar->id, 'pattern' => 'Operazione presso BAR', 'match_type' => 'contains']);
    $movimento = Transaction::factory()->create([
        'account_id' => $this->account->id,
        // La pulizia toglie "Operazione presso": una regola scritta sul testo
        // originale deve continuare a funzionare.
        'raw_description' => 'Operazione presso BAR SOLE CASTENEDOLO',
        'description' => 'BAR SOLE CASTENEDOLO',
    ]);

    app(Categoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBe($this->bar->id);
});

/*
 * Gli estratti conto delle carte attaccano al nome dell'esercente una città, un
 * numero di punto vendita e un riferimento che cambiano ogni volta. Una regola
 * che se li porta dentro corrisponde una volta sola: al movimento da cui nasce.
 */
it('impara una regola sul nome dell\'esercente, senza i numeri dello scontrino', function () {
    $movimento = spesa($this->account, 'ESSELUNGA 4471 BRESCIA 00293841', $this->spesa->id);

    $regola = app(Categoriser::class)->learnFrom($movimento);

    expect($regola)->not->toBeNull()
        ->and($regola->pattern)->not->toContain('4471')
        ->and($regola->pattern)->not->toContain('00293841')
        ->and($regola->auto_learned)->toBeTrue();

    // E la regola imparata deve pescare la stessa spesa il mese dopo.
    $prossimoMese = spesa($this->account, 'ESSELUNGA 4471 BRESCIA 00998877');
    app(Categoriser::class)->run();

    expect($prossimoMese->fresh()->category_id)->toBe($this->spesa->id);
});

it('non impara pattern troppo corti per distinguere qualcosa', function () {
    $movimento = spesa($this->account, 'POS 12345678', $this->spesa->id);

    expect(app(Categoriser::class)->learnFrom($movimento))->toBeNull();
});

/*
 * Un accredito non è "Software e servizi" col meno davanti. Era successo su
 * otto entrate vere: finivano dentro voci di spesa, e quelle voci mostravano
 * un totale più basso di quanto fosse stato speso davvero.
 */
it('non mette un\'entrata in una categoria di spesa', function () {
    CategoryRule::create(['category_id' => $this->spesa->id, 'pattern' => 'RIMBORSO', 'match_type' => 'contains']);
    $entrata = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'amount' => 120.00,
        'description' => 'RIMBORSO SPESE',
        'raw_description' => 'RIMBORSO SPESE',
    ]);

    app(Categoriser::class)->run();

    expect($entrata->fresh()->category_id)->toBeNull();
});

it('non mette un\'uscita in una categoria di entrata', function () {
    $stipendio = Category::create(['name' => 'Stipendio', 'kind' => 'income']);
    CategoryRule::create(['category_id' => $stipendio->id, 'pattern' => 'ACCREDITO', 'match_type' => 'contains']);
    $uscita = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'amount' => -50.00,
        'description' => 'ACCREDITO STORNATO',
        'raw_description' => 'ACCREDITO STORNATO',
    ]);

    app(Categoriser::class)->run();

    expect($uscita->fresh()->category_id)->toBeNull();
});

it('classifica l\'entrata con una categoria di entrata', function () {
    $stipendio = Category::create(['name' => 'Fatture e compensi', 'kind' => 'income']);
    CategoryRule::create(['category_id' => $stipendio->id, 'pattern' => 'ACCREDITO BEU', 'match_type' => 'contains']);
    $entrata = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'amount' => 4500.00,
        'description' => 'ACCREDITO BEU CON CONTABILE',
        'raw_description' => 'ACCREDITO BEU CON CONTABILE',
    ]);

    app(Categoriser::class)->run();

    expect($entrata->fresh()->category_id)->toBe($stipendio->id);
});
