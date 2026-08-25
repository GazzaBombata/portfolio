<?php

use App\Filament\Widgets\PeriodMovementsTable;
use App\Filament\Widgets\SpendingShareChart;
use App\Finance\Reporting;
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

    $this->lavoro = Category::create(['name' => 'Lavoro', 'kind' => 'expense']);
    $this->tasse = Category::create(['name' => 'Tasse', 'parent_id' => $this->lavoro->id, 'kind' => 'expense']);
    $this->casa = Category::create(['name' => 'Casa', 'kind' => 'expense']);

    Transaction::factory()->create(['account_id' => $this->conto->id, 'category_id' => $this->tasse->id, 'amount' => -28000]);
    Transaction::factory()->create(['account_id' => $this->conto->id, 'category_id' => $this->casa->id, 'amount' => -1000]);
});

it('toglie dai totali la categoria esclusa', function () {
    $tutto = (float) Reporting::expenses(['periodo' => 'tutto'])->sum('amount');
    $senzaTasse = (float) Reporting::expenses([
        'periodo' => 'tutto', 'exclude_categories' => [$this->tasse->id],
    ])->sum('amount');

    expect($tutto)->toBe(-29000.0)
        ->and($senzaTasse)->toBe(-1000.0);
});

/*
 * Chi esclude «Lavoro» non intende tenere dentro «Lavoro · Tasse»: un filtro
 * che lascia passare i figli produce un totale che nessuno ha chiesto e che
 * sembra comunque plausibile.
 */
it('escludendo una categoria principale esclude anche le sue sottocategorie', function () {
    $senzaLavoro = (float) Reporting::expenses([
        'periodo' => 'tutto', 'exclude_categories' => [$this->lavoro->id],
    ])->sum('amount');

    expect($senzaLavoro)->toBe(-1000.0);
});

it('la torta si ricalcola su quello che resta', function () {
    $w = Livewire::test(SpendingShareChart::class, ['pageFilters' => [
        'periodo' => 'tutto', 'exclude_categories' => [$this->tasse->id],
    ]])->instance();

    $m = (new ReflectionClass($w))->getMethod('getData');
    $m->setAccessible(true);

    expect($m->invoke($w)['labels'])->toBe(['Casa']);
});

/*
 * Il motivo per cui l'etichetta esiste: un totale filtrato che sembra completo
 * è il modo più facile di leggere male i propri numeri.
 */
it('scrive sui riquadri che cosa è stato escluso', function () {
    $etichetta = Reporting::excludedLabel(['exclude_categories' => [$this->tasse->id]]);

    expect($etichetta)->toContain('Escluse')
        ->and($etichetta)->toContain('Tasse');
});

it('non scrive niente quando non si esclude niente', function () {
    expect(Reporting::excludedLabel(['periodo' => 'tutto']))->toBe('')
        ->and(Reporting::excludedLabel(null))->toBe('');
});

it('la tabella dei movimenti rispetta l\'esclusione', function () {
    $tassa = Transaction::where('category_id', $this->tasse->id)->sole();

    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => [
        'periodo' => 'tutto', 'exclude_categories' => [$this->tasse->id],
    ]])->assertCanNotSeeTableRecords([$tassa]);
});
