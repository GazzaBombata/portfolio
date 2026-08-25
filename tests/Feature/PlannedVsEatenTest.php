<?php

use App\Assistant\Tools\EnergyBalanceTool;
use App\Assistant\Tools\LogMealTool;
use App\Assistant\Tools\PlanMealTool;
use App\Health\Energy;
use App\Models\BodyMetric;
use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    Auth::setUser(User::factory()->create([
        'birth_date' => '1994-01-01', 'height_cm' => 191, 'sex' => 'male', 'activity_factor' => 1.20,
    ]));
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 84.0]);
    $this->oggi = now()->toDateString();
});

/*
 * Il rischio di tenerli nella stessa tabella: un piano contato come cibo fa
 * risultare rispettata una giornata in cui non si è mangiato niente di quello.
 * Il numero resta plausibile, quindi nessuno se ne accorge.
 */
it('non conta il pasto previsto fra quelli mangiati', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pollo e riso', 'calorie' => 600]);

    expect(Energy::intake(CarbonImmutable::now()))->toBe(0)
        ->and(Energy::planned(CarbonImmutable::now()))->toBe(600);
});

it('conta solo il mangiato quando ci sono entrambi', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pollo e riso', 'calorie' => 600]);
    (new LogMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pizza', 'calorie' => 900]);

    expect(Energy::intake(CarbonImmutable::now()))->toBe(900)
        ->and(Meal::count())->toBe(2);
});

it('mette previsto e mangiato uno accanto all\'altro', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pollo e riso', 'calorie' => 600]);
    (new LogMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pizza', 'calorie' => 900]);

    $esito = (new EnergyBalanceTool)->run(['giorno' => $this->oggi]);

    expect($esito->content)->toContain('Confronto col piano')
        ->and($esito->content)->toContain('Pollo e riso')
        ->and($esito->content)->toContain('Pizza');
});

/*
 * Un pasto previsto e non registrato: o è saltato, o è stato dimenticato.
 * Sono due cose diverse e solo una persona lo sa, quindi si dice e basta.
 */
it('segnala il pasto previsto di cui non c\'è traccia', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'dinner', 'descrizione' => 'Pesce al forno', 'calorie' => 500]);

    $esito = (new EnergyBalanceTool)->run(['giorno' => $this->oggi]);

    expect($esito->content)->toContain('NIENTE registrato');
});

it('segnala quello che non era in programma', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Insalata', 'calorie' => 300]);
    (new LogMealTool)->run(['giorno' => $this->oggi, 'momento' => 'snack', 'descrizione' => 'Cornetto', 'calorie' => 350]);

    $esito = (new EnergyBalanceTool)->run(['giorno' => $this->oggi]);

    expect($esito->content)->toContain('NON era in programma');
});

it('ripianificare lo stesso pasto lo corregge invece di sdoppiarlo', function () {
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pollo']);
    (new PlanMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pesce', 'calorie' => 450]);

    expect(Meal::planned()->count())->toBe(1)
        ->and(Meal::planned()->sole()->description)->toBe('Pesce');
});

it('un pasto registrato senza dire altro è cibo mangiato', function () {
    (new LogMealTool)->run(['giorno' => $this->oggi, 'momento' => 'lunch', 'descrizione' => 'Pasta']);

    // Il valore predefinito conta: è quello che si applica quando nessuno sceglie.
    expect(Meal::sole()->kind)->toBe('eaten');
});
