<?php

use App\Assistant\Tools\ExerciseHistoryTool;
use App\Assistant\Tools\LogWorkoutTool;
use App\Assistant\Tools\UpdateWorkoutTool;
use App\Health\Energy;
use App\Models\BodyMetric;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'birth_date' => '1990-01-01', 'height_cm' => 180, 'sex' => 'male', 'activity_factor' => 1.4,
    ]);
    $this->actingAs($this->user);
    BodyMetric::create(['measured_on' => now()->subDays(30), 'weight_kg' => 80.0]);

    /*
     * Il tempo sta fermo: lo storico dice «fermo a 60 kg da 21 giorni», e
     * quel numero è la distanza dall'oggi vero. Senza congelarlo il test
     * passa il giorno in cui lo scrivi e fallisce due giorni dopo, che è il
     * modo peggiore di avere un test — sembra una regressione e non lo è.
     */
    $this->travelTo(CarbonImmutable::parse('2026-08-28 10:00'));

    $this->giorno = CarbonImmutable::parse('2026-08-28');
});

/*
 * Il gemello di `eaten()` sui pasti, e serve alla stessa cosa: una seduta in
 * programma per giovedì non ha bruciato niente. Contarla annuncerebbe un
 * margine guadagnato con un allenamento che non è ancora stato fatto — e il
 * numero resta plausibile, quindi nessuno se ne accorge.
 */
it('non conta le calorie di una seduta solo prevista', function () {
    Workout::create(['kind' => 'planned', 'performed_on' => $this->giorno, 'activity' => 'corsa', 'minutes' => 60]);

    expect(Energy::activityBurn($this->user, $this->giorno))->toBe(0);
});

it('le conta appena diventa fatta', function () {
    $seduta = Workout::create(['kind' => 'planned', 'performed_on' => $this->giorno, 'activity' => 'corsa', 'minutes' => 60]);

    expect(Energy::activityBurn($this->user, $this->giorno))->toBe(0);

    (new UpdateWorkoutTool)->run(['id' => $seduta->id, 'tipo' => 'fatta']);

    expect(Energy::activityBurn($this->user, $this->giorno))->toBeGreaterThan(0);
});

/*
 * La ragione per cui gli esercizi sono righe figlie e non sedute separate:
 * cinque righe per una palestra sola conterebbero cinque volte le calorie
 * della stessa ora, perché il conto somma MET per minuti riga per riga.
 */
it('non moltiplica le calorie per il numero di esercizi', function () {
    (new LogWorkoutTool)->run([
        'giorno' => $this->giorno->toDateString(),
        'attivita' => 'palestra',
        'tipo' => 'fatta',
        'proposta_da' => 'giorgio',
        'minuti' => 60,
        'esercizi' => [
            ['nome' => 'panca piana', 'serie' => 4, 'ripetizioni' => 8, 'carico_kg' => 60],
            ['nome' => 'squat', 'serie' => 5, 'ripetizioni' => 5, 'carico_kg' => 80],
            ['nome' => 'lat machine', 'serie' => 3, 'ripetizioni' => 12, 'carico_kg' => 45],
        ],
    ]);

    $conEsercizi = Energy::activityBurn($this->user, $this->giorno);

    Workout::query()->delete();
    Workout::create(['performed_on' => $this->giorno, 'activity' => 'palestra', 'minutes' => 60]);

    expect($conEsercizi)->toBe(Energy::activityBurn($this->user, $this->giorno))
        ->and($conEsercizi)->toBeGreaterThan(0);
});

it('tiene gli esercizi in ordine dentro la seduta', function () {
    (new LogWorkoutTool)->run([
        'giorno' => $this->giorno->toDateString(),
        'attivita' => 'palestra',
        'tipo' => 'fatta',
        'proposta_da' => 'giorgio',
        'minuti' => 60,
        'esercizi' => [
            ['nome' => 'panca piana', 'serie' => 4, 'ripetizioni' => 8, 'carico_kg' => 60],
            ['nome' => 'squat', 'serie' => 5, 'ripetizioni' => 5, 'carico_kg' => 80],
        ],
    ]);

    $seduta = Workout::sole();

    expect(Workout::count())->toBe(1)
        ->and($seduta->exercises->pluck('name')->all())->toBe(['panca piana', 'squat'])
        ->and($seduta->exercises->first()->summary())->toBe('panca piana 4×8 a 60 kg')
        ->and($seduta->exercises->first()->load_kg)->not->toBeNull()
        ->and($seduta->exercises->first()->volumeKg())->toBe(1920.0);
});

/*
 * L'assistente è l'autore della scheda, ma non può essere l'autore di quello
 * che è successo: il consuntivo lo racconta Giorgio. Senza questo confine
 * «proposta da te» diventerebbe un'etichetta che si attacca a caso, e la
 * distinzione che serve a rileggere il mese non varrebbe più niente.
 */
it('non lascia che il consulente si attribuisca una seduta già fatta', function () {
    $esito = (new LogWorkoutTool)->run([
        'giorno' => $this->giorno->toDateString(),
        'attivita' => 'palestra',
        'tipo' => 'fatta',
        'proposta_da' => 'te',
    ]);

    expect($esito->isError)->toBeTrue()
        ->and(Workout::count())->toBe(0);
});

