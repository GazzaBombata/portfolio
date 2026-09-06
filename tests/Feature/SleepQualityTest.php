<?php

use App\Assistant\Tools\LogSleepTool;
use App\Models\SleepLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
});

/*
 * La qualità del sonno sta fra 1 e 5, e tutto il resto dell'applicazione lo
 * dichiara scrivendo «/5». In tabella c'erano degli 8, arrivati da un pezzo di
 * codice che quella scala non la conosceva: un valore fuori scala non si vede,
 * perché 8 resta un bel voto in tutte e due le scale.
 */
it('accetta la qualità da 1 a 5', function (int $qualita) {
    $log = SleepLog::create(['night_of' => '2026-03-01', 'minutes' => 420, 'quality' => $qualita]);

    expect($log->fresh()->quality)->toBe($qualita);
})->with([1, 2, 3, 4, 5]);

it('rifiuta una qualità fuori scala invece di correggerla di nascosto', function (int $qualita) {
    expect(fn () => SleepLog::create(['night_of' => '2026-03-02', 'quality' => $qualita]))
        ->toThrow(InvalidArgumentException::class);

    expect(SleepLog::count())->toBe(0);
})->with([0, 6, 8, 10]);

it('rifiuta anche quando la qualità arriva su una riga che esiste già', function () {
    $log = SleepLog::create(['night_of' => '2026-03-03', 'quality' => 4]);

    expect(fn () => $log->update(['quality' => 8]))->toThrow(InvalidArgumentException::class)
        ->and($log->fresh()->quality)->toBe(4);
});

/*
 * L'ultima rete è nel database: il modello si aggira con una query scritta a
 * mano, e questa colonna è già stata riempita una volta da qualcosa che il
 * form non attraversa nemmeno.
 */
it('lo rifiuta anche il database, saltando il modello', function () {
    expect(fn () => DB::table('sleep_logs')->insert([
        'user_id' => $this->user->id,
        'night_of' => '2026-03-04',
        'quality' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('la chat non registra una qualità su dieci, e dice perché', function () {
    $risultato = (new LogSleepTool)->run(['notte_del' => '2026-03-05', 'minuti' => 430, 'qualita' => 8]);

    expect($risultato->isError)->toBeTrue()
        ->and($risultato->content)->toContain('da 1 (pessima) a 5 (ottima)')
        ->and(SleepLog::count())->toBe(0);
});

it('la chat registra normalmente una qualità in scala', function () {
    $risultato = (new LogSleepTool)->run(['notte_del' => '2026-03-06', 'minuti' => 430, 'qualita' => 4]);

    expect($risultato->isError)->toBeFalse()
        ->and(SleepLog::sole()->quality)->toBe(4);
});

it('dichiara la scala anche nello schema che legge il modello', function () {
    $qualita = (new LogSleepTool)->schema()['properties']['qualita'];

    expect($qualita['minimum'])->toBe(1)->and($qualita['maximum'])->toBe(5);
});
