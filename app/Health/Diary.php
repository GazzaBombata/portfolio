<?php

namespace App\Health;

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Il diario: una riga per giorno, dal più vecchio al più recente, con dentro
 * tutto quello che di quel giorno è registrato.
 *
 * Le cinque risorse mostrano una cosa alla volta e la dashboard mostra oggi;
 * questo serve a guardare indietro tutto insieme — è la forma che si stampa e
 * si porta a un nutrizionista, dove sfogliare cinque elenchi separati non
 * funziona.
 *
 * Due scelte che vale la pena scrivere:
 *
 * - **I giorni vuoti restano nell'elenco**, se non si chiede di toglierli. Un
 *   buco non è un errore — un giorno senza allenamento può essere riposo — ma
 *   nascondere le righe vuote fa sembrare continuo un tracciamento che ha
 *   saltato tre settimane, e quella è l'unica cosa che una serie di giorni
 *   racconta meglio di qualunque media.
 * - **Niente si inventa e niente si interpola.** Il peso di un giorno senza
 *   misurazione resta vuoto: un valore stimato entrerebbe in tabella con
 *   l'aria di uno letto sulla bilancia. Vale per i numeri come per le parole:
 *   qui non si tronca niente, perché una descrizione tagliata a metà si legge
 *   come una descrizione intera.
 */
class Diary
{
    /** L'ordine in cui si mangia, che non è quello alfabetico né quello di inserimento. */
    private const MOMENTI = ['breakfast' => 0, 'lunch' => 1, 'snack' => 2, 'dinner' => 3];

    private const NOMI_MOMENTI = [
        'breakfast' => 'Colazione',
        'lunch' => 'Pranzo',
        'snack' => 'Spuntino',
        'dinner' => 'Cena',
    ];

    /**
     * Il primo giorno che ha qualcosa dentro.
     *
     * Serve come estremo predefinito dell'intervallo: «tutto quello che
     * abbiamo» è più facile da offrire che da far digitare.
     */
    public static function firstDay(): ?CarbonImmutable
    {
        return collect([
            DailyLog::query()->min('logged_on'),
            SleepLog::query()->min('night_of'),
            BodyMetric::query()->min('measured_on'),
            Meal::query()->min('eaten_on'),
            Workout::query()->min('performed_on'),
        ])
            ->filter()
            ->map(fn (string $data): CarbonImmutable => CarbonImmutable::parse($data))
            ->sort()
            ->first();
    }

    /**
     * Le righe dell'intervallo, in ordine cronologico.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function between(User $user, CarbonImmutable $dal, CarbonImmutable $al, bool $soloConDati = false): array
    {
        [$da, $a] = [$dal->toDateString(), $al->toDateString()];

        $notti = SleepLog::query()->whereBetween('night_of', [$da, $a])->get()
            ->keyBy(fn (SleepLog $n): string => $n->night_of->toDateString());

        $giornate = DailyLog::query()->whereBetween('logged_on', [$da, $a])->get()
            ->keyBy(fn (DailyLog $g): string => $g->logged_on->toDateString());

        $pesi = BodyMetric::query()->whereBetween('measured_on', [$da, $a])->get()
            ->keyBy(fn (BodyMetric $p): string => $p->measured_on->toDateString());

        $pasti = Meal::query()->with('items')->whereBetween('eaten_on', [$da, $a])->get()
            ->groupBy(fn (Meal $p): string => $p->eaten_on->toDateString());

        // Gli esercizi si caricano insieme alle sedute: senza, una scheda di
        // sei mesi fa una query per riga.
        $allenamenti = Workout::query()->with('exercises')->whereBetween('performed_on', [$da, $a])->get()
            ->groupBy(fn (Workout $w): string => $w->performed_on->toDateString());

        $righe = [];

        for ($giorno = $dal; $giorno->lessThanOrEqualTo($al); $giorno = $giorno->addDay()) {
            $chiave = $giorno->toDateString();

            $riga = static::day(
                $user,
                $giorno,
                $notti->get($chiave),
                $giornate->get($chiave),
                $pesi->get($chiave),
                $pasti->get($chiave) ?? collect(),
                $allenamenti->get($chiave) ?? collect(),
            );

            if ($soloConDati && $riga['vuoto']) {
                continue;
            }

            $righe[] = $riga;
        }

        return $righe;
    }

    /**
     * Una giornata.
     *
     * @param  Collection<int, Meal>  $pasti
     * @param  Collection<int, Workout>  $allenamenti
     * @return array<string, mixed>
     */
    private static function day(
        User $user,
        CarbonImmutable $giorno,
        ?SleepLog $notte,
        ?DailyLog $log,
        ?BodyMetric $peso,
        Collection $pasti,
        Collection $allenamenti,
    ): array {
        $mangiati = static::inOrdine($pasti->where('kind', 'eaten'));
        $previsti = static::inOrdine($pasti->where('kind', 'planned'));
        $fatti = $allenamenti->where('kind', 'done')->values();
        $inProgramma = $allenamenti->where('kind', 'planned')->values();

        $vuoto = $notte === null
            && $peso === null
            && $pasti->isEmpty()
            && $allenamenti->isEmpty()
            && ($log === null || ($log->steps === null && $log->water_litres === null && $log->nutrition_adherence === null && blank($log->notes)));

        return [
            'giorno' => $giorno,
            'vuoto' => $vuoto,
            'sonno' => $notte === null ? null : [
                'minuti' => $notte->minutes,
                'qualita' => $notte->quality,
                'risvegli' => $notte->awakenings,
                'addormentato' => $notte->fell_asleep_at?->format('H:i'),
                'sveglio' => $notte->woke_up_at?->format('H:i'),
                'note' => $notte->notes,
            ],
            'corpo' => $peso === null ? null : [
                'peso' => $peso->weight_kg === null ? null : (float) $peso->weight_kg,
                'grasso' => $peso->body_fat_pct === null ? null : (float) $peso->body_fat_pct,
                'muscolo' => $peso->muscle_mass_kg === null ? null : (float) $peso->muscle_mass_kg,
                'battito' => $peso->resting_hr,
                'note' => $peso->notes,
            ],
            'passi' => $log?->steps,
            'acqua' => $log?->water_litres === null ? null : (float) $log->water_litres,
            'aderenza' => $log?->nutrition_adherence,
            'note' => $log?->notes,
            'mangiati' => static::perMomento($mangiati),
            'previsti' => $previsti,
            'fatti' => static::allenamenti($fatti),
            'inProgramma' => static::allenamenti($inProgramma),
            'calorie' => $vuoto ? null : static::calorie($user, $giorno, $log, $mangiati),
            'avvisi' => $vuoto ? [] : static::avvisi($giorno, $mangiati),
        ];
    }

