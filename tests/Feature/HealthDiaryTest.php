<?php

use App\Filament\Pages\HealthDiary;
use App\Health\Diary;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'app_authentication_secret' => 'PROVA',
        'birth_date' => '1990-01-01',
        'height_cm' => 180,
        'sex' => 'male',
        'activity_factor' => 1.4,
    ]);
    $this->actingAs($this->user);
});

it('apre la pagina del diario', function () {
    Livewire::test(HealthDiary::class)->assertSuccessful();
});

it('fa una riga per ogni giorno dell\'intervallo, dal più vecchio al più recente', function () {
    $righe = Diary::between(
        $this->user,
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-05'),
    );

    expect($righe)->toHaveCount(5)
        ->and(collect($righe)->map(fn (array $r): string => $r['giorno']->toDateString())->all())
        ->toBe(['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05']);
});

/*
 * Un giorno senza niente resta nell'elenco: è un'interruzione, e toglierla di
 * default farebbe sembrare continuo un tracciamento che si è fermato.
 */
it('tiene i giorni vuoti, e li salta solo se glielo chiedi', function () {
    DailyLog::create(['logged_on' => '2026-03-03', 'steps' => 9000]);

    $tutti = Diary::between($this->user, CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-03-05'));
    $pieni = Diary::between($this->user, CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-03-05'), soloConDati: true);

    expect($tutti)->toHaveCount(5)
        ->and(collect($tutti)->where('vuoto', false))->toHaveCount(1)
        ->and($pieni)->toHaveCount(1)
        ->and($pieni[0]['passi'])->toBe(9000);
});

it('mette nella riga tutto quello che quel giorno ha dentro', function () {
    $giorno = '2026-03-10';

    SleepLog::create(['night_of' => $giorno, 'minutes' => 430, 'quality' => 4, 'awakenings' => 1]);
    DailyLog::create(['logged_on' => $giorno, 'steps' => 11000, 'water_litres' => 2.5, 'nutrition_adherence' => 8]);
    BodyMetric::create(['measured_on' => $giorno, 'weight_kg' => 78.4, 'body_fat_pct' => 18.2]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => $giorno, 'moment' => 'lunch', 'description' => 'Pasta al pomodoro', 'calories' => 700]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $giorno, 'moment' => 'lunch', 'description' => 'Riso e pollo', 'calories' => 650]);
    $seduta = Workout::create(['kind' => 'done', 'performed_on' => $giorno, 'activity' => 'Palestra', 'minutes' => 60]);
    $seduta->exercises()->create(['position' => 1, 'name' => 'panca', 'sets' => 4, 'reps' => 8, 'load_kg' => 60]);

    $riga = Diary::between($this->user, CarbonImmutable::parse($giorno), CarbonImmutable::parse($giorno))[0];

    expect($riga['vuoto'])->toBeFalse()
        ->and($riga['sonno']['minuti'])->toBe(430)
        ->and($riga['corpo']['peso'])->toBe(78.4)
        ->and($riga['passi'])->toBe(11000)
        ->and($riga['acqua'])->toBe(2.5)
        ->and($riga['aderenza'])->toBe(8)
        ->and($riga['mangiati']['pranzo'][0]['descrizione'])->toBe('Pasta al pomodoro')
        ->and($riga['previsti'][0]['descrizione'])->toBe('Riso e pollo')
        ->and($riga['fatti'][0]['esercizi'][0])->toBe('panca 4×8 a 60 kg');
});

/*
 * Il mangiato sta in tre colonne, e colazione e spuntini stanno insieme: un
 * «tre mandorle» da solo non dice se quella mattina si è fatta colazione.
 */
