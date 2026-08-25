<?php

use App\Filament\Resources\Meals\Pages\ListMeals;
use App\Filament\Resources\SleepLogs\Pages\ListSleepLogs;
use App\Filament\Resources\Workouts\Pages\ListWorkouts;
use App\Filament\Widgets\HealthOverview;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\AgostoSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
});

function statsSalute(): array
{
    $w = Livewire::test(HealthOverview::class)->instance();
    $m = (new ReflectionClass($w))->getMethod('getStats');
    $m->setAccessible(true);

    return $m->invoke($w);
}

it('apre le schermate della salute', function () {
    Livewire::test(ListSleepLogs::class)->assertSuccessful();
    Livewire::test(ListWorkouts::class)->assertSuccessful();
    Livewire::test(ListMeals::class)->assertSuccessful();
});

it('dice quando non c\'è ancora niente, invece di mostrare zeri', function () {
    $stats = statsSalute();

    // Uno zero è un dato: dice "hai dormito zero ore". Il trattino dice
    // "non lo so", che è la verità quando non hai ancora registrato niente.
    expect((string) $stats[0]->getValue())->toBe('—')
        ->and((string) $stats[1]->getValue())->toBe('—');
});

it('fa la media del sonno sulla settimana', function () {
    SleepLog::create(['night_of' => now()->subDay(), 'minutes' => 420, 'quality' => 4]);
    SleepLog::create(['night_of' => now()->subDays(2), 'minutes' => 480, 'quality' => 5]);
    // Fuori dai sette giorni: non deve entrare nella media.
    SleepLog::create(['night_of' => now()->subDays(20), 'minutes' => 120]);

    expect((string) statsSalute()[0]->getValue())->toBe('7h 30m');
});

it('somma il movimento della settimana e dice di che tipo', function () {
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 45]);
    Workout::create(['performed_on' => now()->subDays(2), 'activity' => 'Palestra', 'minutes' => 60]);

    $stat = statsSalute()[1];

    expect((string) $stat->getValue())->toBe('1h 45m')
        ->and((string) $stat->getDescription())->toContain('Corsa');
});

it('confronta il peso con la misurazione precedente, qualunque data abbia', function () {
    BodyMetric::create(['measured_on' => now()->subMonths(3), 'weight_kg' => 80.0]);
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 78.5]);

    $stat = statsSalute()[3];

    expect((string) $stat->getValue())->toBe('78,5 kg')
        ->and((string) $stat->getDescription())->toContain('-1.5 kg');
});

it('tiene separati i dati di salute di due persone', function () {
    Meal::create(['eaten_on' => now(), 'moment' => 'lunch', 'description' => 'La mia pasta']);

    $altra = User::factory()->create();
    Auth::setUser($altra);

    expect(Meal::count())->toBe(0);

    Auth::setUser($this->user);
    expect(Meal::count())->toBe(1);
});

it('non accetta due notti per la stessa data', function () {
    SleepLog::create(['night_of' => '2026-08-01', 'minutes' => 400]);

    expect(fn () => SleepLog::create(['night_of' => '2026-08-01', 'minutes' => 500]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('registra acqua e aderenza al piano', function () {
    DailyLog::create(['logged_on' => now(), 'water_litres' => 2.5, 'nutrition_adherence' => 8]);

    $stat = statsSalute()[2];

    expect((string) $stat->getValue())->toBe('2,5 l')
        ->and((string) $stat->getDescription())->toContain('piano 8.0/10');
});

/*
 * Il seeder dei giorni di agosto va rilanciato senza paura: se duplicasse,
 * ogni esecuzione aggiungerebbe una seconda cyclette allo stesso giorno e il
 * consumo calorico di quella giornata raddoppierebbe.
 */
it('rilanciare il seeder dei giorni corregge invece di duplicare', function () {
    $seeder = new AgostoSeeder;
    $seeder->run();
    $seeder->run();

    expect(BodyMetric::count())->toBe(7)
        ->and(Workout::count())->toBe(5)
        ->and(DailyLog::count())->toBe(6);
});

it('registra la notte sotto la sera in cui è cominciata', function () {
    (new AgostoSeeder)->run();

    // Le 8,5 h riportate sul 10 agosto sono la notte del 9.
    expect(SleepLog::firstWhere('night_of', '2026-08-09')?->minutes)->toBe(510);
});

/*
 * «sgarro» non diventa un valore nullo: una giornata storta che sparisce dalle
 * medie le rende più belle di com'è andata.
 */
it('tiene le giornate storte dentro le statistiche', function () {
    (new AgostoSeeder)->run();

    $sgarro = DailyLog::firstWhere('logged_on', '2026-08-13');

    expect($sgarro->nutrition_adherence)->toBe(3)
        ->and($sgarro->notes)->toContain('Sgarro');
});

it('non inventa i dati mancanti del giorno con il solo peso', function () {
    (new AgostoSeeder)->run();

    expect(BodyMetric::firstWhere('measured_on', '2026-08-16'))->not->toBeNull()
        ->and(DailyLog::firstWhere('logged_on', '2026-08-16'))->toBeNull();
});
