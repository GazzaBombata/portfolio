<?php

use App\Health\Energy;
use App\Models\BodyMetric;
use App\Models\DailyLog;
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
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 94.0]);
    $this->oggi = CarbonImmutable::now();
});

/*
 * Un fattore «sedentario» non vuol dire immobile: comprende già qualche
 * migliaio di passi. Contarli dal primo li conterebbe due volte.
 */
it('non conta i passi che il fattore di attività già comprende', function () {
    DailyLog::create(['logged_on' => now(), 'steps' => 4000]);

    expect(Energy::stepsBurn($this->user, $this->oggi))->toBe(0);
});

it('conta i passi oltre la soglia', function () {
    DailyLog::create(['logged_on' => now(), 'steps' => 21300]);

    // (21300 − 5000) / 1300 km × 0,5 kcal/kg/km × 94 kg
    expect(Energy::stepsBurn($this->user, $this->oggi))->toBe(589);
});

it('una giornata camminata pesa più di una ferma, anche senza allenamenti', function () {
    $ferma = Energy::dailyNeed($this->user, $this->oggi);

    DailyLog::create(['logged_on' => now(), 'steps' => 18000]);

    expect(Energy::dailyNeed($this->user, $this->oggi))->toBeGreaterThan($ferma);
});

it('segnala quando una camminata registrata è già dentro i passi', function () {
    DailyLog::create(['logged_on' => now(), 'steps' => 15000]);
    Workout::create(['performed_on' => now(), 'activity' => 'Camminata', 'minutes' => 90]);

    expect(Energy::overlappingWorkouts($this->oggi))->toContain('Camminata');
});

it('non segnala la cyclette, che di passi non ne produce', function () {
    DailyLog::create(['logged_on' => now(), 'steps' => 15000]);
    Workout::create(['performed_on' => now(), 'activity' => 'Cyclette', 'minutes' => 45]);

    expect(Energy::overlappingWorkouts($this->oggi))->toBeEmpty();
});

/*
 * Un MET è il consumo da fermi, ed è già dentro le 24 ore del basale. Usare il
 * MET pieno lo conta una seconda volta per la durata dell'allenamento: un
 * errore piccolo, sistematico, e sempre nella stessa direzione.
 */
it('conta l\'allenamento al netto del metabolismo basale', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Cyclette', 'minutes' => 60]);

    // (6,0 − 1) MET × 94 kg × 1 h, non 6,0 × 94
    expect(Energy::activityBurn($this->user, $this->oggi))->toBe(470);
});

/*
 * Un cardio riporta le calorie TOTALI della sessione: dentro c'è anche quello
 * che il corpo avrebbe consumato da fermo, e le 24 ore del basale lo contano
 * già. È la stessa doppia contabilità che il «MET − 1» toglie dall'altro ramo,
 * e per mesi da questo non la toglieva nessuno.
 */
it('toglie dalle calorie lette su un cardio il basale di quei minuti', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Cyclette', 'minutes' => 60, 'calories' => 300]);

    $basaleOra = Energy::basalRate($this->user, 94.0) * 1.20 / 24;

    expect(Energy::activityBurn($this->user, $this->oggi))->toBe((int) round(300 - $basaleOra))
        // Resta comunque quello che ha scritto la persona, non la stima da
        // tabella: 300 lorde valgono meno di 470, non 470.
        ->toBeLessThan(300);
});

/*
 * Senza durata non si sa su quanti minuti togliere il basale, e inventarla
 * sarebbe peggio del problema. Il numero passa intero e si dichiara.
 */
it('lascia intere le calorie di una seduta senza durata, e lo dice', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Basket', 'calories' => 400]);

    expect(Energy::activityBurn($this->user, $this->oggi))->toBe(400)
        ->and(Energy::grossWithoutDuration($this->oggi))->toBe(['Basket']);
});

/*
 * L'intensità era registrata e mai usata: «bici molto tranquilla 1/5» contava
 * come una bici a tutta.
 */
it('fa pesare l\'intensità sul consumo, con il 3 come neutro', function () {
    $burn = function (?int $intensita): int {
        Workout::query()->delete();
        Workout::create(array_filter([
            'performed_on' => now(), 'activity' => 'Bici', 'minutes' => 60, 'intensity' => $intensita,
        ], fn ($v) => $v !== null));

        return Energy::activityBurn($this->user, $this->oggi);
    };

    // (7,5 − 1) × 94 = 611 al neutro.
    expect($burn(null))->toBe(611)
        ->and($burn(3))->toBe(611)
        ->and($burn(1))->toBe(367)
        ->and($burn(5))->toBe(855);
});

/*
 * Le bocce valevano 5,0 MET come una seduta di pesi: 381 kcal a partita.
 */
it('non conta le bocce come una seduta di pesi', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Bocce', 'minutes' => 60]);

    // (3,0 − 1) × 94, non (5,0 − 1) × 94
    expect(Energy::activityBurn($this->user, $this->oggi))->toBe(188);
});

/*
 * Il default a 5,0 resta, perché un nome sconosciuto non dice cosa sia. Ma
 * adesso si vede: era il modo in cui le bocce sono valse un'ora di palestra
 * per settimane senza che niente lo dicesse.
 */
it('dichiara quando il MET è quello di ripiego', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Torneo di freccette', 'minutes' => 60]);
    Workout::create(['performed_on' => now(), 'activity' => 'Basket', 'minutes' => 60]);

    expect(Energy::defaultMetWorkouts($this->oggi))->toBe(['Torneo di freccette']);
});
