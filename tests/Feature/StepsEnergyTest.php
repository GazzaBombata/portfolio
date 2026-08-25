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

    // (5,0 − 1) MET × 94 kg × 1 h, non 5,0 × 94
    expect(Energy::activityBurn($this->user, $this->oggi))->toBe(376);
});

it('usa le calorie registrate quando ci sono, senza toccarle', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Cyclette', 'minutes' => 60, 'calories' => 300]);

    expect(Energy::activityBurn($this->user, $this->oggi))->toBe(300);
});
