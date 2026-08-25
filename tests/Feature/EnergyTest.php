<?php

use App\Assistant\Tools\EnergyBalanceTool;
use App\Assistant\Tools\SetNutritionPlanTool;
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
        'birth_date' => '1994-05-10',
        'height_cm' => 191,
        'sex' => 'male',
        'activity_factor' => 1.20,
    ]);
    Auth::setUser($this->user);
});

it('calcola l\'età dalla data di nascita, non da un numero scritto a mano', function () {
    // Il senso: fra dieci anni questo test passa ancora senza toccare niente.
    expect(Energy::age($this->user))->toBe(CarbonImmutable::parse('1994-05-10')->age);
});

it('calcola il metabolismo basale con Mifflin-St Jeor', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);

    // 10×80 + 6.25×191 − 5×età + 5
    $atteso = round(800 + 1193.75 - 5 * Energy::age($this->user) + 5);

    expect(Energy::basalRate($this->user))->toBe($atteso);
});

/*
 * Un fabbisogno calcolato su un dato inventato ha l'aria di un numero vero, ed
 * è il modo peggiore di sbagliare: nessuno lo mette in dubbio.
 */
it('non inventa un fabbisogno quando mancano i dati', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    $this->user->update(['height_cm' => null]);

    expect(Energy::basalRate($this->user->fresh()))->toBeNull();
});

it('non calcola niente senza una misurazione del peso', function () {
    expect(Energy::basalRate($this->user))->toBeNull();
});

it('somma le calorie degli allenamenti del giorno', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    // Corsa: 9.8 MET × 80 kg × 1 ora ≈ 784 kcal
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);

    expect(Energy::activityBurn($this->user, CarbonImmutable::now()))->toBe(784);
});

/*
 * Chi ha scritto le calorie guardava un cardiofrequenzimetro; la tabella MET
 * guarda il nome dell'attività. Vince il primo.
 */
it('preferisce le calorie registrate alla stima da tabella', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60, 'calories' => 620]);

    expect(Energy::activityBurn($this->user, CarbonImmutable::now()))->toBe(620);
});

it('somma lo sport al fabbisogno invece di nasconderlo in un fattore', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    $fermo = Energy::dailyNeed($this->user, CarbonImmutable::now());

    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);
    $attivo = Energy::dailyNeed($this->user, CarbonImmutable::now());

    // Una giornata di corsa non può valere quanto una da fermo.
    expect($attivo)->toBeGreaterThan($fermo)
        ->and($attivo - $fermo)->toBe(784);
});

/*
 * Il fabbisogno di marzo calcolato con il peso di agosto è un numero che a
 * marzo non è mai esistito.
 */
it('usa il peso di quel giorno, non l\'ultimo in assoluto', function () {
    BodyMetric::create(['measured_on' => '2026-03-01', 'weight_kg' => 90.0]);
    BodyMetric::create(['measured_on' => '2026-08-01', 'weight_kg' => 78.0]);

    $marzo = Energy::dailyNeed($this->user, CarbonImmutable::parse('2026-03-15'));
    $agosto = Energy::dailyNeed($this->user, CarbonImmutable::parse('2026-08-15'));

    expect($marzo)->toBeGreaterThan($agosto);
});

it('mette il bilancio del giorno in una risposta leggibile', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    Meal::create(['eaten_on' => now(), 'moment' => 'lunch', 'description' => 'Pasta', 'calories' => 700]);
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 30]);

    $esito = (new EnergyBalanceTool)->run(['giorno' => now()->toDateString()]);

    expect($esito->content)->toContain('Fabbisogno stimato')
        ->and($esito->content)->toContain('700 kcal')
        ->and($esito->content)->toContain('Bilancio')
        // L'avvertenza viaggia col numero, non in fondo a un manuale.
        ->and($esito->content)->toContain('stime');
});

it('registra il piano calcolando l\'obiettivo quando non glielo dai', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);

    (new SetNutritionPlanTool)->run([
        'giorno' => now()->toDateString(),
        'previsto' => 'Colazione leggera, pranzo con pollo e riso, cena con pesce',
    ]);

    $log = DailyLog::sole();
    expect($log->target_calories)->toBe(Energy::dailyNeed($this->user, CarbonImmutable::now()))
        ->and($log->targets_manual)->toBeFalse()
        ->and($log->planned_meals)->toContain('pollo');
});

it('rispetta l\'obiettivo che gli dai, senza ricalcolarlo', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);

    (new SetNutritionPlanTool)->run(['giorno' => now()->toDateString(), 'obiettivo_calorie' => 2100]);

    expect(DailyLog::sole()->target_calories)->toBe(2100)
        ->and(DailyLog::sole()->targets_manual)->toBeTrue();
});

it('dice cosa manca invece di fallire in silenzio', function () {
    $esito = (new SetNutritionPlanTool)->run(['giorno' => now()->toDateString()]);

    expect($esito->isError)->toBeTrue()
        ->and($esito->content)->toContain('peso');
});
