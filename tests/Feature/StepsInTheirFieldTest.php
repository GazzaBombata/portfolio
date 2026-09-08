<?php

use App\Assistant\Tools\LogDailyTool;
use App\Assistant\Tools\LogWorkoutTool;
use App\Models\DailyLog;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * I passi hanno un campo solo — `daily_logs.steps` — ed è l'unico posto da cui
 * `Energy::stepsBurn()` li legge. Scritti altrove sono un dato raccolto,
 * scritto e perso: fra il 25 e il 29/08/2026 sono stati detti in chat cinque
 * giorni di fila e sono finiti quattro volte nella nota della giornata e una
 * nella descrizione di un allenamento. Nel fabbisogno hanno contato zero, e
 * niente lo diceva.
 */

beforeEach(function () {
    $this->user = User::factory()->create([
        'app_authentication_secret' => 'PROVA',
        'birth_date' => '1994-11-23', 'height_cm' => 191, 'sex' => 'male', 'activity_factor' => 1.20,
    ]);
    $this->actingAs($this->user);
});

/*
 * I passi finiti in una seduta non li legge nessuno: `Energy::stepsBurn()`
 * guarda `daily_logs.steps`. È già successo il 25/08/2026, con un allenamento
 * chiamato «Passi giornalieri (non un allenamento)» da zero calorie.
 */
it('rifiuta di registrare i passi come allenamento', function () {
    $risultato = (new LogWorkoutTool)->run([
        'giorno' => '2026-03-07',
        'attivita' => 'Passi giornalieri',
        'tipo' => 'fatta',
        'proposta_da' => 'giorgio',
    ]);

    expect($risultato->isError)->toBeTrue()
        ->and($risultato->content)->toContain('registra_giornata')
        ->and(Workout::count())->toBe(0);
});

it('lascia passare una camminata vera', function () {
    $risultato = (new LogWorkoutTool)->run([
        'giorno' => '2026-03-08', 'attivita' => 'Camminata in montagna',
        'tipo' => 'fatta', 'proposta_da' => 'giorgio', 'minuti' => 90,
    ]);

    expect($risultato->isError)->toBeFalse()->and(Workout::count())->toBe(1);
});

/*
 * Il campo `note` di registra_giornata non aveva descrizione, ed è diventato il
 * posto dove finiva tutto: fra il 25 e il 29/08/2026 i passi di cinque giornate
 * sono stati detti in chat e scritti lì dentro, con il campo «passi» vuoto
 * accanto. Nel fabbisogno hanno contato zero.
 */
it('rifiuta i passi scritti nella nota della giornata', function () {
    foreach (['7000 passi. Colazione saltata.', 'Passi: 7000, nessuna altra attività', '6000 passi'] as $nota) {
        $risultato = (new LogDailyTool)->run([
            'giorno' => '2026-03-10', 'acqua_litri' => 2.5, 'note' => $nota,
        ]);

        expect($risultato->isError)->toBeTrue()
            ->and($risultato->content)->toContain('campo «passi» è vuoto');
    }

    expect(DailyLog::count())->toBe(0);
});

it('lascia passare la nota quando i passi stanno nel loro campo', function () {
    $risultato = (new LogDailyTool)->run([
        'giorno' => '2026-03-11', 'passi' => 7000, 'note' => '7000 passi, nessuna altra attività',
    ]);

    expect($risultato->isError)->toBeFalse()
        ->and(DailyLog::sole()->steps)->toBe(7000);
});

it('non si insospettisce per una nota che parla d\'altro', function () {
    $risultato = (new LogDailyTool)->run([
        'giorno' => '2026-03-12', 'acqua_litri' => 2.0, 'note' => 'Giornata storta, 3 caffè e poca voglia',
    ]);

    expect($risultato->isError)->toBeFalse();
});
