<?php

namespace App\Jobs;

use App\Assistant\Runner;
use App\Assistant\Topic;
use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Esegue un turno dell'assistente fuori dalla richiesta web.
 *
 * Una chiamata al modello con qualche giro di strumenti dura dei secondi: in
 * linea diretta significherebbe una pagina che resta appesa e, oltre il minuto,
 * un 504 mentre il lavoro dietro è andato a buon fine.
 */
class RunAssistantTurn implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public readonly int $userId,
        public readonly int $messageId,
        public readonly string $question,
        public readonly Topic $topic = Topic::Finance,
    ) {}

    public function handle(Runner $runner): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        /*
         * Il turno gira COME la persona a cui la conversazione appartiene.
         *
         * Gli strumenti leggono e scrivono attraverso i modelli, che si filtrano
         * da soli sull'utente autenticato — e in un job non c'è nessuna
         * sessione: senza questa riga ogni ricerca risponderebbe "niente" e ogni
         * scrittura fallirebbe. L'utente viene rimosso alla fine perché il
         * worker riusa lo stesso processo per il turno successivo, che può
         * essere di un'altra persona.
         */
        Auth::setUser($user);

        try {
            // Lo stop arriva da un'altra richiesta HTTP, che scrive sulla riga:
            // il worker lo scopre rileggendola, che è l'unico canale che i due
            // processi hanno in comune.
            $esito = $runner->run($this->question, $this->topic, fn (): bool => AssistantMessage::query()
                ->withoutGlobalScope('user')
                ->whereKey($this->messageId)
                ->where('status', 'stopped')
                ->exists());

            AssistantMessage::query()->whereKey($this->messageId)->update([
                'content' => $esito['content'],
                'steps' => $esito['steps'],
                'status' => ($esito['stopped'] ?? false) ? 'stopped' : 'done',
            ]);
        } catch (Throwable $e) {
            AssistantMessage::query()->whereKey($this->messageId)->update([
                'content' => 'Qualcosa è andato storto: '.$e->getMessage(),
                'status' => 'failed',
            ]);
        } finally {
            Auth::forgetGuards();
        }
    }
}
