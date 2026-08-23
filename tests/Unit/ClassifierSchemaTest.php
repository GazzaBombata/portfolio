<?php

use App\Finance\Ai\ClaudeClassifier;

/*
 * Lo schema è il contratto con il modello, e vale la pena verificarlo qui
 * perché il modo in cui si è rotto non somigliava a un guasto.
 *
 * Dedotto da una classe PHP, `array<int, MerchantGuess>` diventava "un elenco
 * di stringhe": il modello rispondeva con le sole categorie, senza il nome
 * dell'esercente a cui appartenevano. Nessun errore, nessuna eccezione, una
 * risposta ben formata — e quaranta classificazioni buttate perché non si
 * sapeva più di chi fossero. Sembrava un modello prudente che non si sbilancia.
 */
it('descrive ogni voce come oggetto con esercente e categoria', function () {
    $schema = (new ReflectionClass(ClaudeClassifier::class))->getConstant('SCHEMA');

    $items = $schema['properties']['merchants']['items'];

    expect($items['type'])->toBe('object')
        ->and($items['properties'])->toHaveKeys(['merchant', 'category'])
        ->and($items['properties']['merchant']['type'])->toBe('string')
        // La categoria deve poter essere null: è così che il modello dice
        // "non lo so" su un bonifico, invece di indovinare.
        ->and($items['properties']['category']['type'])->toContain('null')
        ->and($items['required'])->toContain('merchant')
        ->and($items['additionalProperties'])->toBeFalse();
});
