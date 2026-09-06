<?php

use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Come per la dashboard: quello che si rompe in una view custom non è il
 * codice ma la disposizione, e quella si vede solo guardandola. Qui in più c'è
 * un motivo specifico — il pannello carica il CSS già compilato di Filament,
 * che le utility di Tailwind scritte a mano in una view non le contiene, e una
 * classe che non esiste non dà nessun errore: dà una pagina storta.
 */
it('mostra la pagina del diario', function () {
    $user = User::factory()->create(['app_authentication_secret' => 'PROVA']);

    SleepLog::create(['user_id' => $user->id, 'night_of' => now()->subDays(3), 'minutes' => 430, 'quality' => 4]);
    DailyLog::create(['user_id' => $user->id, 'logged_on' => now()->subDays(3), 'steps' => 9200, 'water_litres' => 2.5]);
    Meal::create(['user_id' => $user->id, 'kind' => 'eaten', 'eaten_on' => now()->subDays(3), 'moment' => 'lunch', 'description' => 'Pasta al pomodoro', 'calories' => 700]);

    $page = visit('/admin/diario')->actingAs($user);

    $page->assertSee('Diario')
        ->assertSee('Che periodo')
        ->assertSee('Scarica il PDF')
        ->assertNoJavascriptErrors()
        ->screenshot('diario');
});
