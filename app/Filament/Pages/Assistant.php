<?php

namespace App\Filament\Pages;

use App\Ai\Budget;
use App\Ai\ModelCatalog;
use App\Ai\Pricing;
use App\Assistant\Topic;
use App\Jobs\RunAssistantTurn;
use App\Models\AssistantMessage;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * La chat con l'assistente.
 *
 * Serve a registrare quello che è successo scrivendolo come lo si direbbe a
 * voce — "ieri ho corso quaranta minuti, dormito male" — invece di aprire tre
 * schermate e compilare tre moduli. È lo stesso lavoro, ma fatto nel momento in
 * cui uno ci pensa, che è l'unico in cui viene fatto davvero.
 */
abstract class Assistant extends Page
{
    protected string $view = 'filament.pages.assistant';

    /** Di cosa parla questa conversazione. */
    abstract public function topic(): Topic;

    public string $question = '';

    /** Il modello di questa conversazione: si cambia dal menu in alto. */
    public ?string $chatModel = null;

    public function mount(): void
    {
        // Riparte dall'ultima risposta di QUESTA chat: le due conversazioni
        // possono ragionevolmente girare su modelli diversi — le spese sono
        // ricerche, la salute è ragionamento — e sarebbe fastidioso doverlo
        // ridire ogni volta che si passa dall'una all'altra.
        $ultimo = AssistantMessage::query()
            ->where('topic', $this->topic()->value)
            ->whereNotNull('model')
            ->orderByDesc('id')
            ->value('model');

        $this->chatModel = $ultimo ?: static::defaultModel();
    }

    /**
     * I modelli scegliibili, id => etichetta.
     *
     * Sono quelli che hanno un prezzo: senza prezzo la chiamata verrebbe
     * rifiutata da `Pricing::ensurePriced`, quindi offrirli sarebbe offrire un
     * errore. L'etichetta viene dalla configurazione, se no dal catalogo di
     * Anthropic, se no è l'id nudo.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function modelOptions(): array
    {
        $etichette = (array) config('ai.assistant_models', []);
        $catalogo = app(ModelCatalog::class)->names();

        $ids = Pricing::models();
        $predefinito = static::defaultModel();
        if (! in_array($predefinito, $ids, true)) {
            $ids[] = $predefinito;
        }

        $opzioni = [];
        foreach ($ids as $id) {
            // La variante datata di un modello già in elenco è lo stesso
            // modello: nel listino ci sta come rete di sicurezza, nel menu no.
            if (ModelCatalog::isKnownAs($id, array_keys($opzioni)) && ! isset($etichette[$id])) {
                continue;
            }

            $opzioni[$id] = $etichette[$id] ?? ($catalogo[$id] ?? $id);
        }

        return $opzioni;
    }

    /**
     * Modelli che esistono sull'account ma che nessuno ha abilitato, perché non
     * hanno un prezzo. Si vedono come promemoria: così l'uscita di un modello
     * nuovo si nota, senza che il pannello inizi a usarlo da solo.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function newModels(): array
    {
        $conosciuti = array_unique(array_merge(array_keys($this->modelOptions), Pricing::models()));

        return array_filter(
            app(ModelCatalog::class)->names(),
            fn (string $id): bool => ! ModelCatalog::isKnownAs($id, $conosciuti),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Quanto è costato il mese, accanto al menu del modello.
     *
     * Sta lì apposta: scegliere Opus invece di Haiku costa cinque volte tanto,
     * e la differenza si vede solo se il numero è nella stessa riga della
     * scelta. Un tetto che si scopre quando la chat smette di rispondere è un
     * tetto che sorprende.
     */
    #[Computed]
    public function costoDelMese(): string
    {
        $speso = Budget::dollari(Budget::spentThisMonth());
        $tetto = Budget::limit();

        return $tetto > 0 ? $speso.' di '.Budget::dollari($tetto).' questo mese' : $speso.' questo mese';
    }

    private static function defaultModel(): string
    {
        $predefinito = (string) (config('ai.assistant_model') ?: config('ai.model'));

        return $predefinito !== '' ? $predefinito : (Pricing::models()[0] ?? 'claude-opus-5');
    }

    #[Computed]
    public function messages(): Collection
    {
        return AssistantMessage::query()->where('topic', $this->topic()->value)->orderBy('id')->get();
    }

    /** C'è un turno in corso: è quello che tiene la pagina ad aggiornarsi. */
    #[Computed]
    public function thinking(): bool
    {
        return AssistantMessage::query()
            ->where('topic', $this->topic()->value)
            ->where('status', 'pending')
            ->exists();
    }

    public function send(): void
    {
        $testo = trim($this->question);

        if ($testo === '') {
            return;
        }

        AssistantMessage::create([
            'topic' => $this->topic()->value,
            'role' => 'user',
            'content' => $testo,
            'status' => 'done',
        ]);

        /*
         * Quello che arriva dal browser non decide da solo su cosa spendere: se
         * l'id non è fra quelli in menu si torna al predefinito. La stessa cosa
         * la ricontrolla `Pricing::ensurePriced` più a valle, ma qui si evita
         * di scrivere in tabella un modello che poi fallirebbe.
         */
        $modello = isset($this->modelOptions[$this->chatModel])
            ? (string) $this->chatModel
            : static::defaultModel();

        // La risposta nasce vuota e in attesa: è quella riga a far comparire
        // "sto lavorando" al posto giusto, cioè in fondo alla conversazione.
        $risposta = AssistantMessage::create([
            'topic' => $this->topic()->value,
            'model' => $modello,
            'role' => 'assistant',
            'content' => null,
            'status' => 'pending',
        ]);

        $this->question = '';
        unset($this->messages, $this->thinking, $this->costoDelMese);

        RunAssistantTurn::dispatch(Auth::id(), $risposta->id, $testo, $this->topic(), $modello);
    }

    /** Chiamata dal polling mentre un turno è in corso. */
    public function refreshMessages(): void
    {
        unset($this->messages, $this->thinking, $this->costoDelMese);
    }

    /**
     * Ferma il turno in corso.
     *
     * Non uccide il worker — sta aspettando la rete — ma gli dice di non fare
     * il giro successivo. Quello che ha già eseguito resta eseguito, e la
     * risposta lo dice invece di far finta di niente.
     */
    public function stop(): void
    {
        AssistantMessage::query()
            ->where('topic', $this->topic()->value)
            ->where('status', 'pending')
            ->update(['status' => 'stopped']);

        unset($this->messages, $this->thinking, $this->costoDelMese);
    }

    public function clear(): void
    {
        AssistantMessage::query()->where('topic', $this->topic()->value)->delete();
        unset($this->messages, $this->thinking, $this->costoDelMese);
    }
}
