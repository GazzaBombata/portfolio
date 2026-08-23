<?php

use App\Finance\TransferMatcher;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    Auth::setUser(User::factory()->create());
    $this->conto = Account::factory()->create(['name' => 'Conto corrente']);
    $this->carta = Account::factory()->create(['name' => 'Carta di credito']);
});

function movimento(Account $account, string $date, float $amount, string $description): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $account->id,
        'booked_on' => $date,
        'amount' => $amount,
        'raw_description' => $description,
        'description' => $description,
        'fingerprint' => sha1($account->id.$date.$amount.$description),
    ]);
}

it('riconosce il pagamento di una carta come giroconto', function () {
    $uscita = movimento($this->conto, '2026-08-08', -869.58, 'PAGAMENTO CARTA');
    $entrata = movimento($this->carta, '2026-08-08', 869.58, 'PAGAMENTO RICEVUTO - GRAZIE');

    $esito = app(TransferMatcher::class)->run();

    expect($esito['paired'])->toBe(1)
        ->and($uscita->fresh()->category->kind)->toBe('transfer')
        ->and($entrata->fresh()->category->kind)->toBe('transfer');
});

it('accetta qualche giorno di ritardo fra le due gambe', function () {
    movimento($this->conto, '2026-08-08', -500.00, 'BONIFICO');
    movimento($this->carta, '2026-08-11', 500.00, 'ACCREDITO');

    expect(app(TransferMatcher::class)->run()['paired'])->toBe(1);
});

it('non accoppia movimenti troppo lontani nel tempo', function () {
    movimento($this->conto, '2026-08-01', -500.00, 'BONIFICO');
    movimento($this->carta, '2026-08-20', 500.00, 'ACCREDITO');

    expect(app(TransferMatcher::class)->run()['paired'])->toBe(0);
});

/*
 * Soldi che escono e rientrano sullo stesso conto sono un rimborso, non un
 * travaso: quella è spesa, e va contata.
 */
it('non accoppia un rimborso sullo stesso conto', function () {
    movimento($this->conto, '2026-08-08', -80.00, 'ACQUISTO');
    movimento($this->conto, '2026-08-09', 80.00, 'RIMBORSO');

    expect(app(TransferMatcher::class)->run()['paired'])->toBe(0);
});

/*
 * Con due candidati identici la coppia giusta non è deducibile dai dati. Un
 * accoppiamento sbagliato toglie due movimenti veri dai totali senza dirlo —
 * peggio di un giroconto lasciato contato, che almeno si vede nella lista.
 */
it('non indovina quando ci sono due candidati possibili', function () {
    movimento($this->conto, '2026-08-08', -200.00, 'BONIFICO');
    movimento($this->carta, '2026-08-08', 200.00, 'ACCREDITO A');
    movimento($this->carta, '2026-08-09', 200.00, 'ACCREDITO B');

    $esito = app(TransferMatcher::class)->run();

    expect($esito['paired'])->toBe(0)
        ->and($esito['ambiguous'])->toBe(1);
});

it('non tocca una categoria scelta da una persona', function () {
    $spesa = Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
    $uscita = movimento($this->conto, '2026-08-08', -100.00, 'PAGAMENTO');
    $uscita->update(['category_id' => $spesa->id, 'category_locked' => true]);
    movimento($this->carta, '2026-08-08', 100.00, 'ACCREDITO');

    app(TransferMatcher::class)->run();

    expect($uscita->fresh()->category_id)->toBe($spesa->id);
});

/*
 * Il caso vero che ha insegnato la regola: una quota associativa da 35 € e un
 * accredito PayPal da 35 € a cinque giorni di distanza. Due movimenti veri,
 * identici per importo e vicini di data, che il primo accoppiamento aveva
 * tolto entrambi dai totali.
 */
it('non accoppia due movimenti che si somigliano solo per importo e data', function () {
    movimento($this->carta, '2026-07-14', -35.00, 'QUOTA ASSOCIATIVA ESENTE DA IVA');
    movimento($this->conto, '2026-07-09', 35.00, 'ACCREDITO TRAMITE CARTA PAYPAL');

    $esito = app(TransferMatcher::class)->run();

    expect($esito['paired'])->toBe(0)
        ->and($esito['ambiguous'])->toBe(1);
});

it('accoppia quando almeno un lato dichiara di essere un travaso', function () {
    movimento($this->conto, '2026-07-14', -35.00, 'BONIFICO A MIO FAVORE');
    movimento($this->carta, '2026-07-14', 35.00, 'ACCREDITO');

    expect(app(TransferMatcher::class)->run()['paired'])->toBe(1);
});