    /**
     * Il conto calorico del giorno.
     *
     * L'obiettivo e il fabbisogno sono due numeri diversi e stanno qui
     * affiancati apposta: l'obiettivo è quanto avevo deciso di mangiare, il
     * fabbisogno è quanto ho bruciato. Confonderli è l'errore che questa
     * applicazione esiste per rendere difficile, e un diario che ne mostrasse
     * uno solo lo renderebbe di nuovo facile.
     *
     * Un giorno senza pasti registrati non ha mangiato zero calorie: non ha
     * un dato. Scriverci 0 farebbe comparire un bilancio di −2.400 kcal per
     * una giornata in cui semplicemente non si è registrato niente, e in una
     * colonna di numeri quello si legge come un digiuno.
     *
     * @param  array<int, array<string, mixed>>  $mangiati
     * @return array<string, mixed>
     */
    private static function calorie(User $user, CarbonImmutable $giorno, ?DailyLog $log, array $mangiati): array
    {
        $mangiate = $mangiati === [] ? null : Energy::intake($giorno);
        $fabbisogno = static::fabbisogno($user, $giorno, $log);

        return [
            'mangiate' => $mangiate,
            'obiettivo' => Energy::target($giorno),
            'fabbisogno' => $fabbisogno,
            'attivita' => $log?->activity_calories !== null ? (int) $log->activity_calories : Energy::activityBurn($user, $giorno),
            'bilancio' => $fabbisogno === null || $mangiate === null ? null : $mangiate - $fabbisogno,
        ];
    }

    /**
     * Il fabbisogno di quel giorno, com'era quel giorno.
     *
     * `daily_logs.target_calories` lo salva al momento (vedi
     * `DayRecalculator`): dipende dal peso di allora e dagli allenamenti di
     * allora, e ricalcolarlo a mesi di distanza con i dati di oggi darebbe un
     * numero che quel giorno non è mai esistito — che in un diario è
     * esattamente la cosa da non fare.
     *
     * Si ricalcola solo quando in tabella non c'è niente, o quando quella
     * colonna sta ospitando un obiettivo scritto a mano: `targets_manual`
     * vuol dire che il valore lì dentro è un obiettivo, non un fabbisogno.
     */
    private static function fabbisogno(User $user, CarbonImmutable $giorno, ?DailyLog $log): ?int
    {
        if ($log !== null && $log->target_calories !== null && ! $log->targets_manual) {
            return (int) $log->target_calories;
        }

        return Energy::dailyNeed($user, $giorno);
    }

