<?php

use App\Assistant\Runner;
use App\Assistant\Topic;
use App\Health\Gaps;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->oggi = CarbonImmutable::parse('2026-08-28');
    $this->domani = $this->oggi->addDay();
});

/**
 * Riempie tutto quello che oggi deve avere, così ogni test può togliere una
 * cosa sola e vedere che è esattamente quella a mancare.
 */
function giornataCompleta(CarbonImmutable $oggi): void
{
    DailyLog::create(['logged_on' => $oggi, 'steps' => 8000, 'water_litres' => 2.0]);
    SleepLog::create(['night_of' => $oggi->subDay(), 'minutes' => 450]);
    BodyMetric::create(['measured_on' => $oggi, 'weight_kg' => 80.0]);
    Workout::create(['kind' => 'done', 'performed_on' => $oggi, 'activity' => 'cyclette', 'minutes' => 30]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
}

it('elenca tutto quando la giornata è vuota', function () {
    expect(Gaps::today($this->oggi))->toBe([
        'passi',
        'acqua',
        'sonno della notte scorsa',
        'peso',
        'allenamenti',
        'pasti previsti',
        'pasti mangiati',
    ]);
});

it('non elenca niente quando la giornata è completa', function () {
    giornataCompleta($this->oggi);

    expect(Gaps::today($this->oggi))->toBe([]);
});

it('vede mancare solo il dato tolto', function (string $manca, callable $togli) {
    giornataCompleta($this->oggi);
    $togli($this->oggi);

    expect(Gaps::today($this->oggi))->toBe([$manca]);
})->with([
    ['passi', fn (CarbonImmutable $g) => DailyLog::query()->update(['steps' => null])],
    ['acqua', fn (CarbonImmutable $g) => DailyLog::query()->update(['water_litres' => null])],
    ['sonno della notte scorsa', fn (CarbonImmutable $g) => SleepLog::query()->delete()],
    ['peso', fn (CarbonImmutable $g) => BodyMetric::query()->delete()],
    ['allenamenti', fn (CarbonImmutable $g) => Workout::query()->delete()],
    ['pasti previsti', fn (CarbonImmutable $g) => Meal::query()->planned()->delete()],
    ['pasti mangiati', fn (CarbonImmutable $g) => Meal::query()->eaten()->delete()],
]);

/*
 * Il sonno di stanotte è datato ieri: è la stessa regola che vale in tutta
 * l'applicazione, e sbagliarla qui farebbe chiedere ogni mattina una notte
 * appena registrata.
 */
it('cerca il sonno alla data di ieri, non a quella di oggi', function () {
    giornataCompleta($this->oggi);
    SleepLog::query()->update(['night_of' => $this->oggi]);

    expect(Gaps::today($this->oggi))->toBe(['sonno della notte scorsa']);
});

/*
 * Un pasto senza calorie non è un pasto mancante: è nel conto, e fa risultare
 * la giornata più leggera di quello che è. Va chiesto, ma con l'altro nome.
 */
it('distingue il pasto che manca da quello senza calorie', function () {
    giornataCompleta($this->oggi);
    Meal::create(['kind' => 'eaten', 'eaten_on' => $this->oggi, 'moment' => 'dinner', 'description' => 'cena fuori']);

    expect(Gaps::today($this->oggi))->toBe(['valori nutrizionali di 1 pasto già registrato']);
});

/*
 * Di domani si decide, non si consuntiva. Chiedere i pasti mangiati di domani
 * vorrebbe dire elencare tutti i giorni un buco che non si può chiudere.
 */
it('a domani chiede solo le decisioni', function () {
    expect(Gaps::tomorrow($this->domani))->toBe(['pasti previsti', 'allenamenti in programma']);
});

it('non chiede a domani niente di già deciso', function () {
    Meal::create(['kind' => 'planned', 'eaten_on' => $this->domani, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
    Workout::create(['kind' => 'planned', 'performed_on' => $this->domani, 'activity' => 'cyclette', 'minutes' => 30]);

    expect(Gaps::tomorrow($this->domani))->toBe([]);
});

/*
 * Una seduta di domani segnata come FATTA non è un programma: è un errore di
 * registrazione. Contarla come piano fatto smetterebbe di chiedere la cosa che
 * manca davvero, e in più quel giorno il fabbisogno conterebbe calorie che
 * nessuno ha ancora bruciato.
 */
it('non scambia per programma una seduta di domani segnata come fatta', function () {
    Workout::create(['kind' => 'done', 'performed_on' => $this->domani, 'activity' => 'cyclette', 'minutes' => 30]);

    expect(Gaps::tomorrow($this->domani))->toContain('allenamenti in programma');
});

it('scrive una riga sola con i due giorni', function () {
    giornataCompleta($this->oggi);
    DailyLog::query()->update(['steps' => null]);

    expect(Gaps::line($this->oggi))
        ->toBe('Da completare: oggi (28/08) passi; domani (29/08) pasti previsti, allenamenti in programma.');
});

/*
 * Quando non manca niente la riga deve dirlo, non sparire: un contesto muto
 * lascia il modello libero di ricordare qualcosa a caso.
 */
it('dice esplicitamente quando non c\'è niente da ricordare', function () {
    giornataCompleta($this->oggi);
    Meal::create(['kind' => 'planned', 'eaten_on' => $this->domani, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
    Workout::create(['kind' => 'planned', 'performed_on' => $this->domani, 'activity' => 'cyclette', 'minutes' => 30]);

    expect(Gaps::line($this->oggi))->toBe('Da completare: niente, oggi e domani sono a posto. Non ricordargli nulla.');
});

/* Il confine di sempre: i buchi sono i suoi, non quelli dell'altra persona. */
it('non guarda i dati di un\'altra persona', function () {
    $altra = User::factory()->create();
    $this->actingAs($altra);
    giornataCompleta($this->oggi);

    $this->actingAs($this->user);

    expect(Gaps::today($this->oggi))->toHaveCount(7);
});

/*
 * La riga deve stare nel blocco VARIABILE, non in quello marcato per la
 * cache: cambia a ogni registrazione, e davanti al punto di cache
 * l'annullerebbe a ogni turno — cioè farebbe pagare a prezzo pieno il prompt
 * e gli schemi degli strumenti che le stanno davanti.
 */
it('mette i buchi nel blocco fuori dalla cache, e solo nella chat salute', function () {
    $runner = app(Runner::class);
    $m = (new ReflectionClass($runner))->getMethod('systemBlocks');

    $salute = $m->invoke($runner, Topic::Health);
    $spese = $m->invoke($runner, Topic::Finance);

    expect($salute[0])->toHaveKey('cacheControl')
        ->and($salute[0]['text'])->not->toContain('Da completare:')
        ->and($salute[1])->not->toHaveKey('cacheControl')
        ->and($salute[1]['text'])->toContain('Da completare:')
        // Il consulente delle spese non registra pasti: un promemoria sui
        // pasti sotto una domanda sui movimenti è solo rumore pagato a token.
        ->and($spese[1]['text'])->not->toContain('Da completare:');
});
