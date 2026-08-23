<?php

use App\Filament\Pages\Today;
use App\Filament\Widgets\SleepChart;
use App\Filament\Widgets\WeightChart;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
});

it('apre la schermata di oggi', function () {
    Livewire::test(Today::class)->assertSuccessful();
});

it('salva sonno, acqua, peso, allenamento e pasto in un colpo', function () {
    Livewire::test(Today::class)
        ->fillForm([
            'minutes' => 430, 'quality' => 4,
            'water_litres' => 2.5, 'nutrition_adherence' => 8,
            'weight_kg' => 78.4,
            'activity' => 'Corsa', 'workout_minutes' => 40,
            'moment' => 'lunch', 'meal' => 'Pasta e insalata',
        ])
        ->call('save');

    expect(SleepLog::sole()->minutes)->toBe(430)
        ->and((float) DailyLog::sole()->water_litres)->toBe(2.5)
        ->and((float) BodyMetric::sole()->weight_kg)->toBe(78.4)
        ->and(Workout::sole()->activity)->toBe('Corsa')
        ->and(Meal::sole()->description)->toBe('Pasta e insalata');
});

/*
 * La notte registrata qui è quella cominciata IERI sera: è la convenzione che
 * rende confrontabili le notti, e sbagliarla sposta ogni dato di un giorno.
 */
it('registra la notte sotto la sera in cui è cominciata', function () {
    Livewire::test(Today::class)->fillForm(['minutes' => 400])->call('save');

    expect(SleepLog::sole()->night_of->toDateString())->toBe(now()->subDay()->toDateString());
});

it('corregge invece di duplicare quando salvi due volte', function () {
    Livewire::test(Today::class)->fillForm(['minutes' => 400, 'weight_kg' => 78.0])->call('save');
    Livewire::test(Today::class)->fillForm(['minutes' => 450, 'weight_kg' => 77.5])->call('save');

    expect(SleepLog::count())->toBe(1)
        ->and(SleepLog::sole()->minutes)->toBe(450)
        ->and(BodyMetric::count())->toBe(1);
});

it('aggiunge un secondo allenamento invece di sostituire il primo', function () {
    Livewire::test(Today::class)->fillForm(['activity' => 'Corsa', 'workout_minutes' => 40])->call('save');
    Livewire::test(Today::class)->fillForm(['activity' => 'Palestra', 'workout_minutes' => 60])->call('save');

    expect(Workout::count())->toBe(2);
});

it('non salva niente se non è stato scritto niente', function () {
    Livewire::test(Today::class)->call('save');

    expect(SleepLog::count())->toBe(0)
        ->and(DailyLog::count())->toBe(0);
});

/*
 * Con una misurazione sola non c'è un andamento: il grafico direbbe un punto.
 */
it('mostra i grafici solo quando c\'è abbastanza storia', function () {
    expect(WeightChart::canView())->toBeFalse()
        ->and(SleepChart::canView())->toBeFalse();

    BodyMetric::create(['measured_on' => now()->subDays(2), 'weight_kg' => 79]);
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 78]);
    foreach ([1, 2, 3] as $g) {
        SleepLog::create(['night_of' => now()->subDays($g), 'minutes' => 400 + $g]);
    }

    expect(WeightChart::canView())->toBeTrue()
        ->and(SleepChart::canView())->toBeTrue();
});
