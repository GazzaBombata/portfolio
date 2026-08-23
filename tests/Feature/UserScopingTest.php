<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/*
 * Two people share this application. These tests are the boundary between them:
 * if any of them go red, one person can see the other's money.
 */

it('mostra a ciascuno solo le proprie transazioni', function () {
    $giorgio = User::factory()->create();
    $morosa = User::factory()->create();

    Auth::setUser($giorgio);
    $account = Account::factory()->create();
    Transaction::factory()->count(3)->create(['account_id' => $account->id]);

    Auth::setUser($morosa);
    $suo = Account::factory()->create();
    Transaction::factory()->count(2)->create(['account_id' => $suo->id]);

    expect(Transaction::count())->toBe(2);

    Auth::setUser($giorgio);
    expect(Transaction::count())->toBe(3);
});

it('non restituisce nulla quando non c\'è un utente autenticato', function () {
    $giorgio = User::factory()->create();

    Auth::setUser($giorgio);
    Transaction::factory()->count(3)->create();

    Auth::forgetGuards();

    // Fail closed: senza utente si vede il vuoto, non tutto.
    expect(Transaction::count())->toBe(0)
        ->and(Account::count())->toBe(0);
});

it('rifiuta di creare una riga senza utente invece di lasciarla orfana', function () {
    Auth::forgetGuards();

    expect(fn () => Account::create(['name' => 'Conto fantasma']))
        ->toThrow(RuntimeException::class);
});

it('assegna automaticamente user_id a chi sta scrivendo', function () {
    $giorgio = User::factory()->create();
    Auth::setUser($giorgio);

    $account = Account::create(['name' => 'Conto principale']);

    expect($account->user_id)->toBe($giorgio->id);
});

it('permette a un job di uscire dallo scope solo dichiarandolo', function () {
    $giorgio = User::factory()->create();
    $morosa = User::factory()->create();

    Auth::setUser($giorgio);
    Account::factory()->create();
    Auth::setUser($morosa);
    Account::factory()->create();

    expect(Account::count())->toBe(1)
        ->and(Account::acrossAllUsers()->count())->toBe(2);
});
