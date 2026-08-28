<?php

use App\Assistant\ChangesSomething;
use App\Assistant\Tools\SearchRecordsTool;
use App\Assistant\Tools\UpdateMealTool;
use App\Assistant\Tools\UpdateWorkoutTool;
use App\Health\Energy;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'birth_date' => '1994-01-01', 'height_cm' => 191, 'sex' => 'male', 'activity_factor' => 1.20,
    ]);
    Auth::setUser($this->user);
    BodyMetric::create(['measured_on' => now()->subYear(), 'weight_kg' => 84.0]);
});

/*
 * Il punto di tutto questo: registrare qualcosa e poi dover chiedere "adesso
 * ricalcola" è il passaggio che si salta. Un bilancio aggiornato solo quando
 * qualcuno se lo ricorda è peggio di nessun bilancio, perché sembra aggiornato.
 */
it('ricalcola il giorno da solo quando nasce un allenamento', function () {
    expect(DailyLog::count())->toBe(0);

    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);

    $log = DailyLog::sole();
    // (9,8 − 1) MET × 84 kg × 1 h: il MET pieno conterebbe una seconda volta
    // il basale, che nelle 24 ore c'è già.
    expect($log->activity_calories)->toBe(739)
        ->and($log->target_calories)->toBe(Energy::dailyNeed($this->user, CarbonImmutable::now()));
});

it('rifà il conto quando l\'allenamento viene corretto', function () {
    $w = Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);
    $prima = DailyLog::sole()->activity_calories;

    (new UpdateWorkoutTool)->run(['id' => $w->id, 'minuti' => 30]);

    expect(DailyLog::sole()->activity_calories)->toBe((int) round($prima / 2));
});

it('rifà il conto quando l\'allenamento viene cancellato', function () {
    $w = Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);
    $w->delete();

    expect(DailyLog::sole()->activity_calories)->toBe(0);
});

/*
 * Spostare un allenamento di data tocca DUE giorni. Senza, quello di partenza
 * continua a contare calorie di un allenamento che non c'è più — e nessuno va
 * a guardarlo, perché la modifica riguardava un altro giorno.
 */
it('aggiorna tutti e due i giorni quando un allenamento viene spostato', function () {
    $ieri = now()->subDay()->toDateString();
    $oggi = now()->toDateString();

    $w = Workout::create(['performed_on' => $ieri, 'activity' => 'Corsa', 'minutes' => 60]);
    expect(DailyLog::where('logged_on', $ieri)->sole()->activity_calories)->toBeGreaterThan(0);

    (new UpdateWorkoutTool)->run(['id' => $w->id, 'giorno' => $oggi]);

    expect(DailyLog::where('logged_on', $ieri)->sole()->activity_calories)->toBe(0)
        ->and(DailyLog::where('logged_on', $oggi)->sole()->activity_calories)->toBeGreaterThan(0);
});

it('non tocca un obiettivo che una persona ha scritto a mano', function () {
    DailyLog::create(['logged_on' => now(), 'target_calories' => 2100, 'targets_manual' => true]);

    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);

    $log = DailyLog::sole();
    expect($log->target_calories)->toBe(2100)          // la scelta resta
        ->and($log->activity_calories)->toBeGreaterThan(0);  // il consumo si aggiorna comunque
});

it('corregge un pasto senza azzerare i campi che non gli hai detto', function () {
    $m = Meal::create([
        'eaten_on' => now(), 'moment' => 'lunch',
        'description' => 'Pasta', 'calories' => 600, 'protein_g' => 20,
    ]);

    (new UpdateMealTool)->run(['id' => $m->id, 'calorie' => 750]);

    $m->refresh();
    expect($m->calories)->toBe(750)
        ->and($m->protein_g)->toBe(20)          // non toccato
        ->and($m->description)->toBe('Pasta');  // non toccato
});

it('rifiuta un id che non esiste invece di far finta di niente', function () {
    $esito = (new UpdateMealTool)->run(['id' => 999, 'calorie' => 500]);

    expect($esito->isError)->toBeTrue()
        ->and($esito->content)->toContain('cerca_registrazioni');
});

it('elenca pasti e allenamenti con il loro id, per poterli correggere', function () {
    Meal::create(['eaten_on' => now(), 'moment' => 'dinner', 'description' => 'Pesce', 'calories' => 400]);
    Workout::create(['performed_on' => now(), 'activity' => 'Nuoto', 'minutes' => 45]);

    $esito = (new SearchRecordsTool)->run(['dal' => now()->toDateString()]);

    expect($esito->content)->toContain('PASTO #')
        ->and($esito->content)->toContain('SEDUTA #')
        ->and($esito->content)->toContain('Pesce')
        ->and($esito->content)->toContain('Nuoto');
});

it('non crea una giornata vuota per un giorno senza niente da dire', function () {
    Meal::create(['eaten_on' => now(), 'moment' => 'lunch', 'description' => 'Pasta']);

    // I pasti non cambiano il fabbisogno: non c'è motivo di creare la riga.
    expect(DailyLog::count())->toBe(0);
});

it('gli strumenti di modifica si dichiarano come scritture', function () {
    expect(new UpdateMealTool)->toBeInstanceOf(ChangesSomething::class)
        ->and(new UpdateWorkoutTool)->toBeInstanceOf(ChangesSomething::class)
        // Cercare no: se contasse come scrittura, la guardia sulle promesse
        // non mantenute tacerebbe proprio quando serve.
        ->and(new SearchRecordsTool)->not->toBeInstanceOf(ChangesSomething::class);
});
