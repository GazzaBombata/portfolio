<?php

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
});

function dati(array $filtri = ['periodo' => 'tutto']): array
{
    $w = Livewire::test(SpendingShareChart::class, ['pageFilters' => $filtri])->instance();
    $m = (new ReflectionClass($w))->getMethod('getData');
    $m->setAccessible(true);

    return $m->invoke($w);
}

function spesaIn(Account $conto, string $categoria, float $importo): void
{
    $c = Category::firstOrCreate(['name' => $categoria, 'parent_id' => null], ['kind' => 'expense']);
    Transaction::factory()->create(['account_id' => $conto->id, 'category_id' => $c->id, 'amount' => -$importo]);
}

it('mostra le categorie dalla più pesante', function () {
    spesaIn($this->conto, 'Casa', 100);
    spesaIn($this->conto, 'Trasporti', 500);

    $d = dati();

    expect($d['labels'][0])->toBe('Trasporti')
        ->and($d['datasets'][0]['data'][0])->toBe(500.0);
});

/*
 * Oltre quattro fette i colori non reggono la verifica sui daltonismi in
 * entrambi i temi: il resto va in una fetta sola invece di generare tinte che
 * due persone su cento non distinguono.
 */
it('raccoglie oltre la quarta categoria in una fetta sola', function () {
    foreach (['A' => 600, 'B' => 500, 'C' => 400, 'D' => 300, 'E' => 200, 'F' => 100] as $nome => $importo) {
        spesaIn($this->conto, $nome, $importo);
    }

    $d = dati();

    expect($d['labels'])->toHaveCount(5)
        ->and($d['labels'][4])->toBe('Altre 2 categorie')
        // 200 + 100: il mucchio conserva il suo totale
        ->and($d['datasets'][0]['data'][4])->toBe(300.0);
});

it('dice quante categorie ci sono dentro «Altro»', function () {
    foreach (['A' => 600, 'B' => 500, 'C' => 400, 'D' => 300, 'E' => 200] as $nome => $importo) {
        spesaIn($this->conto, $nome, $importo);
    }

    // «Altro» senza un numero sembra una categoria, e invece è un mucchio.
    expect(dati()['labels'][4])->toBe('Altre 1 categorie');
});

it('non mostra una fetta «Altro» quando le categorie sono quattro o meno', function () {
    spesaIn($this->conto, 'Casa', 100);
    spesaIn($this->conto, 'Trasporti', 200);

    expect(dati()['labels'])->toHaveCount(2);
});

it('esclude i giroconti, come tutto il resto della dashboard', function () {
    spesaIn($this->conto, 'Casa', 100);
    $g = Category::create(['name' => 'Giroconti', 'kind' => 'transfer']);
    Transaction::factory()->create(['account_id' => $this->conto->id, 'category_id' => $g->id, 'amount' => -9000]);

    $d = dati();

    expect($d['labels'])->toHaveCount(1)
        ->and($d['datasets'][0]['data'][0])->toBe(100.0);
});

it('segue i filtri della pagina', function () {
    $altro = Account::factory()->create();
    spesaIn($this->conto, 'Casa', 100);
    spesaIn($altro, 'Trasporti', 500);

    $d = dati(['periodo' => 'tutto', 'accounts' => [$this->conto->id]]);

    expect($d['labels'])->toBe(['Casa']);
});

it('regge un periodo senza nessuna spesa', function () {
    expect(dati()['labels'])->toBe([]);
});

it('apre il riquadro senza errori', function () {
    spesaIn($this->conto, 'Casa', 100);

    Livewire::test(SpendingShareChart::class, ['pageFilters' => ['periodo' => 'tutto']])->assertSuccessful();
});
