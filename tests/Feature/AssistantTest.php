<?php

use App\Ai\ModelCatalog;
use App\Ai\Pricing;
use App\Assistant\ChangesSomething;
use App\Assistant\Runner;
use App\Assistant\Tools\CategoriseTransactionsTool;
use App\Assistant\Tools\LogMealTool;
use App\Assistant\Tools\LogSleepTool;
use App\Assistant\Tools\LogWorkoutTool;
use App\Assistant\Tools\SearchTransactionsTool;
use App\Assistant\Topic;
use App\Filament\Pages\FinanceAssistant;
use App\Filament\Pages\HealthAssistant;
use App\Jobs\RunAssistantTurn;
use App\Models\Account;
use App\Models\AssistantMessage;
use App\Models\Category;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
});

it('apre le due pagine della chat', function () {
    Livewire::test(FinanceAssistant::class)->assertSuccessful();
    Livewire::test(HealthAssistant::class)->assertSuccessful();
});

/*
 * Le due conversazioni non si vedono fra loro: è il senso di averle separate,
 * e se si mescolassero ogni chat si porterebbe dietro il contesto dell'altra —
 * cioè proprio i token che la divisione voleva risparmiare.
 */
it('tiene separate le due conversazioni', function () {
    Queue::fake();

    Livewire::test(FinanceAssistant::class)->set('question', 'quanto ho speso?')->call('send');
    Livewire::test(HealthAssistant::class)->set('question', 'ieri ho corso')->call('send');

    expect(AssistantMessage::where('topic', 'finance')->where('role', 'user')->count())->toBe(1)
        ->and(AssistantMessage::where('topic', 'health')->where('role', 'user')->count())->toBe(1);

    Livewire::test(FinanceAssistant::class)
        ->assertSee('quanto ho speso?')
        ->assertDontSee('ieri ho corso');
});

it('ogni conversazione porta solo i propri strumenti', function () {
    $runner = new Runner('x', 'y');
    $m = (new ReflectionClass($runner))->getMethod('tools');
    $m->setAccessible(true);

    $spese = array_keys($m->invoke($runner, Topic::Finance));
    $salute = array_keys($m->invoke($runner, Topic::Health));

    expect($spese)->toContain('cerca_movimenti')
        ->and($spese)->not->toContain('registra_pasto')
        ->and($salute)->toContain('registra_pasto')
        // Il consulente della salute non può toccare i movimenti bancari.
        ->and($salute)->not->toContain('classifica_movimenti');
});

it('mette in coda il turno e mostra subito la domanda', function () {
    Queue::fake();

    Livewire::test(FinanceAssistant::class)
        ->set('question', 'ieri ho corso 40 minuti')
        ->call('send');

    Queue::assertPushed(RunAssistantTurn::class);

    expect(AssistantMessage::where('role', 'user')->first()->content)->toBe('ieri ho corso 40 minuti')
        // La risposta nasce in attesa: è quella riga a far comparire
        // "sto lavorando" in fondo alla conversazione.
        ->and(AssistantMessage::where('role', 'assistant')->first()->status)->toBe('pending');
});

it('non manda in coda un messaggio vuoto', function () {
    Queue::fake();

    Livewire::test(FinanceAssistant::class)->set('question', '   ')->call('send');

    Queue::assertNothingPushed();
    expect(AssistantMessage::count())->toBe(0);
});

it('registra una notte di sonno', function () {
    $esito = (new LogSleepTool)->run(['notte_del' => '2026-08-20', 'minuti' => 400, 'qualita' => 2]);

    expect($esito->isError)->toBeFalse()
        ->and(SleepLog::sole()->minutes)->toBe(400)
        ->and($esito->content)->toContain('6h 40m');
});

/*
 * Una notte esiste una volta sola: raccontarla due volte deve correggere la
 * prima, non creare una seconda notte che non c'è stata.
 */
