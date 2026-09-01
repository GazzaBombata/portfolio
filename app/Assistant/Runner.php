<?php

namespace App\Assistant;

use Anthropic\Client;
use App\Ai\Budget;
use App\Ai\Pricing;
use App\Health\Energy;
use App\Health\Gaps;
use App\Models\AssistantMessage;
use App\Models\BodyMetric;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Un turno dell'assistente: costruisce il prompt fidato e il set di strumenti,
 * poi chiama il modello, esegue quello che chiede e gli ripassa i risultati,
 * finché non produce una risposta. Limitato nel numero di giri per non spendere
 * all'infinito su una richiesta che non converge.
 */
class Runner
{
    /** Tetto ai giri di strumenti per turno. */
    private const MAX_ROUNDS = 6;

    /**
     * L'assistente che dice, in prima persona, di aver appena registrato o
     * cambiato qualcosa.
     *
     * Volutamente stretta: una versione più larga segnalava anche le risposte
     * che si limitavano a DESCRIVERE ("hai dormito sette ore"), e un avviso che
     * grida al lupo su una risposta onesta insegna a saltarlo.
     */
    private const CLAIMS_A_WRITE = '/\b(?:l\'|le |li |ti )?ho\s+(?:registrat|segnat|salvat|aggiornat|classificat|inserit|messo)|\becco\s+fatto\b|^\s*fatto\s*[!:.]/iu';

    public function __construct(private readonly string $apiKey, private readonly string $model) {}

