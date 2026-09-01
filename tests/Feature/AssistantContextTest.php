<?php

use App\Assistant\Runner;
use App\Assistant\Topic;
use App\Filament\Pages\AssistantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Giorgio Giotto',
        'app_authentication_secret' => encrypt('ABCDEFGHIJKLMNOP'),
        'assistant_notes' => 'Sveglia alle 6, risposte brevi.',
        'health_notes' => 'Obiettivo 78 kg. In garage bilanciere e cyclette.',
        'finance_notes' => 'Stipendio il 27, obiettivo 500 € al mese da parte.',
    ]);
    $this->actingAs($this->user);
});

function blocchi(Topic $topic): array
{
    $runner = app(Runner::class);
    $m = (new ReflectionClass($runner))->getMethod('systemBlocks');

    return $m->invoke($runner, $topic);
}

/*
 * Il punto di tutta la modifica: il prompt in cache non deve contenere niente
 * che valga per una persona sola. Se ci finisce dentro un nome, al secondo
 * utente o si scrive un secondo prompt — cioè ogni regola di dominio tenuta
 * allineata in due copie — oppure il secondo si prende addosso il profilo del
 * primo.
 */
it('non nomina nessuno nel prompt che sta in cache', function (Topic $topic) {
    $statico = blocchi($topic)[0];

    expect($statico)->toHaveKey('cacheControl')
        ->and($statico['text'])->not->toContain('Giorgio');
})->with([[Topic::Health], [Topic::Finance]]);

it('mette il nome e il contesto nel blocco variabile', function () {
    $variabile = blocchi(Topic::Health)[1];

    expect($variabile)->not->toHaveKey('cacheControl')
        ->and($variabile['text'])->toContain('Stai parlando con Giorgio.')
        ->toContain('Sveglia alle 6')
        ->toContain('Obiettivo 78 kg');
});

/*
 * Le due conversazioni non si vedono fra loro, ed è la stessa riga che vale
 * per gli strumenti: un consulente che non può toccare i pasti non ha motivo
 * di sapere quanto pesi, e uno che non può toccare i movimenti non ha motivo
 * di sapere quanto guadagni. Mescolarli qui annullerebbe la divisione e la
 * farebbe pagare in token a ogni domanda.
 */
it('non passa il contesto di una chat all\'altra', function () {
    $salute = blocchi(Topic::Health)[1]['text'];
    $spese = blocchi(Topic::Finance)[1]['text'];

    expect($salute)->toContain('Obiettivo 78 kg')->not->toContain('Stipendio il 27')
        ->and($spese)->toContain('Stipendio il 27')->not->toContain('Obiettivo 78 kg');
});

/* Il generale invece lo leggono tutti e due. */
it('dà a tutti e due il contesto generale', function () {
    expect(blocchi(Topic::Health)[1]['text'])->toContain('Sveglia alle 6')
        ->and(blocchi(Topic::Finance)[1]['text'])->toContain('Sveglia alle 6');
});

/* Il consulente delle spese non ha motivo di sapere quanto pesi. */
it('non manda i dati fisici alla chat delle spese', function () {
    $this->user->forceFill(['birth_date' => '1990-01-01', 'height_cm' => 180, 'sex' => 'male'])->save();

    expect(blocchi(Topic::Finance)[1]['text'])->not->toContain('anni')->not->toContain('alto');
});

it('non si scompone se il contesto è vuoto', function () {
    $this->user->forceFill(['assistant_notes' => null, 'health_notes' => null, 'finance_notes' => null])->save();

    expect(blocchi(Topic::Health)[1]['text'])->toContain('Stai parlando con Giorgio.')
        ->not->toContain('Contesto che');
});

/* Il confine di sempre: il contesto di una persona non arriva all'altra. */
it('dà a ciascuno il proprio contesto', function () {
    $altra = User::factory()->create(['name' => 'Anna', 'health_notes' => 'Corro tre volte a settimana.']);
    $this->actingAs($altra);

    $variabile = blocchi(Topic::Health)[1]['text'];

    expect($variabile)->toContain('Stai parlando con Anna.')
        ->toContain('Corro tre volte a settimana')
        ->not->toContain('Obiettivo 78 kg');
});

it('salva il contesto dalla pagina', function () {
    Livewire::test(AssistantContext::class)
        ->fillForm([
            'assistant_notes' => 'Niente giri di parole.',
            'health_notes' => 'Spalla destra da rispettare.',
            'finance_notes' => 'Partita IVA saltuaria.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->user->fresh()->assistant_notes)->toBe('Niente giri di parole.')
        ->and($this->user->fresh()->health_notes)->toBe('Spalla destra da rispettare.')
        ->and($this->user->fresh()->finance_notes)->toBe('Partita IVA saltuaria.');
});

/*
 * Un tetto perché ogni carattere sta nel blocco variabile: non entra in cache
 * e viene rispedito per intero a ogni domanda. Un incollaggio di dieci pagine
 * si sentirebbe sul conto senza che nessuno colleghi le due cose.
 */
it('non lascia incollare dieci pagine', function () {
    Livewire::test(AssistantContext::class)
        ->fillForm(['health_notes' => str_repeat('a', AssistantContext::MAX + 1)])
        ->call('save')
        ->assertHasFormErrors(['health_notes']);
});

it('apre la pagina', function () {
    $this->get('/admin/assistant-context')->assertOk();
});
