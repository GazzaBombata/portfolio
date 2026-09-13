<?php

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/*
 * Come per la dashboard: quello che si rompe in una view custom non è il codice
 * ma la disposizione, e quella si vede solo guardandola. Qui in più c'è un
 * motivo specifico — il pannello carica il CSS già compilato di Filament, che
 * le utility di Tailwind scritte a mano in una view non le contiene, e una
 * classe che non esiste non dà nessun errore: dà una pagina storta.
 */
it('mostra il diario con la griglia correggibile', function () {
    $user = User::factory()->create([
        'app_authentication_secret' => 'PROVA',
        'birth_date' => '1994-11-23', 'height_cm' => 191, 'sex' => 'male', 'activity_factor' => 1.20,
    ]);

    // `user_id` non è fillable da nessuna parte: lo stampa il trait
    // BelongsToUser sull'utente autenticato, che qui va impostato a mano.
    Auth::setUser($user);

    // Una settimana con dentro un po' di tutto, compreso un giorno vuoto: è la
    // riga che più facilmente si disegna male.
    foreach (range(0, 6) as $i) {
        $g = now()->copy()->subDays($i + 1)->toDateString();

        if ($i === 3) {
            continue;
        }

        SleepLog::create(['night_of' => $g, 'minutes' => 420 + $i * 10, 'quality' => 3 + ($i % 3)]);
        DailyLog::create(['logged_on' => $g, 'steps' => 7000 + $i * 900, 'water_litres' => 2.5, 'nutrition_adherence' => 7]);
        BodyMetric::create(['measured_on' => $g, 'weight_kg' => 94.5 - $i * 0.2]);
        Meal::create(['kind' => 'eaten', 'eaten_on' => $g, 'moment' => 'lunch', 'description' => 'Pasta al pomodoro e insalata', 'calories' => 700]);
        Meal::create(['kind' => 'eaten', 'eaten_on' => $g, 'moment' => 'dinner', 'description' => 'Pollo e verdure', 'calories' => 620]);
        Workout::create(['kind' => 'done', 'performed_on' => $g, 'activity' => 'Palestra', 'minutes' => 50, 'intensity' => 4]);
    }

    // L'autenticazione si imposta sul test, non sulla pagina: `visit()` non
    // ha un `actingAs()`.
    $this->actingAs($user);

    $page = visit('/admin/diario');

    $page->assertSee('La tabella')
        ->assertSee('Scarica il PDF')
        ->assertSee('Fabbisogno')
        ->assertNoJavascriptErrors()
        ->screenshot(fullPage: true);
});
