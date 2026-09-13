<?php

use Tests\TestCase;

/*
 * Feature tests boot the framework: they need the application's TestCase, or
 * every facade call fails with "A facade root has not been set".
 */
pest()->extend(TestCase::class)->in('Feature');

/*
 * Anche i test del browser: senza questa riga girano sul TestCase di PHPUnit,
 * che l'applicazione non la avvia — e falliscono con «Target class [config]
 * does not exist», che non somiglia per niente alla sua causa.
 */
pest()->extend(TestCase::class)->in('Browser');