it('aggiorna la notte invece di sdoppiarla', function () {
    (new LogSleepTool)->run(['notte_del' => '2026-08-20', 'minuti' => 400]);
    (new LogSleepTool)->run(['notte_del' => '2026-08-20', 'minuti' => 450, 'qualita' => 4]);

    expect(SleepLog::count())->toBe(1)
        ->and(SleepLog::sole()->minutes)->toBe(450)
        ->and(SleepLog::sole()->quality)->toBe(4);
});

it('tiene due allenamenti dello stesso giorno come due allenamenti', function () {
    (new LogWorkoutTool)->run(['giorno' => '2026-08-20', 'attivita' => 'Corsa', 'minuti' => 40]);
    (new LogWorkoutTool)->run(['giorno' => '2026-08-20', 'attivita' => 'Palestra', 'minuti' => 60]);

    expect(Workout::count())->toBe(2);
});

/*
 * Una stima mostrata come un dato certo diventa un dato certo entro due giorni.
 */
it('segna come stimati i valori nutrizionali che non ha pesato', function () {
    (new LogMealTool)->run([
        'giorno' => '2026-08-20', 'momento' => 'lunch',
        'descrizione' => 'Pasta al pomodoro', 'calorie' => 600, 'stimati' => true,
    ]);

    expect(Meal::sole()->nutrition_estimated)->toBeTrue();
});

it('rifiuta una categoria che non esiste invece di sceglierne una simile', function () {
    Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
    $conto = Account::factory()->create();
    $t = Transaction::factory()->create(['account_id' => $conto->id, 'amount' => -30]);

    $esito = (new CategoriseTransactionsTool)->run(['ids' => [$t->id], 'categoria' => 'Alimentari']);

    expect($esito->isError)->toBeTrue()
        ->and($esito->content)->toContain('Supermercato')
        ->and($t->fresh()->category_id)->toBeNull();
});

it('non assegna una categoria di entrata a un\'uscita', function () {
    $entrate = Category::create(['name' => 'Stipendio', 'kind' => 'income']);
    $conto = Account::factory()->create();
    $uscita = Transaction::factory()->create(['account_id' => $conto->id, 'amount' => -30]);

    $esito = (new CategoriseTransactionsTool)->run(['ids' => [$uscita->id], 'categoria' => 'Stipendio']);

    expect($esito->isError)->toBeTrue()
        ->and($uscita->fresh()->category_id)->toBeNull();
});

it('marca come decisa da una persona la categoria assegnata in chat', function () {
    $categoria = Category::create(['name' => 'Supermercato', 'kind' => 'expense']);
    $conto = Account::factory()->create();
    $t = Transaction::factory()->create(['account_id' => $conto->id, 'amount' => -30]);

    (new CategoriseTransactionsTool)->run(['ids' => [$t->id], 'categoria' => 'Supermercato']);

    expect($t->fresh()->category_id)->toBe($categoria->id)
        ->and($t->fresh()->category_locked)->toBeTrue();
});

/*
 * Una risposta costruita su trenta righe spacciate per l'insieme è il modo in
 * cui nasce un numero sbagliato: il limite va dichiarato.
 */
it('dichiara quando i risultati sono più di quelli mostrati', function () {
    $conto = Account::factory()->create();
    Transaction::factory()->count(35)->create(['account_id' => $conto->id, 'description' => 'BAR CENTRALE']);

    $esito = (new SearchTransactionsTool)->run(['testo' => 'BAR']);

    expect($esito->content)->toContain('35')
        ->and($esito->content)->toContain('30 più recenti');
});

it('dichiara quali strumenti scrivono davvero', function () {
    expect(new LogSleepTool)->toBeInstanceOf(ChangesSomething::class)
        ->and(new CategoriseTransactionsTool)->toBeInstanceOf(ChangesSomething::class)
        // Cercare non cambia niente: se risultasse una scrittura, la guardia
        // sulle promesse non mantenute tacerebbe proprio quando serve.
        ->and(new SearchTransactionsTool)->not->toBeInstanceOf(ChangesSomething::class);
});

