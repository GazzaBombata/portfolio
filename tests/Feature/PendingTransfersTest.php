<?php

use App\Filament\Widgets\PendingTransfersTable;
use App\Finance\TransferMatcher;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
    $this->conto = Account::factory()->create(['name' => 'Conto']);
    $this->carta = Account::factory()->create(['name' => 'Carta']);
});

function mov(Account $a, string $data, float $importo, string $descrizione): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $a->id, 'booked_on' => $data, 'amount' => $importo,
        'description' => $descrizione, 'raw_description' => $descrizione,
        'fingerprint' => sha1($a->id.$data.$importo.$descrizione),
    ]);
}

it('elenca le coppie che non è riuscito a decidere', function () {
    mov($this->carta, '2026-07-14', -35.00, 'QUOTA ASSOCIATIVA');
    mov($this->conto, '2026-07-12', 35.00, 'ACCREDITO PAYPAL');

    $dubbi = app(TransferMatcher::class)->pending();

    expect($dubbi)->toHaveCount(1)
        ->and($dubbi[0]['reason'])->toContain('nessuno dei due');
});

it('non elenca quelle che ha già deciso da solo', function () {
    mov($this->conto, '2026-08-08', -869.58, 'BONIFICO A CARTA');
    mov($this->carta, '2026-08-08', 869.58, 'PAGAMENTO RICEVUTO');

    app(TransferMatcher::class)->run();

    expect(app(TransferMatcher::class)->pending())->toBeEmpty();
});

/*
 * Il riquadro esiste solo quando ha qualcosa da dire: uno vuoto che annuncia
 * «niente da fare» occupa lo stesso spazio e insegna a non guardarlo.
 */
it('non mostra il riquadro quando non c\'è niente da confermare', function () {
    expect(PendingTransfersTable::canView())->toBeFalse();

    mov($this->carta, '2026-07-14', -35.00, 'QUOTA ASSOCIATIVA');
    mov($this->conto, '2026-07-12', 35.00, 'ACCREDITO PAYPAL');

    expect(PendingTransfersTable::canView())->toBeTrue();
});

it('marca la coppia come giroconto quando la confermi', function () {
    $uscita = mov($this->carta, '2026-07-14', -35.00, 'QUOTA ASSOCIATIVA');
    $entrata = mov($this->conto, '2026-07-12', 35.00, 'ACCREDITO PAYPAL');

    Livewire::test(PendingTransfersTable::class)
        ->call('confirm', $uscita->id, $entrata->id);

    expect($uscita->fresh()->category->kind)->toBe('transfer')
        ->and($entrata->fresh()->category->kind)->toBe('transfer')
        // Deciso da una persona: la passata automatica non lo tocca più.
        ->and($uscita->fresh()->category_locked)->toBeTrue();
});
