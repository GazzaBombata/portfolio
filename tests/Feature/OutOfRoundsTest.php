<?php

use App\Assistant\ResumeNotes;
use App\Assistant\Runner;
use App\Assistant\Topic;
use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->runner = new Runner('chiave-finta', 'claude-opus-5');
});

function metodo(string $nome): ReflectionMethod
{
    return (new ReflectionClass(Runner::class))->getMethod($nome);
}

/*
 * Il bug: la risposta di fine giri diceva «ecco cosa ho raccolto» e poi non
 * raccontava niente. I risultati degli strumenti erano tutti in memoria, ma
 * nessuno li faceva leggere al modello, quindi la promessa non era mantenuta
 * da nessuna parte — sopra restavano solo le pastiglie con i nomi degli
 * strumenti, che dicono cosa ha GUARDATO e non cosa ha TROVATO.
 */
it('chiede il resoconto di quello che ha trovato, non dei passaggi fatti', function () {
    $messaggi = metodo('messaggiDiChiusura')->invoke($this->runner, [
        ['role' => 'user', 'content' => 'quanto ho speso ad agosto?'],
    ]);

    $chiusura = end($messaggi);

    expect($chiusura['role'])->toBe('user')
        ->and($chiusura['content'])->toContain('TROVATO')
        ->toContain('non elencare gli strumenti')
        // Le altre due richieste: cosa manca, e il permesso di proseguire.
        ->toContain('non sei riuscito a controllare')
        ->toContain('da dove riprenderesti');
});

/* Il testo del modello vince sulla frase fissa. */
it('restituisce quello che il modello ha scritto', function () {
    $esito = metodo('esitoResoconto')->invoke($this->runner, 'Ad agosto risultano 1.240 € su 87 movimenti.', [], []);

    expect($esito['content'])->toBe('Ad agosto risultano 1.240 € su 87 movimenti.');
});

/*
 * Se anche il resoconto fallisce — tetto di spesa, rete, API — sparire è la
 * cosa peggiore: quello che gli strumenti hanno scritto resta scritto, e va
 * detto.
 */
it('dice cosa ha eseguito anche quando il resoconto non riesce', function () {
    $passi = [['tool' => 'registra_pasto', 'summary' => 'pranzo · 28/08']];

    $esito = metodo('esitoResoconto')->invoke($this->runner, '', $passi, []);

    expect($esito['content'])->toContain('non sono riuscito nemmeno a riassumerteli')
        ->toContain('resta scritto')
        ->and($esito['steps'])->toBe($passi);
});

it('non promette registrazioni quando non ne ha fatte', function () {
    $esito = metodo('esitoResoconto')->invoke($this->runner, '', [], []);

    expect($esito['content'])->toContain('Non avevo ancora eseguito niente.');
});

/*
 * Da quando la frase la scrive il modello, dal testo non si riconoscono più i
 * turni finiti contro il tetto — ed è il numero che dice se sei giri bastano.
 */
it('segna in tabella i turni finiti contro il tetto', function () {
    $m = AssistantMessage::create(['topic' => 'health', 'role' => 'assistant', 'content' => 'x', 'status' => 'done']);

    // Il valore predefinito lo mette il database, quindi si legge rileggendo.
    expect($m->fresh()->out_of_rounds)->toBeFalse();

    $m->update(['out_of_rounds' => true]);

    expect($m->fresh()->out_of_rounds)->toBeTrue();
});

/* La ripresa */

it('riconosce un sì e non una domanda nuova', function (string $testo, bool $atteso) {
    expect(ResumeNotes::soundsLikeYes($testo))->toBe($atteso);
})->with([
    ['sì', true],
    ['si', true],
    ['ok', true],
    ['ok vai', true],
    ['continua pure', true],
    ['Sì, grazie.', true],
    ['procedi', true],
    // Una domanda nuova, anche se comincia per «sì»: riprendere qui vorrebbe
    // dire rispondere a qualcosa che non è stato chiesto.
    ['sì ma guarda solo agosto', false],
    ['ok allora dimmi quanto ho speso al bar', false],
    ['quanto ho speso?', false],
    ['no', false],
    ['', false],
]);

it('riparte da dove si era rimasti invece di rifare le ricerche', function () {
    $memoria = [
        ['role' => 'user', 'content' => 'quanto ho speso ad agosto?'],
        ['role' => 'assistant', 'content' => 'Ecco i movimenti che ho letto.'],
    ];

    ResumeNotes::remember(Topic::Finance, $memoria, [['tool' => 'cerca_movimenti', 'summary' => '87 righe']]);

    $ripresa = ResumeNotes::take(Topic::Finance);

    expect($ripresa['messages'])->toBe($memoria)
        ->and($ripresa['steps'][0]['tool'])->toBe('cerca_movimenti');
});

/* Un «sì» detto due volte non deve ripartire due volte dallo stesso punto. */
it('consuma gli appunti leggendoli', function () {
    ResumeNotes::remember(Topic::Finance, [['role' => 'user', 'content' => 'x']], []);

    expect(ResumeNotes::take(Topic::Finance))->not->toBeNull()
        ->and(ResumeNotes::take(Topic::Finance))->toBeNull();
});

/* Una catena infinita di «continua» spenderebbe senza che nessuno decida. */
it('non si fa riprendere all\'infinito', function () {
    ResumeNotes::remember(Topic::Finance, [['role' => 'user', 'content' => 'x']], [], ResumeNotes::MAX_RESUMES);

    expect(ResumeNotes::take(Topic::Finance))->toBeNull();
});

/* Meglio nessuna ripresa che una voce di cache enorme per ogni turno lungo. */
it('non salva appunti sterminati', function () {
    ResumeNotes::remember(Topic::Finance, [['role' => 'user', 'content' => str_repeat('a', ResumeNotes::MAX_CHARS + 1)]], []);

    expect(ResumeNotes::take(Topic::Finance))->toBeNull();
});

/* Le due conversazioni non si vedono fra loro, nemmeno negli appunti. */
it('non riprende la ricerca dell\'altra chat', function () {
    ResumeNotes::remember(Topic::Finance, [['role' => 'user', 'content' => 'spese']], []);

    expect(ResumeNotes::take(Topic::Health))->toBeNull()
        ->and(ResumeNotes::take(Topic::Finance))->not->toBeNull();
});

/* E nemmeno fra due persone. */
it('non riprende la ricerca di un\'altra persona', function () {
    ResumeNotes::remember(Topic::Finance, [['role' => 'user', 'content' => 'spese']], []);

    $this->actingAs(User::factory()->create());

    expect(ResumeNotes::take(Topic::Finance))->toBeNull();
});