it('divide il mangiato in pranzo, cena e il resto', function () {
    $giorno = '2026-03-20';

    foreach ([
        ['breakfast', 'Yogurt e caffè'],
        ['snack', 'Tre mandorle'],
        ['lunch', 'Pasta al pomodoro'],
        ['dinner', 'Pollo e insalata'],
    ] as [$momento, $cosa]) {
        Meal::create(['kind' => 'eaten', 'eaten_on' => $giorno, 'moment' => $momento, 'description' => $cosa]);
    }

    // Un pasto previsto non deve finire in nessuna delle tre: il piano è
    // un'altra colonna, e sommarlo qui conterebbe due volte la giornata.
    Meal::create(['kind' => 'planned', 'eaten_on' => $giorno, 'moment' => 'lunch', 'description' => 'Riso e pollo']);

    $mangiati = Diary::between($this->user, CarbonImmutable::parse($giorno), CarbonImmutable::parse($giorno))[0]['mangiati'];

    expect(array_column($mangiati['colazione'], 'descrizione'))->toBe(['Yogurt e caffè', 'Tre mandorle'])
        ->and(array_column($mangiati['pranzo'], 'descrizione'))->toBe(['Pasta al pomodoro'])
        ->and(array_column($mangiati['cena'], 'descrizione'))->toBe(['Pollo e insalata']);
});

it('tiene i tre gruppi anche quando sono vuoti', function () {
    $mangiati = Diary::between($this->user, CarbonImmutable::parse('2026-03-21'), CarbonImmutable::parse('2026-03-21'))[0]['mangiati'];

    expect($mangiati)->toBe(['colazione' => [], 'pranzo' => [], 'cena' => []]);
});

/*
 * Le due colonne che è facile confondere: l'obiettivo è quanto avevo deciso di
 * mangiare (la somma dei pasti previsti), il fabbisogno è quanto ho bruciato.
 * E il previsto non conta come mangiato.
 */
it('tiene separati mangiato, obiettivo e fabbisogno', function () {
    $giorno = '2026-03-11';

    BodyMetric::create(['measured_on' => $giorno, 'weight_kg' => 80]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => $giorno, 'moment' => 'lunch', 'description' => 'Pranzo', 'calories' => 600]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $giorno, 'moment' => 'lunch', 'description' => 'Pranzo previsto', 'calories' => 900]);
    Meal::create(['kind' => 'planned', 'eaten_on' => $giorno, 'moment' => 'dinner', 'description' => 'Cena prevista', 'calories' => 700]);

    $calorie = Diary::between($this->user, CarbonImmutable::parse($giorno), CarbonImmutable::parse($giorno))[0]['calorie'];

    expect($calorie['mangiate'])->toBe(600)
        ->and($calorie['obiettivo'])->toBe(1600)
        ->and($calorie['fabbisogno'])->toBeGreaterThan(1600)
        ->and($calorie['bilancio'])->toBe(600 - $calorie['fabbisogno']);
});

/*
 * Un pasto senza calorie sta nell'elenco ma non nella somma: se la tabella non
 * lo dice, mostra un totale più basso del vero e nessuno se ne accorge.
 */
it('segnala quello che vale zero pur essendo registrato', function () {
    $giorno = '2026-03-12';

    Meal::create(['kind' => 'eaten', 'eaten_on' => $giorno, 'moment' => 'dinner', 'description' => 'Cena fuori']);
    Workout::create(['kind' => 'done', 'performed_on' => $giorno, 'activity' => 'Palestra']);

    $avvisi = Diary::between($this->user, CarbonImmutable::parse($giorno), CarbonImmutable::parse($giorno))[0]['avvisi'];

    expect(implode(' | ', $avvisi))
        ->toContain('1 pasto mangiato senza calorie')
        ->toContain('Palestra: senza durata');
});

it('scarica un PDF', function () {
    DailyLog::create(['logged_on' => '2026-03-13', 'steps' => 9000]);
    Meal::create(['kind' => 'eaten', 'eaten_on' => '2026-03-13', 'moment' => 'lunch', 'description' => 'Pasta', 'calories' => 700]);

    Livewire::test(HealthDiary::class)
        ->fillForm(['dal' => '2026-03-12', 'al' => '2026-03-14'])
        ->call('scarica')
        ->assertFileDownloaded('diario-2026-03-12_2026-03-14.pdf');
});

it('non scarica niente se l\'intervallo è al contrario', function () {
    Livewire::test(HealthDiary::class)
        ->fillForm(['dal' => '2026-03-14', 'al' => '2026-03-12'])
        ->call('scarica')
        ->assertNotified();
});