    /**
     * @return array{content: string, steps: array<int, array{tool: string, summary: ?string}>}
     */
    /**
     * @param  (callable(): bool)|null  $shouldStop  chiamata fra un giro e
     *                                               l'altro: se torna true, il
     *                                               turno si chiude con quello
     *                                               che ha raccolto.
     * @param  string|null  $model  scelto dalla chat; se non arriva vale quello
     *                              di configurazione. Passa comunque da
     *                              `ensurePriced`, quindi un id arrivato dal
     *                              browser non può far spendere niente.
     * @return array{content: string, steps: array<int, array{tool: string, summary: ?string}>, stopped?: bool}
     */
    public function run(string $question, Topic $topic = Topic::Finance, ?callable $shouldStop = null, ?string $model = null): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY mancante: impostala per usare l\'assistente.');
        }

        $model = $model !== null && $model !== '' ? $model : $this->model;

        Pricing::ensurePriced($model);

        $client = new Client(apiKey: $this->apiKey);
        $registry = $this->tools($topic);
        $schemas = $this->schemas($registry);
        $messages = $this->history($question, $topic);
        $steps = [];

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            /*
             * Fermato da chi guarda.
             *
             * Il controllo sta qui e non dentro la chiamata perché è lì che il
             * turno è interrompibile senza lasciare le cose a metà: gli
             * strumenti di questo giro sono stati eseguiti fino in fondo, e
             * quello che hanno scritto resta scritto — dirlo è meglio che
             * fingere che il turno non sia mai esistito.
             */
            if ($shouldStop !== null && $shouldStop()) {
                return [
                    'content' => $steps === []
                        ? 'Fermato prima di fare qualsiasi cosa.'
                        : 'Fermato. Quello che avevo già eseguito è stato fatto — lo vedi qui sopra.',
                    'steps' => $steps,
                    'stopped' => true,
                ];
            }

            // Controllato a ogni giro e non solo all'inizio: un turno con
            // molti passaggi può sfondare il tetto da solo.
            Budget::guard();

            $response = $client->messages->create(
                model: $model,
                maxTokens: 8192,
                system: $this->systemBlocks($topic),
                messages: $messages,
                tools: $schemas,
            );

            Budget::record('assistente', $model, $response->usage);

            if ($response->stopReason === 'refusal') {
                return ['content' => 'Non me la sento di rispondere a questa richiesta.', 'steps' => $steps];
            }

            /*
             * Tagliata a metà dal limite di token: gli strumenti NON vengono
             * eseguiti. Gli argomenti di una chiamata sono generati come
             * output, quindi un troncamento può produrre una registrazione
             * incompleta — un pasto senza descrizione, un peso a metà — che poi
             * resta lì come se fosse un dato.
             */
            if ($response->stopReason === 'max_tokens') {
                return [
                    'content' => 'La risposta era troppo lunga ed è stata tagliata. Mi fermo per non salvare dati a metà: prova a chiedermi una cosa per volta.',
                    'steps' => $steps,
                ];
            }

            $testo = '';
            $chiamate = [];

            foreach ($response->content as $block) {
                if ($block->type === 'text') {
                    $testo .= $block->text;
                } elseif ($block->type === 'tool_use') {
                    $chiamate[] = ['id' => $block->id, 'name' => $block->name, 'input' => (array) $block->input];
                }
            }

            if ($chiamate === []) {
                return ['content' => $this->checked(trim($testo), $steps, $registry), 'steps' => $steps];
            }

            $messages[] = ['role' => 'assistant', 'content' => $response->content];
            $risultati = [];

            foreach ($chiamate as $chiamata) {
                $tool = $registry[$chiamata['name']] ?? null;

                if ($tool === null) {
                    $esito = ToolResult::error('Strumento sconosciuto: '.$chiamata['name']);
                } else {
                    try {
                        $esito = $tool->run($chiamata['input']);
                    } catch (Throwable $e) {
                        $esito = ToolResult::error('Errore nello strumento: '.$e->getMessage());
                    }
                }

                $risultati[] = [
                    'type' => 'tool_result',
                    'toolUseID' => $chiamata['id'],
                    'content' => $esito->content,
                    'isError' => $esito->isError,
                ];

                $steps[] = ['tool' => $chiamata['name'], 'summary' => $esito->summary];
            }

            /*
             * Un segnaposto di cache in coda ai risultati.
             *
             * Il giro successivo rispedisce TUTTO quello che c'è stato finora —
             * prompt, domanda, richieste di strumenti, risultati — e senza
             * questo lo ripagherebbe intero a ogni giro. Sui dati veri il
             * secondo giro rimandava tremila token già mandati un secondo
             * prima. Marcato l'ultimo blocco, quello che sta davanti si rilegge
             * a un decimo.
             *
             * Il segnaposto si sposta: Anthropic ne accetta pochi, e uno per
             * giro li esaurirebbe in un turno un po' lungo.
             */
            $risultati[count($risultati) - 1]['cacheControl'] = ['type' => 'ephemeral'];
            $messages = $this->withoutStaleBreakpoints($messages);

            $messages[] = ['role' => 'user', 'content' => $risultati];
        }

        return [
            'content' => 'Ho fatto parecchi passaggi senza arrivare in fondo. Ecco cosa ho raccolto: dimmi tu come procedere.',
            'steps' => $steps,
        ];
    }

    /**
     * Toglie i segnaposto di cache lasciati dai giri precedenti.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function withoutStaleBreakpoints(array $messages): array
    {
        foreach ($messages as $i => $messaggio) {
            if (! is_array($messaggio['content'] ?? null)) {
                continue;
            }

            foreach ($messaggio['content'] as $j => $blocco) {
                if (is_array($blocco) && isset($blocco['cacheControl'])) {
                    unset($messages[$i]['content'][$j]['cacheControl']);
                }
            }
        }

        return $messages;
    }

    /**
     * Gli strumenti di questa conversazione, e nessun altro.
     *
     * @return array<string, Tool>
     */
    private function tools(Topic $topic): array
    {
        return collect($topic->tools())->keyBy(fn (Tool $t): string => $t->name())->all();
    }

    /**
     * @param  array<string, Tool>  $registry
     * @return array<int, array<string, mixed>>
     */
    private function schemas(array $registry): array
    {
        return collect($registry)->map(fn (Tool $t): array => [
            'name' => $t->name(),
            'description' => $t->description(),
            'inputSchema' => $t->schema(),
        ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function history(string $question, Topic $topic): array
    {
        $precedenti = AssistantMessage::query()
            ->where('topic', $topic->value)
            ->where('status', 'done')
            ->orderByDesc('id')
            // Gli ultimi scambi bastano a tenere il filo; l'intera storia
            // crescerebbe a ogni turno e verrebbe pagata di nuovo ogni volta.
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $messages = $precedenti
            ->filter(fn (AssistantMessage $m): bool => filled($m->content))
            ->map(fn (AssistantMessage $m): array => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        /*
         * La domanda di questo turno è GIÀ una riga in tabella: la pagina la
         * scrive prima di mettere il lavoro in coda, ed è a stato «done»,
         * quindi la storia qui sopra la comprende.
         *
         * Senza questo controllo finiva nel prompt due volte — una dalla
         * storia e una appesa in fondo — e il modello si trovava davanti due
         * messaggi dell'utente identici di fila. Il 26/08 se n'è accorto e
         * l'ha scritto in chat ("la tua chat mi è arrivata due volte"); prima
         * di allora era passata inosservata, che è la parte peggiore: un
         * doppione così non rompe niente, sposta solo le probabilità verso il
         * fare la cosa due volte.
         *
         * Resta appesa quando non c'è (chiamate diverse da quella della
         * pagina, per esempio dai test), così `run()` funziona da sola.
         */
        $ultimo = end($messages) ?: null;

        if ($ultimo === null || $ultimo['role'] !== 'user' || $ultimo['content'] !== $question) {
            $messages[] = ['role' => 'user', 'content' => $question];
        }

        return $messages;
    }

    /**
     * I dati fisici di chi sta scrivendo.
     *
     * Ricavati dal profilo a ogni turno, mai scritti nel prompt: un'età messa
     * a mano è giusta il giorno in cui la scrivi, e il peso cambia. Così fra
     * dieci anni la frase è ancora vera senza che nessuno l'abbia aggiornata.
     *
     * Va solo nella chat salute. Il consulente delle spese non ha motivo di
     * sapere quanto pesi: è la stessa riga che separa le due conversazioni, e
     * qui costerebbe anche dei token a ogni domanda.
     */
    private function profilo(): string
    {
        $user = Auth::user();

        if ($user === null) {
            return '';
        }

        $parti = [];

        if (($eta = Energy::age($user)) !== null) {
            $parti[] = "{$eta} anni";
        }

        if ($user->height_cm !== null) {
            $parti[] = 'alto '.number_format($user->height_cm / 100, 2, ',', '').' m';
        }

        $peso = BodyMetric::query()->whereNotNull('weight_kg')->orderByDesc('measured_on')->first();

        if ($peso !== null) {
            $parti[] = 'ultimo peso registrato '.number_format((float) $peso->weight_kg, 1, ',', '')
                .' kg il '.$peso->measured_on->format('d/m/Y');
        }

        if (($basale = Energy::basalRate($user)) !== null) {
            $parti[] = "metabolismo basale stimato {$basale} kcal";
        }

        return $parti === [] ? '' : 'Dati fisici: '.implode(', ', $parti).'.';
    }

    /**
     * Come chiamare chi sta scrivendo.
     *
     * Il prompt è impersonale, quindi il nome arriva da qui. Senza, il modello
     * si rivolge a «l'utente», che è il tono di un modulo e non di qualcuno
     * che ti allena.
     */
    private static function nome(User $user): string
    {
        return Str::of((string) $user->name)->trim()->explode(' ')->first() ?: 'La persona';
    }

    /**
     * Il contesto che la persona ha scritto di suo pugno per gli assistenti.
     *
     * Sono istruzioni sue, non dati letti da uno strumento: gli obiettivi di
     * peso, gli attrezzi che ha in casa, com'è fatta la sua entrata. È la
     * ragione per cui il prompt può restare impersonale — le cose che valgono
     * per una persona sola stanno nella sua riga, non nel codice.
     *
     * Quello generale va a tutti e due; gli altri due restano ciascuno nella
     * propria conversazione, che è la stessa divisione che vale per gli
     * strumenti.
     *
     * @return array<int, string>
     */
    private function contestoPersonale(Topic $topic): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $righe = [];

        if (filled($user->assistant_notes)) {
            $righe[] = 'Contesto che '.static::nome($user).' ha scritto per te: '.trim($user->assistant_notes);
        }

        $specifico = match ($topic) {
            Topic::Health => $user->health_notes,
            Topic::Finance => $user->finance_notes,
        };

        if (filled($specifico)) {
            $righe[] = match ($topic) {
                Topic::Health => 'Su salute e allenamento: '.trim($specifico),
                Topic::Finance => 'Sulle sue finanze: '.trim($specifico),
            };
        }

        return $righe;
    }

    /**
     * Il prompt in due blocchi, e l'ordine non è estetico.
     *
     * Il primo — regole e ruolo — non cambia mai, e insieme alle definizioni
     * degli strumenti forma il prefisso che viene messo in cache: rileggerlo
     * costa un decimo di rimandarlo. Il secondo porta quello che cambia (la
     * data, il peso, l'elenco delle categorie) e sta DOPO, perché un solo
     * carattere diverso prima del punto di cache la annulla tutta.
     *
     * @return array<int, array<string, mixed>>
     */
    private function systemBlocks(Topic $topic): array
    {
        return [
            [
                'type' => 'text',
                'text' => $topic->staticPrompt(),
                'cacheControl' => ['type' => 'ephemeral'],
            ],
            [
                'type' => 'text',
                'text' => $this->contesto($topic),
            ],
        ];
    }

    /** Quello che cambia da un giorno all'altro, tenuto fuori dalla cache. */
    private function contesto(Topic $topic): string
    {
        $user = Auth::user();

        $righe = ['Oggi è '.now()->translatedFormat('l j F Y').'.'];

        if ($user !== null) {
            $righe[] = 'Stai parlando con '.static::nome($user).'.';
        }

        // I dati fisici solo dove servono: alle spese non interessa il peso.
        if ($topic === Topic::Health && ($profilo = $this->profilo()) !== '') {
            $righe[] = $profilo;
        }

        $righe = array_merge($righe, $this->contestoPersonale($topic));

        if ($topic === Topic::Finance) {
            $righe[] = 'Categorie disponibili: '
                .Category::query()->orderBy('name')->pluck('name')->implode(', ');
        }

        /*
         * I buchi di oggi e domani, contati qui e non dedotti dal modello.
         *
         * Un promemoria si legge finché è vero: uno inventato — o uno che
         * chiede quello che è già stato registrato — insegna a saltarli tutti,
         * e allora tanto valeva non averli. Sta nel blocco variabile perché
         * cambia a ogni registrazione: davanti al punto di cache la
         * annullerebbe a ogni turno.
         */
        if ($topic === Topic::Health) {
            $righe[] = Gaps::line(CarbonImmutable::now());
        }

        return implode("\n", $righe);
    }

    /**
     * Intercetta la risposta che annuncia una scrittura mai avvenuta.
     *
     * È un controllo su un fatto, non un'ipotesi sull'intenzione: se il testo
     * dice "l'ho registrato" e in questo turno non è stato eseguito nessuno
     * strumento che scrive, l'affermazione è falsa. Il lavoro vero lascia
     * sempre una traccia.
     *
     * @param  array<int, array{tool: string, summary: ?string}>  $steps
     * @param  array<string, Tool>  $registry
     */
    private function checked(string $content, array $steps, array $registry): string
    {
        if ($this->somethingWasWritten($steps, $registry)) {
            return $content;
        }

        if (preg_match(self::CLAIMS_A_WRITE, $content) !== 1) {
            return $content;
        }

        return '⚠️ **Attenzione: non ho registrato niente.** Qui sotto scrivo di averlo fatto, ma non ho eseguito nessuna operazione. '
            ."Chiedimelo di nuovo, così lo faccio davvero.\n\n---\n\n".$content;
    }

    /**
     * @param  array<int, array{tool: string, summary: ?string}>  $steps
     * @param  array<string, Tool>  $registry
     */
    private function somethingWasWritten(array $steps, array $registry): bool
    {
        foreach ($steps as $step) {
            $tool = $registry[$step['tool']] ?? null;

            // Uno strumento che non risulta al registro conta come scrittura:
            // è il verso giusto in cui sbagliare. Tacere davanti a un "fatto!"
            // falso costa una registrazione da rifare; accusare un "fatto!"
            // vero costa la fiducia nell'avviso, e allora non lo legge più
            // nessuno.
            if ($tool === null || $tool instanceof ChangesSomething) {
                return true;
            }
        }

        return false;
    }
}