it('marca la scheda che ha proposto il consulente', function () {
    (new LogWorkoutTool)->run([
        'giorno' => $this->giorno->addDay()->toDateString(),
        'attivita' => 'palestra',
        'tipo' => 'prevista',
        'proposta_da' => 'te',
        'esercizi' => [['nome' => 'panca piana', 'serie' => 4, 'ripetizioni' => 8, 'carico_kg' => 62.5]],
    ]);

    expect(Workout::sole()->proposedByAssistant())->toBeTrue()
        ->and(Workout::sole()->kind)->toBe('planned');
});

it('sostituisce gli esercizi invece di accodarli', function () {
    (new LogWorkoutTool)->run([
        'giorno' => $this->giorno->toDateString(), 'attivita' => 'palestra',
        'tipo' => 'fatta', 'proposta_da' => 'giorgio',
        'esercizi' => [['nome' => 'panca piana', 'serie' => 3, 'ripetizioni' => 10, 'carico_kg' => 55]],
    ]);

    (new UpdateWorkoutTool)->run([
        'id' => Workout::sole()->id,
        'esercizi' => [['nome' => 'panca piana', 'serie' => 4, 'ripetizioni' => 8, 'carico_kg' => 60]],
    ]);

    expect(WorkoutExercise::count())->toBe(1)
        ->and((float) WorkoutExercise::sole()->load_kg)->toBe(60.0);
});

/* Lo storico */

function seduta(CarbonImmutable $giorno, float $carico, string $kind = 'done'): void
{
    $w = Workout::create(['kind' => $kind, 'performed_on' => $giorno, 'activity' => 'palestra', 'minutes' => 60]);
    WorkoutExercise::create(['workout_id' => $w->id, 'position' => 0, 'name' => 'panca piana', 'sets' => 4, 'reps' => 8, 'load_kg' => $carico]);
}

it('vede il carico che non si muove', function () {
    seduta($this->giorno->subDays(21), 60);
    seduta($this->giorno->subDays(14), 60);
    seduta($this->giorno->subDays(7), 60);

    $esito = (new ExerciseHistoryTool)->run(['esercizio' => 'panca', 'al' => $this->giorno->toDateString()]);

    expect($esito->content)->toContain('panca piana')
        ->toContain('3 sedute')
        ->toContain('fermo a 60 kg da 21 giorni');
});

it('vede il carico che sale', function () {
    seduta($this->giorno->subDays(14), 60);
    seduta($this->giorno->subDays(7), 65);

    $esito = (new ExerciseHistoryTool)->run(['esercizio' => 'panca', 'al' => $this->giorno->toDateString()]);

    expect($esito->content)->toContain('da 60 a 65 kg')->toContain('+5');
});

/*
 * Una scheda dice cosa si vorrebbe sollevare. Una progressione costruita su
 * quella misura le intenzioni, e sarebbe sempre in crescita.
 */
it('non misura la progressione sulle sedute solo previste', function () {
    seduta($this->giorno->subDays(7), 60);
    seduta($this->giorno->addDay(), 70, 'planned');

    $esito = (new ExerciseHistoryTool)->run(['al' => $this->giorno->addDays(7)->toDateString()]);

    expect($esito->content)->toContain('1 seduta')->not->toContain('70');
});

it('dice che non sa invece di tirare a indovinare', function () {
    $esito = (new ExerciseHistoryTool)->run(['esercizio' => 'stacco']);

    expect($esito->content)->toContain('Nessun esercizio registrato')
        ->toContain('non tirare a indovinare');
});

/* Il confine di sempre: gli esercizi di una persona non si vedono dall'altra. */
it('non mostra gli esercizi di un\'altra persona', function () {
    seduta($this->giorno->subDay(), 60);

    $altra = User::factory()->create();
    $this->actingAs($altra);

    expect(WorkoutExercise::count())->toBe(0)
        ->and((new ExerciseHistoryTool)->run([])->content)->toContain('Nessun esercizio registrato');
});

/*
 * Il pannello si usa dal telefono, e una seduta con gli esercizi dentro è la
 * schermata che è cambiata di più: vale la pena sapere che si apre.
 */
it('apre le pagine delle sedute nel pannello', function () {
    $this->user->forceFill(['app_authentication_secret' => encrypt('ABCDEFGHIJKLMNOP')])->save();
    seduta($this->giorno->subDay(), 60);

    $this->actingAs($this->user);

    $this->get('/admin/workouts')->assertOk();
    $this->get('/admin/workouts/create')->assertOk();
    $this->get('/admin/workouts/'.Workout::sole()->id.'/edit')->assertOk();
});

/* I chili si scrivono all'italiana anche quando hanno i decimali. */
it('scrive 62,5 e non 62.50', function () {
    $w = Workout::create(['performed_on' => $this->giorno, 'activity' => 'palestra', 'minutes' => 60]);
    WorkoutExercise::create(['workout_id' => $w->id, 'name' => 'panca piana', 'sets' => 4, 'reps' => 8, 'load_kg' => 62.5]);

    expect(WorkoutExercise::sole()->summary())->toBe('panca piana 4×8 a 62,5 kg');
});
