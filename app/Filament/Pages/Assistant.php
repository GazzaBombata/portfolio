<?php

namespace App\Filament\Pages;

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

        // La risposta nasce vuota e in attesa: è quella riga a far comparire
        // "sto lavorando" al posto giusto, cioè in fondo alla conversazione.
        $risposta = AssistantMessage::create([
            'topic' => $this->topic()->value,
            'role' => 'assistant',
            'content' => null,
            'status' => 'pending',
        ]);

        $this->question = '';
        unset($this->messages, $this->thinking);

        RunAssistantTurn::dispatch(Auth::id(), $risposta->id, $testo, $this->topic());
    }

    /** Chiamata dal polling mentre un turno è in corso. */
    public function refreshMessages(): void
    {
        unset($this->messages, $this->thinking);
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

        unset($this->messages, $this->thinking);
    }

    public function clear(): void
    {
        AssistantMessage::query()->where('topic', $this->topic()->value)->delete();
        unset($this->messages, $this->thinking);
    }
}
