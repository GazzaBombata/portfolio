<?php

use App\Filament\Widgets\FinanceOverview;
use App\Filament\Widgets\PeriodMovementsTable;
use App\Filament\Widgets\SpendingByCategoryChart;
use App\Finance\Period;
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
    $this->conto = Account::factory()->create(['name' => 'Conto A']);
    $this->altro = Account::factory()->create(['name' => 'Conto B']);
    $this->spesa = Category::create(['name' => 'Spesa', 'kind' => 'expense']);
});

function movimento2(Account $a, string $data, float $importo, ?int $cat = null): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $a->id, 'booked_on' => $data, 'amount' => $importo, 'category_id' => $cat,
    ]);
}

it('traduce le scelte rapide in un intervallo di date', function () {
    expect(Period::fromFilters(['periodo' => 'mese'])->from->format('Y-m-d'))
        ->toBe(now()->startOfMonth()->format('Y-m-d'))
        ->and(Period::fromFilters(['periodo' => 'tutto'])->from)->toBeNull()
        ->and(Period::fromFilters(['periodo' => 'anno'])->from->format('m-d'))->toBe('01-01');
});

/*
 * Il confronto deve essere fra intervalli della stessa lunghezza: un mese con
 * un mese, un trimestre con un trimestre. Confrontare un trimestre col mese
 * precedente darebbe un "+200%" che non significa niente.
 */
it('confronta con un intervallo precedente della stessa durata', function () {
    $periodo = Period::fromFilters(['periodo' => 'mese']);
    $prima = $periodo->previous();

    expect($prima->to->lt($periodo->from))->toBeTrue()
        ->and((int) $prima->from->startOfDay()->diffInDays($prima->to->startOfDay()))
        ->toBe((int) $periodo->from->startOfDay()->diffInDays($periodo->to->startOfDay()));
});

it('restringe i totali al periodo scelto', function () {
    movimento2($this->conto, now()->format('Y-m-d'), -100.00, $this->spesa->id);
    movimento2($this->conto, now()->copy()->subMonths(6)->format('Y-m-d'), -900.00, $this->spesa->id);

    $stats = Livewire::test(FinanceOverview::class, ['pageFilters' => ['periodo' => 'mese']])->instance();

    $m = (new ReflectionClass($stats))->getMethod('getStats');
    $m->setAccessible(true);
    $valori = $m->invoke($stats);

    expect((string) $valori[1]->getValue())->toContain('100,00');
});

it('restringe i totali ai conti scelti', function () {
    movimento2($this->conto, now()->format('Y-m-d'), -100.00, $this->spesa->id);
    movimento2($this->altro, now()->format('Y-m-d'), -500.00, $this->spesa->id);

    $w = Livewire::test(SpendingByCategoryChart::class, [
        'pageFilters' => ['periodo' => 'anno', 'accounts' => [$this->conto->id]],
    ])->instance();

    $m = (new ReflectionClass($w))->getMethod('getData');
    $m->setAccessible(true);

    expect($m->invoke($w)['datasets'][0]['data'])->toBe([100.0]);
});

it('apre la tabella dei movimenti del periodo', function () {
    movimento2($this->conto, now()->format('Y-m-d'), -100.00, $this->spesa->id);
    $vecchio = movimento2($this->conto, now()->copy()->subMonths(6)->format('Y-m-d'), -900.00, $this->spesa->id);

    Livewire::test(PeriodMovementsTable::class, ['pageFilters' => ['periodo' => 'mese']])
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$vecchio]);
});