/*
 * La guardia che rende verificabile un "l'ho registrato".
 *
 * È un controllo su un fatto, non un'ipotesi sull'intenzione: se il testo
 * annuncia una scrittura e in questo turno nessuno strumento che scrive è
 * stato eseguito, l'affermazione è falsa. Il lavoro vero lascia una traccia.
 */
function verifica(string $testo, array $steps): string
{
    $runner = new Runner('chiave-finta', 'modello-finto');
    $registry = (function () use ($runner) {
        $m = (new ReflectionClass($runner))->getMethod('tools');
        $m->setAccessible(true);

        // La guardia vale su entrambe le conversazioni: si prova con tutti gli
        // strumenti insieme.
        return array_merge($m->invoke($runner, Topic::Finance), $m->invoke($runner, Topic::Health));
    })();

    $m = (new ReflectionClass($runner))->getMethod('checked');
    $m->setAccessible(true);

    return $m->invoke($runner, $testo, $steps, $registry);
}

it('avvisa quando dice di aver registrato senza aver registrato', function () {
    $risposta = verifica('Fatto! Ho registrato la tua corsa di ieri.', []);

    expect($risposta)->toContain('non ho registrato niente');
});

it('tace quando la scrittura è davvero avvenuta', function () {
    $risposta = verifica(
        'Fatto! Ho registrato la tua corsa di ieri.',
        [['tool' => 'registra_allenamento', 'summary' => 'Corsa · 20/08']],
    );

    expect($risposta)->not->toContain('non ho registrato niente')
        ->and($risposta)->toBe('Fatto! Ho registrato la tua corsa di ieri.');
});

/*
 * Una versione più larga della regex segnalava anche le risposte che si
 * limitavano a descrivere. Un avviso che grida al lupo su una risposta onesta
 * insegna a saltarlo, e allora non protegge più da niente.
 */
it('non accusa una risposta che si limita a raccontare i dati', function () {
    $risposta = verifica(
        'Questa settimana hai dormito in media 7 ore e ti sei allenato tre volte.',
        [['tool' => 'riepilogo_salute', 'summary' => 'Riepilogo']],
    );

    expect($risposta)->not->toContain('Attenzione');
});

it('non accusa quando ha solo cercato e lo dice', function () {
    $risposta = verifica(
        'Ho trovato 12 movimenti al bar questo mese, per 47 euro in tutto.',
        [['tool' => 'cerca_movimenti', 'summary' => '12 movimenti trovati']],
    );

    expect($risposta)->not->toContain('Attenzione');
});

it('ferma un turno in corso senza cancellare quello che ha già fatto', function () {
    AssistantMessage::create(['role' => 'assistant', 'content' => null, 'status' => 'pending']);

    Livewire::test(FinanceAssistant::class)->call('stop');

    expect(AssistantMessage::sole()->status)->toBe('stopped');
});

/*
 * Il worker non si può uccidere da fuori: sta aspettando la rete. Quello che
 * si può fare è dirgli di non fare il giro successivo, e il canale fra i due
 * processi è la riga sul database.
 */
it('chiude il turno dicendo che è stato fermato', function () {
    $runner = new Runner('chiave-finta', 'claude-opus-5');

    $esito = $runner->run('qualcosa', Topic::Finance, fn (): bool => true);

    expect($esito['stopped'])->toBeTrue()
        ->and($esito['content'])->toContain('Fermato');
});

/*
 * Il menu del modello.
 *
 * Il cancello è il prezzo, non l'elenco: un modello senza prezzo configurato
 * non deve poter essere chiamato nemmeno passando il suo id a mano, perché una
 * chiamata a un modello non prezzato spende soldi che nessun conteggio vede.
 */
