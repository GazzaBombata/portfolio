<?php

use Tests\TestCase;

/*
 * Feature tests boot the framework: they need the application's TestCase, or
 * every facade call fails with "A facade root has not been set".
 */
pest()->extend(TestCase::class)->in('Feature');
