<?php

use App\Filament\Widgets\PeriodMovementsTable;
use App\Filament\Widgets\SpendingByCategoryChart;
use App\Filament\Widgets\SpendingShareChart;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
    $this->conto = Account::factory()->create();

    $this->casa = Category::create(['name' => 'Casa', 'kind' => 'expense']);
    $this->auto = Category::create(['name' => 'Auto', 'kind' => 'expense']);

    $this->spesaCasa = Transaction::factory()->create([
        'account_id' => $this->conto->id, 'category_id' => $this->casa->id, 'amount' => -800,
    ]);
    $this->spesaAuto = Transaction::factory()->create([
        'account_id' => $this->conto->id, 'category_id' => $this->auto->id, 'amount' => -200,
    ]);
});

/*
 * Il browser sa solo di aver cliccato il secondo elemento: la corrispondenza
 * fra quel numero e una categoria esiste soltanto se le due liste sono
 * costruite con la stessa query e nello stesso ordine.
 */
it('gli indici delle fette corrispondono alle etichette', function () {
    $w = Livewire::test(SpendingShareChart::class, ['pageFilters' => ['periodo' => 'tutto']])->instance();
    $m = (new ReflectionClass($w))->getMethod('getData');
    $m->setAccessible(true);

    $etichette = $m->invoke($w)['labels'];
    $target = $w->drillTargets();

    expect($etichette[0])->toBe('Casa')
        ->and($target[0])->toBe([$this->casa->id])
        ->and($etichette[1])->toBe('Auto')
        ->and($target[1])->toBe([$this->auto->id]);
});

it('vale anche per le barre', function () {
    $w = Livewire::test(SpendingByCategoryChart::class, ['pageFilters' => ['periodo' => 'tutto']])->instance();

    expect($w->drillTargets()[0])->toBe([$this->casa->id]);
});

it('la fetta del resto porta dentro tutte le categorie che raccoglie', function () {
    foreach (['A' => 700, 'B' => 600, 'C' => 500, 'D' => 400] as $nome => $importo) {
        $c = Category::create(['name' => $nome, 'kind' => 'expense']);
        Transaction::factory()->create(['account_id' => $this->conto->id, 'category_id' => $c->id, 'amount' => -$importo]);
    }

    $w = Livewire::test(SpendingShareChart::class, ['pageFilters' => ['periodo' => 'tutto']])->instance();
    $target = $w->drillTargets();

    // Quinto elemento = la fetta «Altro», che ne contiene più di una.
    expect($target[4])->toHaveCount(2)
        ->and($target[4])->toContain($this->auto->id);
});

it('cliccare una fetta filtra la tabella su quella categoria', function () {
    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => ['periodo' => 'tutto']])
        ->call('drillIntoCategories', [$this->casa->id])
        ->assertCanSeeTableRecords([$this->spesaCasa])
        ->assertCanNotSeeTableRecords([$this->spesaAuto]);
});

it('si torna a vedere tutto', function () {
    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => ['periodo' => 'tutto']])
        ->call('drillIntoCategories', [$this->casa->id])
        ->assertCanNotSeeTableRecords([$this->spesaAuto])
        ->call('clearDrill')
        ->assertCanSeeTableRecords([$this->spesaCasa, $this->spesaAuto]);
});

it('il filtro del clic si somma a quelli della pagina, non li sostituisce', function () {
    $altroConto = Account::factory()->create();
    $altrove = Transaction::factory()->create([
        'account_id' => $altroConto->id, 'category_id' => $this->casa->id, 'amount' => -900,
    ]);

    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => [
        'periodo' => 'tutto', 'accounts' => [$this->conto->id],
    ]])
        ->call('drillIntoCategories', [$this->casa->id])
        ->assertCanSeeTableRecords([$this->spesaCasa])
        // Stessa categoria, ma su un conto che il filtro della pagina esclude.
        ->assertCanNotSeeTableRecords([$altrove]);
});

it('un clic a vuoto non cambia niente', function () {
    $w = Livewire::test(SpendingShareChart::class, ['pageFilters' => ['periodo' => 'tutto']]);

    // Un indice che non esiste: nessun evento, nessun filtro.
    $w->call('drillInto', 99)->assertNotDispatched('drill-into-categories');
});

/*
 * La parte JavaScript non la coprono i test: si verifica almeno che arrivi
 * nella pagina, perché il modo in cui questa funzione si rompe è che il
 * gestore non venga proprio renderizzato.
 */
it('il grafico rende il gestore del clic', function () {
    Livewire::test(SpendingShareChart::class, ['pageFilters' => ['periodo' => 'tutto']])
        ->assertSee('getElementsAtEventForMode', escape: false)
        ->assertSee('drillInto', escape: false);
});

it('la tabella rende l\'aggancio per lo scorrimento', function () {
    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => ['periodo' => 'tutto']])
        ->assertSee('scroll-to-movements', escape: false);
});
