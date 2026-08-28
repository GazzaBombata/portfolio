<?php

namespace App\Filament\Widgets;

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\SleepLog;
use App\Models\Workout;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Gli ultimi sette giorni di sonno, movimento, acqua e peso.
 *
 * Sette giorni e non "questo mese": sono abitudini, e su un mese la media
 * nasconde proprio quello che serve vedere — che sono tre giorni che non ti
 * alleni. Ogni riquadro dice anche su quanti giorni è calcolata la media,
 * perché una media su due giorni non è la stessa cosa di una su sette.
 */
class HealthOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'La tua settimana';

    protected function getStats(): array
    {
        $da = now()->subDays(6)->startOfDay();

        $notti = SleepLog::query()->where('night_of', '>=', $da)->whereNotNull('minutes')->get();
        $allenamenti = Workout::query()->done()->where('performed_on', '>=', $da)->get();
        $giornate = DailyLog::query()->where('logged_on', '>=', $da)->get();
        $peso = BodyMetric::query()->whereNotNull('weight_kg')->orderByDesc('measured_on')->first();

        return [
            $this->sonno($notti),
            $this->movimento($allenamenti),
            $this->acqua($giornate),
            $this->peso($peso),
        ];
    }

    private function sonno($notti): Stat
    {
        if ($notti->isEmpty()) {
            return Stat::make('Sonno', '—')->description('nessuna notte registrata')->color('gray');
        }

        $media = (int) round($notti->avg('minutes'));
        $qualita = $notti->whereNotNull('quality')->avg('quality');

        return Stat::make('Sonno', sprintf('%dh %02dm', intdiv($media, 60), $media % 60))
            ->description(sprintf(
                'media su %d %s%s',
                $notti->count(),
                $notti->count() === 1 ? 'notte' : 'notti',
                $qualita !== null ? sprintf(' · qualità %.1f/5', $qualita) : '',
            ))
            ->color($media >= 420 ? 'success' : 'warning');
    }

    private function movimento($allenamenti): Stat
    {
        if ($allenamenti->isEmpty()) {
            return Stat::make('Movimento', '—')->description('niente in questa settimana')->color('gray');
        }

        $minuti = (int) $allenamenti->sum('minutes');
        $tipi = $allenamenti->pluck('activity')->unique()->take(3)->implode(', ');

        return Stat::make('Movimento', sprintf('%dh %02dm', intdiv($minuti, 60), $minuti % 60))
            ->description($allenamenti->count().' volte · '.$tipi)
            // L'OMS raccomanda 150 minuti a settimana: è il metro più comune,
            // e serve solo a dare un colore, non a dare un giudizio.
            ->color($minuti >= 150 ? 'success' : 'warning');
    }

    private function acqua($giornate): Stat
    {
        $conAcqua = $giornate->whereNotNull('water_litres');

        if ($conAcqua->isEmpty()) {
            return Stat::make('Acqua', '—')->description('nessun giorno registrato')->color('gray');
        }

        $media = (float) $conAcqua->avg('water_litres');
        $piano = $giornate->whereNotNull('nutrition_adherence')->avg('nutrition_adherence');

        return Stat::make('Acqua', number_format($media, 1, ',', '.').' l')
            ->description(sprintf(
                'media su %d %s%s',
                $conAcqua->count(),
                $conAcqua->count() === 1 ? 'giorno' : 'giorni',
                $piano !== null ? sprintf(' · piano %.1f/10', $piano) : '',
            ))
            ->color($media >= 2 ? 'success' : 'warning');
    }

    private function peso(?BodyMetric $ultimo): Stat
    {
        if ($ultimo === null) {
            return Stat::make('Peso', '—')->description('nessuna misurazione')->color('gray');
        }

        // Il confronto è con la misurazione precedente, qualunque sia la sua
        // data: pesarsi ogni tanto è normale, e un "rispetto a 30 giorni fa"
        // che non trova nulla non direbbe niente.
        $prima = BodyMetric::query()
            ->whereNotNull('weight_kg')
            ->where('measured_on', '<', $ultimo->measured_on)
            ->orderByDesc('measured_on')
            ->first();

        $descrizione = $ultimo->measured_on->format('d/m/Y');

        if ($prima !== null) {
            $delta = (float) $ultimo->weight_kg - (float) $prima->weight_kg;
            $descrizione .= sprintf(' · %s%.1f kg dal %s', $delta >= 0 ? '+' : '', $delta, $prima->measured_on->format('d/m'));
        }

        return Stat::make('Peso', number_format((float) $ultimo->weight_kg, 1, ',', '.').' kg')
            ->description($descrizione)
            ->color('info');
    }
}
