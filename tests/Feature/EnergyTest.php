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
    // Corsa: (9,8 − 1) MET × 80 kg × 1 ora. Il MET pieno conterebbe di nuovo
    // il metabolismo basale di quell'ora, già dentro le 24.
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);

    expect(Energy::activityBurn($this->user, CarbonImmutable::now()))->toBe(704);
});

/*
 * Chi ha scritto le calorie guardava un cardiofrequenzimetro; la tabella MET
 * guarda il nome dell'attività. Vince il primo — ma al netto del basale di
 * quei minuti, che è lordo nel numero del cardio ed è già dentro le 24 ore.
 */
it('preferisce le calorie registrate alla stima da tabella', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60, 'calories' => 620]);

    $basaleOra = Energy::basalRate($this->user, 80.0) * 1.20 / 24;

    // Non 704, che è la stima da tabella: quella non viene nemmeno calcolata.
    expect(Energy::activityBurn($this->user, CarbonImmutable::now()))->toBe((int) round(620 - $basaleOra));
});

it('somma lo sport al fabbisogno invece di nasconderlo in un fattore', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    $fermo = Energy::dailyNeed($this->user, CarbonImmutable::now());

    Workout::create(['performed_on' => now(), 'activity' => 'Corsa', 'minutes' => 60]);
    $attivo = Energy::dailyNeed($this->user, CarbonImmutable::now());

    // Una giornata di corsa non può valere quanto una da fermo.
    expect($attivo)->toBeGreaterThan($fermo)
        ->and($attivo - $fermo)->toBe(704);
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

it('senza un numero esplicito registra il piano, non il fabbisogno', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'riso e pollo', 'calories' => 665]);

    (new SetNutritionPlanTool)->run(['giorno' => now()->toDateString()]);

    $log = DailyLog::sole();

    // 665, non il fabbisogno: obiettivo è quanto ho deciso di mangiare,
    // fabbisogno è quanto brucio, e scambiarli falsa ogni percentuale.
    expect($log->target_calories)->toBe(665)
        ->and($log->targets_manual)->toBeFalse();
});

it('rispetta l\'obiettivo che gli dai, senza ricalcolarlo', function () {
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);

    (new SetNutritionPlanTool)->run(['giorno' => now()->toDateString(), 'obiettivo_calorie' => 2100]);

    expect(DailyLog::sole()->target_calories)->toBe(2100)
        ->and(DailyLog::sole()->targets_manual)->toBeTrue();
});

it('dice cosa manca invece di fallire in silenzio', function () {
    // Senza pasti previsti non c'è un piano da sommare: lo dice e si ferma,
    // invece di mettere al suo posto il fabbisogno — che è un altro numero.
    $esito = (new SetNutritionPlanTool)->run(['giorno' => now()->toDateString()]);

    expect($esito->isError)->toBeTrue()
        ->and($esito->content)->toContain('pianifica_pasto');
});

/*
 * L'obiettivo di un giorno è la somma dei pasti previsti, e non lo si chiede
 * a nessuno: è già in tabella. Prima veniva messo al suo posto il FABBISOGNO,
 * cioè quanto si brucia — e su una giornata da 1.575 kcal di piano l'assistente
 * annunciava un obiettivo di 3.000, cioè diceva che c'era margine dove non ce
 * n'era.
 */
it('ricava l\'obiettivo del giorno dai pasti previsti', function () {
    $oggi = CarbonImmutable::today();

    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'riso e pollo', 'calories' => 665]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'dinner', 'description' => 'pesce e verdure', 'calories' => 730]);
    // Quello mangiato non c'entra con l'obiettivo: è l'altro lato del confronto.
    Meal::create(['kind' => 'eaten', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'ossobuco', 'calories' => 840]);

    expect(Energy::target($oggi))->toBe(1395);
});

it('lascia vincere un obiettivo deciso a mano', function () {
    $oggi = CarbonImmutable::today();
    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 665]);

    (new SetNutritionPlanTool)->run(['giorno' => $oggi->toDateString(), 'obiettivo_calorie' => 1800]);

    // Se una persona l'ha detto, non glielo si ricalcola sotto i piedi.
    expect(Energy::target($oggi))->toBe(1800);
});

it('non inventa un obiettivo quando non c\'è piano', function () {
    expect(Energy::target(CarbonImmutable::today()))->toBeNull();
});

/*
 * Un pasto previsto senza calorie abbassa la somma, e la differenza si legge
 * come margine disponibile: non è correggibile — nessuno sa quante calorie
 * fosse — quindi va segnalato.
 */
it('conta i pasti previsti senza calorie', function () {
    $oggi = CarbonImmutable::today();
    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'lunch', 'description' => 'riso', 'calories' => 665]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $oggi, 'moment' => 'dinner', 'description' => 'cena fuori']);

    expect(Energy::plannedWithoutCalories($oggi))->toBe(1)
        ->and(Energy::target($oggi))->toBe(665);
});
