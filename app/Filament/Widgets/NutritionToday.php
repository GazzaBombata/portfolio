<?php

namespace App\Filament\Widgets;

use App\Health\Energy;
use App\Models\Meal;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Come sta andando oggi: due barre e un confronto pasto per pasto.
 *
 * Le due barre rispondono a due domande diverse che è facile confondere:
 *
 * - **piano** — di quello che avevo deciso di mangiare, quanto ne ho mangiato?
 * - **fabbisogno** — di quello che brucio oggi (basale + attività + passi),
 *   quanto ne ho reintegrato?
 *
 * Non sono la stessa percentuale e non lo diventano mai: si può essere al 100%
 * del piano e al 55% del fabbisogno, ed è esattamente l'informazione che
 * interessa in un deficit calorico. Tenerle affiancate è il punto del riquadro;
 * mostrarne una sola lascerebbe credere che l'altra le somigli.
 *
 * Le barre possono superare il 100% e lo fanno vedere invece di fermarsi al
 * bordo: una barra che si ferma a filo dice «obiettivo raggiunto» tanto a chi
 * è in pari quanto a chi ha sforato del 40%.
 */
class NutritionToday extends Widget
{
    protected string $view = 'filament.widgets.nutrition-today';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $oggi = CarbonImmutable::today();
        $utente = Auth::user();

        $mangiate = Energy::intake($oggi);
        $obiettivo = Energy::target($oggi);
        $fabbisogno = $utente !== null ? Energy::dailyNeed($utente, $oggi) : null;

        return [
            'mangiate' => $mangiate,
            'obiettivo' => $obiettivo,
            'fabbisogno' => $fabbisogno,
            'pianoIncompleto' => Energy::plannedWithoutCalories($oggi),
            'basale' => $utente !== null ? (int) round((float) (Energy::basalRate($utente) ?? 0) * (float) $utente->activity_factor) : 0,
            'attivita' => $utente !== null ? Energy::activityBurn($utente, $oggi) : 0,
            // Una seduta senza durata si vede fra gli allenamenti ma vale zero
            // calorie: senza dirlo, il riquadro sembra averla contata.
            'attivitaSenzaDurata' => Energy::workoutsWithoutDuration($oggi),
            'passi' => $utente !== null ? Energy::stepsBurn($utente, $oggi) : 0,
            'pasti' => $this->pasti($oggi),
        ];
    }

    /**
     * I quattro momenti, previsto contro mangiato.
     *
     * Ci sono tutti e quattro anche quando sono vuoti: una riga «Cena — niente»
     * è un'informazione, e toglierla farebbe sembrare completa una giornata a
     * cui manca il pasto principale.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pasti(CarbonImmutable $giorno): array
    {
        $nomi = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'snack' => 'Spuntini', 'dinner' => 'Cena'];

        $somma = fn (string $kind): Collection => Meal::query()
            ->where('kind', $kind)
            ->whereDate('eaten_on', $giorno)
            ->get()
            ->groupBy('moment');

        $previsti = $somma('planned');
        $mangiati = $somma('eaten');

        $righe = [];

        foreach ($nomi as $chiave => $nome) {
            $p = $previsti->get($chiave);
            $m = $mangiati->get($chiave);

            $righe[] = [
                'nome' => $nome,
                'previsto' => (int) ($p?->sum('calories') ?? 0),
                'mangiato' => (int) ($m?->sum('calories') ?? 0),
                'descrizionePrevisto' => $p?->pluck('description')->implode(' + '),
                'descrizioneMangiato' => $m?->pluck('description')->implode(' + '),
            ];
        }

        return $righe;
    }
}
