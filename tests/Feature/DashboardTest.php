<?php

use App\Filament\Widgets\FinanceOverview;
use App\Filament\Widgets\MonthlyFlowChart;
use App\Filament\Widgets\SpendingByCategoryChart;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/** I dati che il riquadro passa al grafico: il metodo è protetto per contratto. */
function datiDi(object $widget): array
{
    $m = (new ReflectionClass($widget))->getMethod('getData');
    $m->setAccessible(true);

    return $m->invoke($widget);
}

function statsDi(object $widget): array
{
    $m = (new ReflectionClass($widget))->getMethod('getStats');
    $m->setAccessible(true);

    return $m->invoke($widget);
}

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
    $this->account = Account::factory()->create();
    $this->spesa = Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
    $this->compensi = Category::create(['name' => 'Fatture e compensi', 'kind' => 'income']);
    $this->giroconti = Category::create(['name' => 'Giroconti', 'kind' => 'transfer']);
});

function movimentoIl(Account $account, string $date, float $amount, ?int $categoryId = null): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $account->id,
        'booked_on' => $date,
        'amount' => $amount,
        'category_id' => $categoryId,
    ]);
}

it('apre i tre riquadri senza errori', function () {
    movimentoIl($this->account, now()->format('Y-m-d'), -50.00, $this->spesa->id);

    Livewire::test(FinanceOverview::class)->assertSuccessful();
    Livewire::test(MonthlyFlowChart::class)->assertSuccessful();
    Livewire::test(SpendingByCategoryChart::class)->assertSuccessful();
});

/*
 * Il motivo per cui le query stanno tutte in Reporting: se un riquadro contasse
 * i giroconti e quello accanto no, la dashboard mostrerebbe due numeri diversi
 * per la stessa cosa — e chi guarda non ha modo di sapere quale credere.
 */
it('esclude i giroconti da ogni riquadro', function () {
    $oggi = now()->format('Y-m-d');
    movimentoIl($this->account, $oggi, -100.00, $this->spesa->id);
    movimentoIl($this->account, $oggi, -900.00, $this->giroconti->id);

    $dati = datiDi(Livewire::test(MonthlyFlowChart::class)->instance());
    $uscite = $dati['datasets'][1]['data'];

    // Solo i 100 di spesa vera, non i 900 di travaso.
    expect(array_sum($uscite))->toBe(100.0);
});

it('mostra le uscite come numeri positivi, non sotto lo zero', function () {
    movimentoIl($this->account, now()->format('Y-m-d'), -250.00, $this->spesa->id);

    $dati = datiDi(Livewire::test(MonthlyFlowChart::class)->instance());

    expect(array_sum($dati['datasets'][1]['data']))->toBe(250.0);
});

it('tiene entrate e uscite su serie distinte', function () {
    $oggi = now()->format('Y-m-d');
    movimentoIl($this->account, $oggi, 1000.00, $this->compensi->id);
    movimentoIl($this->account, $oggi, -300.00, $this->spesa->id);

    $dati = datiDi(Livewire::test(MonthlyFlowChart::class)->instance());

    expect($dati['datasets'][0]['label'])->toBe('Entrate')
        ->and(array_sum($dati['datasets'][0]['data']))->toBe(1000.0)
        ->and($dati['datasets'][1]['label'])->toBe('Uscite')
        ->and(array_sum($dati['datasets'][1]['data']))->toBe(300.0);
});

it('ordina le categorie dalla più pesante', function () {
    $oggi = now()->format('Y-m-d');
    $trasporti = Category::create(['name' => 'Trasporti', 'kind' => 'expense']);
    movimentoIl($this->account, $oggi, -100.00, $this->spesa->id);
    movimentoIl($this->account, $oggi, -500.00, $trasporti->id);

    $dati = datiDi(Livewire::test(SpendingByCategoryChart::class)->instance());

    expect($dati['labels'][0])->toBe('Trasporti')
        ->and($dati['datasets'][0]['data'][0])->toBe(500.0);
});

it('conta i movimenti ancora senza categoria', function () {
    movimentoIl($this->account, now()->format('Y-m-d'), -10.00);
    movimentoIl($this->account, now()->format('Y-m-d'), -20.00, $this->spesa->id);

    $stats = statsDi(Livewire::test(FinanceOverview::class)->instance());

    expect((string) $stats[3]->getValue())->toBe('1');
});
