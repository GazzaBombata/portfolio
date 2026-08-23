<?php

namespace App\Finance\Ai;

/**
 * Chi decide la categoria di un esercente che le regole non coprono.
 *
 * Un'interfaccia perché i test non devono chiamare l'API: costano, sono lente e
 * darebbero risposte diverse a ogni esecuzione, il che è l'opposto di un test.
 */
interface Classifier
{
    /**
     * @param  array<int, array{merchant: string, samples: array<int, string>, total: string, count: int}>  $merchants
     * @param  array<int, string>  $categories
     * @return array<string, string> esercente => categoria, solo per quelli decisi
     */
    public function classify(array $merchants, array $categories): array;
}