it('offre solo i modelli che hanno un prezzo', function () {
    $opzioni = array_keys(Livewire::test(FinanceAssistant::class)->get('modelOptions'));

    expect($opzioni)->toContain('claude-opus-5')
        ->and($opzioni)->toContain('claude-haiku-4-5')
        // La variante datata è lo stesso modello dell'alias: nel menu una volta sola.
        ->and($opzioni)->not->toContain('claude-haiku-4-5-20251001');

    foreach ($opzioni as $id) {
        expect(Pricing::isPriced($id))->toBeTrue();
    }
});

it('parte dal modello predefinito e ricorda quello scelto', function () {
    Queue::fake();

    expect(Livewire::test(HealthAssistant::class)->get('chatModel'))->toBe(config('ai.assistant_model'));

    Livewire::test(HealthAssistant::class)
        ->set('chatModel', 'claude-haiku-4-5')
        ->set('question', 'ieri ho corso')
        ->call('send');

    expect(AssistantMessage::where('role', 'assistant')->latest('id')->value('model'))->toBe('claude-haiku-4-5')
        // Riaprendo la pagina il menu riparte da lì, non dal predefinito.
        ->and(Livewire::test(HealthAssistant::class)->get('chatModel'))->toBe('claude-haiku-4-5');

    Queue::assertPushed(RunAssistantTurn::class, fn ($job): bool => $job->model === 'claude-haiku-4-5');
});

it('ignora un modello non in elenco arrivato dal browser', function () {
    Queue::fake();

    Livewire::test(FinanceAssistant::class)
        ->set('chatModel', 'claude-gratis-inventato')
        ->set('question', 'quanto ho speso?')
        ->call('send');

    // Torna al predefinito invece di scrivere in tabella un id che poi
    // fallirebbe a valle: l'errore si vedrebbe come una risposta rotta.
    Queue::assertPushed(RunAssistantTurn::class, fn ($job): bool => $job->model === config('ai.assistant_model'));
});

it('riconosce la variante datata di un modello', function () {
    expect(ModelCatalog::isKnownAs('claude-haiku-4-5-20251001', ['claude-haiku-4-5']))->toBeTrue()
        ->and(ModelCatalog::isKnownAs('claude-haiku-4-5', ['claude-haiku-4-5-20251001']))->toBeTrue()
        ->and(ModelCatalog::isKnownAs('claude-opus-5', ['claude-haiku-4-5']))->toBeFalse();
});

it('non chiede il catalogo ad Anthropic senza chiave', function () {
    Http::fake();
    config()->set('ai.key', '');

    expect((new ModelCatalog)->names())->toBe([]);

    Http::assertNothingSent();
});

/*
 * Il segnaposto di cache in coda ai risultati degli strumenti.
 *
 * Senza, ogni giro del ciclo ripaga a prezzo pieno tutto quello che il giro
 * prima ha già mandato. È una riga sola e non si vede da nessuna parte se
 * sparisce: si vedrebbe solo in fattura, un mese dopo.
 */
it('sposta il segnaposto di cache invece di accumularne uno per giro', function () {
    $runner = new Runner('x', 'y');
    $m = (new ReflectionClass($runner))->getMethod('withoutStaleBreakpoints');
    $m->setAccessible(true);

    $messaggi = [
        ['role' => 'user', 'content' => 'domanda'],
        ['role' => 'user', 'content' => [
            ['type' => 'tool_result', 'content' => 'a'],
            ['type' => 'tool_result', 'content' => 'b', 'cacheControl' => ['type' => 'ephemeral']],
        ]],
    ];

    $puliti = $m->invoke($runner, $messaggi);

    expect($puliti[1]['content'][1])->not->toHaveKey('cacheControl')
        // Il contenuto non va toccato: si toglie il segnaposto, non il risultato.
        ->and($puliti[1]['content'][1]['content'])->toBe('b')
        ->and($puliti[0]['content'])->toBe('domanda');
});
