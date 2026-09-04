<?php

use App\Assistant\Tools\BodyMetricHistoryTool;
use App\Models\BodyMetric;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->oggi = CarbonImmutable::parse('2026-09-04');
});

function pesa(CarbonImmutable $giorno, float $kg): BodyMetric
{
    return BodyMetric::create(['measured_on' => $giorno, 'weight_kg' => $kg]);
}

it('dà il peso di un giorno preciso', function () {
    pesa($this->oggi, 81.4);

    expect((new BodyMetricHistoryTool)->run(['giorno' => $this->oggi->toDateString()])->content)
        ->toContain('04/09/2026')->toContain('81,4 kg');
});

/*
 * Il caso che conta: non ci si pesa tutti i giorni. La risposta onesta non è
 * «non lo so» e non è un numero interpolato — sono le due misurazioni intorno,
 * con la distanza, e chi legge decide.
 */
it('sul giorno senza misurazione dà le due più vicine e vieta di interpolare', function () {
    pesa($this->oggi->subDays(5), 82.0);
    pesa($this->oggi->addDays(3), 81.0);

    $esito = (new BodyMetricHistoryTool)->run(['giorno' => $this->oggi->toDateString()]);

    expect($esito->content)->toContain('non c\'è nessuna misurazione')
        ->toContain('5 giorni prima')
        ->toContain('3 giorni dopo')
        ->toContain('Non stimare un valore intermedio');
});

/*
 * La media è il numero che riepilogo_salute non sapeva dare: da 82 a 80 kg non
 * dice niente su cosa c'è stato in mezzo.
 */
it('calcola media, minimo, massimo e variazione di un periodo', function () {
    pesa($this->oggi->subDays(20), 82.0);
    pesa($this->oggi->subDays(10), 84.0);
    pesa($this->oggi->subDays(2), 80.0);

    $esito = (new BodyMetricHistoryTool)->run([
        'dal' => $this->oggi->subDays(30)->toDateString(),
        'al' => $this->oggi->toDateString(),
    ]);

    expect($esito->content)->toContain('3 misurazioni')
        ->toContain('Media: 82,0 kg')
        ->toContain('Minimo: 80,0 kg')
        ->toContain('Massimo: 84,0 kg')
        ->toContain('Variazione: -2,0 kg');
});

/* L'elenco è completo: un campione produrrebbe una tendenza che non esiste. */
it('elenca tutte le misurazioni, non un campione', function () {
    foreach (range(1, 12) as $i) {
        pesa($this->oggi->subDays($i), 80 + $i / 10);
    }

    $esito = (new BodyMetricHistoryTool)->run([
        'dal' => $this->oggi->subDays(30)->toDateString(),
        'al' => $this->oggi->toDateString(),
    ]);

    expect($esito->content)->toContain('12 misurazioni')
        ->and(substr_count($esito->content, ' · '))->toBeGreaterThanOrEqual(11);
});

it('dice chiaramente quando non c\'è niente', function () {
    expect((new BodyMetricHistoryTool)->run(['giorno' => $this->oggi->toDateString()])->content)
        ->toContain('Nessuna misurazione del peso registrata');
});

/* Il confine di sempre. */
it('non mostra il peso di un\'altra persona', function () {
    pesa($this->oggi, 81.4);

    $this->actingAs(User::factory()->create());

    expect((new BodyMetricHistoryTool)->run(['giorno' => $this->oggi->toDateString()])->content)
        ->toContain('Nessuna misurazione');
});
