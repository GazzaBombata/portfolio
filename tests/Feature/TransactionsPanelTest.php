<?php

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Il secondo fattore è obbligatorio: senza, ogni pagina rimanda al set-up.
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
    $this->account = Account::factory()->create();
});

it('apre la lista dei movimenti', function () {
    Transaction::factory()->count(3)->create(['account_id' => $this->account->id]);

    Livewire::test(ListTransactions::class)->assertSuccessful();
});

it('mostra solo i movimenti di chi guarda', function () {
    $mio = Transaction::factory()->create(['account_id' => $this->account->id, 'description' => 'MIA SPESA']);

    $altra = User::factory()->create();
    $this->actingAs($altra);
    $suoConto = Account::factory()->create();
    $suo = Transaction::factory()->create(['account_id' => $suoConto->id, 'description' => 'SPESA ALTRUI']);

    $this->actingAs($this->user);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$mio])
        ->assertCanNotSeeTableRecords([$suo]);
});

/*
 * Il filtro dei giroconti parte attivo: un pagamento di carta di credito conta
 * una seconda volta spese già contate una per una, e un totale che le include
 * è semplicemente sbagliato.
 */
it('nasconde i giroconti finché non li si chiede', function () {
    $spesa = Category::create(['name' => 'Spesa', 'kind' => 'expense']);
    $giroconto = Category::create(['name' => 'Giroconti', 'kind' => 'transfer']);

    $normale = Transaction::factory()->create(['account_id' => $this->account->id, 'category_id' => $spesa->id]);
    $travaso = Transaction::factory()->create(['account_id' => $this->account->id, 'category_id' => $giroconto->id]);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$normale])
        ->assertCanNotSeeTableRecords([$travaso]);
});

it('assegna una categoria a più movimenti in un colpo e la blocca', function () {
    $categoria = Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
    $movimenti = Transaction::factory()->count(3)->create(['account_id' => $this->account->id]);

    Livewire::test(ListTransactions::class)
        ->selectTableRecords($movimenti->pluck('id')->all())
        ->callTableBulkAction('assegnaCategoria', $movimenti, data: ['category_id' => $categoria->id]);

    $movimenti->each(function (Transaction $t) use ($categoria) {
        $t->refresh();
        expect($t->category_id)->toBe($categoria->id)
            ->and($t->category_locked)->toBeTrue();
    });
});