    /**
     * Quello che il giorno conta meno di quanto sembra.
     *
     * Sono tutti casi in cui una riga è registrata e vale zero: un pasto senza
     * calorie sta nell'elenco ma non nella somma, un allenamento senza durata
     * si vede ma non brucia niente. Detti qui perché una tabella che li tace
     * mostra numeri più bassi del vero senza che niente lo faccia sospettare.
     *
     * @param  array<int, array<string, mixed>>  $mangiati
     * @return array<int, string>
     */
    private static function avvisi(CarbonImmutable $giorno, array $mangiati): array
    {
        $avvisi = [];

        $senzaCalorie = count(array_filter($mangiati, fn (array $p): bool => $p['calorie'] === null));

        if ($senzaCalorie > 0) {
            $avvisi[] = $senzaCalorie === 1
                ? '1 pasto mangiato senza calorie: il totale è più basso del vero'
                : "{$senzaCalorie} pasti mangiati senza calorie: il totale è più basso del vero";
        }

        // Un ingrediente senza calorie non si vede: il pasto un totale ce l'ha,
        // solo che è la somma di meno righe di quante ne ha davvero.
        $ingredientiSenza = array_sum(array_map(fn (array $p): int => $p['ingredientiSenzaCalorie'], $mangiati));

        if ($ingredientiSenza > 0) {
            $avvisi[] = $ingredientiSenza === 1
                ? '1 ingrediente senza calorie: il totale del suo pasto è più basso del vero'
                : "{$ingredientiSenza} ingredienti senza calorie: il totale dei loro pasti è più basso del vero";
        }

        if (($previstiSenza = Energy::plannedWithoutCalories($giorno)) > 0) {
            $avvisi[] = $previstiSenza === 1
                ? '1 pasto previsto senza calorie: l\'obiettivo è più basso del piano'
                : "{$previstiSenza} pasti previsti senza calorie: l'obiettivo è più basso del piano";
        }

        foreach (Energy::workoutsWithoutDuration($giorno) as $attivita) {
            $avvisi[] = "{$attivita}: senza durata, vale zero calorie";
        }

        foreach (Energy::grossWithoutDuration($giorno) as $attivita) {
            $avvisi[] = "{$attivita}: calorie da cardio senza durata, il basale di quei minuti è contato due volte";
        }

        foreach (Energy::defaultMetWorkouts($giorno) as $attivita) {
            $avvisi[] = "{$attivita}: attività sconosciuta, contata con il valore di ripiego";
        }

        foreach (Energy::overlappingWorkouts($giorno) as $attivita) {
            $avvisi[] = "{$attivita}: i passi la contano già";
        }

        return $avvisi;
    }

    /**
     * I pasti mangiati divisi in tre gruppi, uno per colonna del diario.
     *
     * Pranzo e cena sono i due pasti che si confrontano con il piano e che
     * hanno dentro delle frasi intere; colazione e spuntini stanno insieme
     * perché sono corti e perché uno spuntino senza la colazione accanto non
     * si legge — «tre mandorle» da solo non dice se quella mattina si è
     * mangiato. I gruppi ci sono anche vuoti: una colonna «Cena» con dentro
     * un trattino dice che quel giorno la cena non è registrata, e toglierla
     * farebbe sembrare completa una giornata a cui manca il pasto principale.
     *
     * @param  array<int, array<string, mixed>>  $mangiati
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function perMomento(array $mangiati): array
    {
        $gruppi = ['colazione' => [], 'pranzo' => [], 'cena' => []];

        foreach ($mangiati as $pasto) {
            $gruppi[match ($pasto['chiave']) {
                'lunch' => 'pranzo',
                'dinner' => 'cena',
                default => 'colazione',
            }][] = $pasto;
        }

        return $gruppi;
    }

    /**
     * I pasti nell'ordine in cui si mangiano.
     *
     * @param  Collection<int, Meal>  $pasti
     * @return array<int, array<string, mixed>>
     */
    private static function inOrdine(Collection $pasti): array
    {
        return $pasti
            ->sortBy(fn (Meal $p): string => sprintf('%d-%s', self::MOMENTI[$p->moment] ?? 9, $p->eaten_at ?? ''))
            ->map(fn (Meal $p): array => [
                'chiave' => $p->moment,
                'momento' => self::NOMI_MOMENTI[$p->moment] ?? $p->moment,
                'ora' => $p->eaten_at === null ? null : substr((string) $p->eaten_at, 0, 5),
                'descrizione' => $p->description,
                'calorie' => $p->calories,
                'proteine' => $p->protein_g,
                'carboidrati' => $p->carbs_g,
                'grassi' => $p->fat_g,
                'ingredientiSenzaCalorie' => $p->items->whereNull('calories')->count(),
                'stimato' => (bool) $p->nutrition_estimated,
                'fuori' => (bool) $p->eaten_out,
                'note' => $p->notes,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Workout>  $allenamenti
     * @return array<int, array<string, mixed>>
     */
    private static function allenamenti(Collection $allenamenti): array
    {
        return $allenamenti
            ->sortBy(fn (Workout $w): string => (string) $w->started_at)
            ->map(fn (Workout $w): array => [
                'attivita' => $w->activity,
                'ora' => $w->started_at === null ? null : substr((string) $w->started_at, 0, 5),
                'minuti' => $w->minutes,
                'km' => $w->distance_km === null ? null : (float) $w->distance_km,
                'intensita' => $w->intensity,
                'calorie' => $w->calories,
                // Chi ha deciso questa seduta si legge qui e non si ricostruisce
                // a memoria fra sei mesi: vedi CLAUDE.md, `authored_by`.
                'daAssistente' => $w->proposedByAssistant(),
                'esercizi' => $w->exercises->map(fn ($e): string => $e->summary())->all(),
                'note' => $w->notes,
            ])
            ->values()
            ->all();
    }
}
