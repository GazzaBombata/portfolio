<?php

use App\Ai\Budget;
use App\Ai\Pricing;
use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('calcola il costo di una chiamata dai token', function () {
    // Opus 5: 5 $ per milione in ingresso, 25 in uscita.
    expect(Pricing::cost('claude-opus-5', 1_000_000, 100_000))->toBe(7.5);

    // La scrittura in cache costa più dell'input, la lettura un decimo: se
    // finissero entrambe a zero la cache sembrerebbe gratis, e il tetto
    // mensile guarderebbe un numero più basso di quello vero.
    expect(Pricing::cost('claude-opus-5', 0, 0, 1_000_000, 1_000_000))->toBe(6.75);
});

/*
 * Un modello senza prezzo spende soldi che nessun conteggio vedrà, e il primo
 * momento in cui te ne accorgi è la fattura.
 */
it('rifiuta un modello di cui non conosce il prezzo', function () {
    expect(fn () => Pricing::ensurePriced('modello-mai-visto'))
        ->toThrow(RuntimeException::class, 'non ha un prezzo configurato');
});

it('lascia passare finché il tetto non è raggiunto', function () {
    config(['ai.monthly_limit' => 10]);
    AiUsage::create(['kind' => 'test', 'model' => 'claude-opus-5', 'cost' => 3.0]);

    Budget::guard();

    expect(Budget::remaining())->toBe(7.0);
});

it('si ferma con un errore che dice quanto è stato speso', function () {
    config(['ai.monthly_limit' => 10]);
    AiUsage::create(['kind' => 'test', 'model' => 'claude-opus-5', 'cost' => 10.5]);

    expect(fn () => Budget::guard())
        ->toThrow(RuntimeException::class, 'Tetto di spesa AI raggiunto');
});

it('conta solo il mese in corso', function () {
    config(['ai.monthly_limit' => 10]);
    AiUsage::create(['kind' => 'test', 'model' => 'claude-opus-5', 'cost' => 50.0])
        ->forceFill(['created_at' => now()->subMonths(2)])->save();

    Budget::guard();

    expect(Budget::spentThisMonth())->toBe(0.0);
});

it('senza limite configurato non blocca niente', function () {
    config(['ai.monthly_limit' => 0]);
    AiUsage::create(['kind' => 'test', 'model' => 'claude-opus-5', 'cost' => 9999.0]);

    Budget::guard();

    expect(Budget::remaining())->toBe(INF);
});

it('registra una chiamata con il costo calcolato al momento', function () {
    $usage = (object) ['inputTokens' => 200_000, 'outputTokens' => 20_000, 'cacheReadInputTokens' => 0];

    $riga = Budget::record('assistente', 'claude-opus-5', $usage);

    expect((float) $riga->cost)->toBe(1.5)
        ->and($riga->kind)->toBe('assistente');
});
