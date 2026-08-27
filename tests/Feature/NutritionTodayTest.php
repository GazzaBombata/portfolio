<?php

use App\Filament\Widgets\NutritionToday;
use App\Models\BodyMetric;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'birth_date' => '1990-01-01', 'height_cm' => 180, 'sex' => 'male', 'activity_factor' => 1.4,
    ]);
    $this->actingAs($this->user);
    BodyMetric::create(['measured_on' => now(), 'weight_kg' => 80.0]);
});

it('apre il riquadro', function () {
    Livewire::test(NutritionToday::class)->assertSuccessful();
});

it('confronta il mangiato con la somma dei pasti previsti', function () {
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'dinner', 'description' => 'pesce', 'calories' => 400]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'ossobuco', 'calories' => 500]);

    // 500 su 1000 previste: metà del piano, e va detto in percentuale.
    Livewire::test(NutritionToday::class)->assertSee('50%')->assertSee('1.000');
});

/*
 * Prima di colazione la giornata è a zero, e il riquadro deve dirlo come zero
 * — non come «nessun dato». Sono due cose diverse: la prima è una giornata
 * appena cominciata, la seconda è un tracciamento che non funziona.
 */
it('mostra zero per cento a giornata appena cominciata', function () {
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);

    Livewire::test(NutritionToday::class)->assertSee('0%');
});

it('non inventa un obiettivo quando non c\'è nessun piano', function () {
    Livewire::test(NutritionToday::class)->assertSee('Nessun pasto previsto per oggi');
});

it('avverte quando un pasto previsto non ha le calorie', function () {
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'riso', 'calories' => 600]);
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'dinner', 'description' => 'cena fuori']);

    // L'obiettivo esce più basso del piano vero, quindi la percentuale è più
    // alta del dovuto: senza avviso si legge come margine disponibile.
    Livewire::test(NutritionToday::class)->assertSee('più basso del piano vero');
});

it('mostra lo sforamento invece di fermarsi al bordo', function () {
    Meal::create(['kind' => 'planned', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'riso', 'calories' => 1000]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => now(), 'moment' => 'lunch', 'description' => 'ossobuco', 'calories' => 1400]);

    Livewire::test(NutritionToday::class)
        ->assertSee('140%')
        ->assertSee('400 oltre il piano');
});

it('dice cosa manca invece di stimare un fabbisogno su dati assenti', function () {
    $this->user->update(['birth_date' => null]);

    Livewire::test(NutritionToday::class)->assertSee('Fabbisogno non calcolabile');
});
