<?php

namespace App\Assistant;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Quello che il turno aveva in mano quando ha finito i giri.
 *
 * Serve a rendere onesta la domanda «vuoi che continui?». Di un turno, in
 * tabella, sopravvivono solo il testo e i nomi degli strumenti: i RISULTATI no.
 * Quindi un «sì» senza questi appunti fa ripartire da zero, e le stesse
 * ricerche che avevano già esaurito sei giri vengono rifatte tutte — cioè la
 * risposta costa il doppio e il tetto viene colpito una seconda volta.
 *
 * Tre limiti, e ognuno chiude un modo di far male:
 *
 * - **Scadono.** Mezz'ora dopo, «sì» vuol dire un'altra cosa.
 * - **Non crescono all'infinito.** Oltre il tetto di caratteri non si salva:
 *   meglio nessuna ripresa che una voce di cache enorme per ogni turno lungo.
 * - **Si riprende al massimo due volte.** Una catena infinita di «continua»
 *   spenderebbe senza che nessuno abbia deciso di spendere.
 *
 * Stanno in cache e non in tabella di proposito: sono appunti di lavoro, non
 * la conversazione. Se Redis li perde, si perde una scorciatoia e non un dato.
 */
class ResumeNotes
{
    public const MINUTES = 30;

    /** Oltre questo, non si salva: sono i risultati degli strumenti, e crescono. */
    public const MAX_CHARS = 200_000;

    public const MAX_RESUMES = 2;

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array{tool: string, summary: ?string}>  $steps
     */
    public static function remember(Topic $topic, array $messages, array $steps, int $riprese = 0): void
    {
        if ($riprese >= self::MAX_RESUMES) {
            return;
        }

        $appunti = ['messages' => $messages, 'steps' => $steps, 'riprese' => $riprese + 1];

        if (mb_strlen(json_encode($appunti) ?: '') > self::MAX_CHARS) {
            return;
        }

        Cache::put(static::key($topic), $appunti, now()->addMinutes(self::MINUTES));
    }

    /**
     * Gli appunti, se ce ne sono — e si consumano leggendoli.
     *
     * Consumati e non lasciati lì: un «sì» detto due volte non deve ripartire
     * due volte dallo stesso punto.
     *
     * @return array{messages: array<int, array<string, mixed>>, steps: array<int, array{tool: string, summary: ?string}>, riprese: int}|null
     */
    public static function take(Topic $topic): ?array
    {
        $appunti = Cache::pull(static::key($topic));

        return is_array($appunti) && isset($appunti['messages']) ? $appunti : null;
    }

    public static function forget(Topic $topic): void
    {
        Cache::forget(static::key($topic));
    }

    /**
     * «Sì», «vai», «continua» — e nient'altro.
     *
     * Volutamente strettissima. Sbagliare per eccesso significa riprendere una
     * ricerca vecchia al posto di una domanda nuova, cioè rispondere a
     * qualcosa che non è stato chiesto; sbagliare per difetto costa una
     * ricerca rifatta, che è quello che succedeva prima. Un «sì ma guarda solo
     * agosto» è una domanda nuova, e va trattato come tale.
     */
    public static function soundsLikeYes(string $question): bool
    {
        return preg_match(
            '/^\s*(s[iì]+|ok(?:ay)?|va bene|vai|dai|certo|procedi|prosegui|continua|avanti|d\'accordo|perfetto)'
            .'[\s,.!]*(?:pure|avanti|grazie|continua|vai|con questo)?[\s,.!]*$/iu',
            $question,
        ) === 1;
    }

    private static function key(Topic $topic): string
    {
        return 'assistente:ripresa:'.Auth::id().':'.$topic->value;
    }
}
