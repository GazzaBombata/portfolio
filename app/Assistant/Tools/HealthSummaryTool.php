<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class HealthSummaryTool implements Tool
{
    public function name(): string
    {
        return 'riepilogo_salute';
    }

    public function description(): string
    {
        return 'Legge sonno, allenamenti, pasti, acqua e peso di un intervallo di giorni. Usalo prima di rispondere a domande su come sta andando.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'dal' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'al' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
            ],
            'required' => ['dal', 'al'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $dal = CarbonImmutable::parse($input['dal'])->toDateString();
        $al = CarbonImmutable::parse($input['al'])->toDateString();

        $notti = SleepLog::query()->whereBetween('night_of', [$dal, $al])->orderBy('night_of')->get();
        $allenamenti = Workout::query()->whereBetween('performed_on', [$dal, $al])->orderBy('performed_on')->get();
        $pasti = Meal::query()->eaten()->whereBetween('eaten_on', [$dal, $al])->orderBy('eaten_on')->get();
        $giornate = DailyLog::query()->whereBetween('logged_on', [$dal, $al])->orderBy('logged_on')->get();
        $pesi = BodyMetric::query()->whereBetween('measured_on', [$dal, $al])->orderBy('measured_on')->get();

        $righe = ["Dal {$dal} al {$al}:"];

        $righe[] = $notti->isEmpty()
            ? '- Sonno: nessuna notte registrata.'
            : sprintf('- Sonno: %d notti, media %d minuti%s.', $notti->count(), (int) $notti->avg('minutes'),
                $notti->whereNotNull('quality')->isNotEmpty() ? sprintf(', qualità media %.1f/5', $notti->avg('quality')) : '');

        $righe[] = $allenamenti->isEmpty()
            ? '- Allenamenti: nessuno.'
            : sprintf('- Allenamenti: %d, %d minuti in tutto (%s).', $allenamenti->count(), (int) $allenamenti->sum('minutes'),
                $allenamenti->pluck('activity')->unique()->implode(', '));

        $righe[] = $pasti->isEmpty()
            ? '- Pasti: nessuno registrato.'
            : sprintf('- Pasti: %d registrati. Ultimi: %s.', $pasti->count(),
                $pasti->reverse()->take(3)->map(fn (Meal $m): string => $m->eaten_on->format('d/m').' '.Str::limit($m->description, 40))->implode(' · '));

        $conAcqua = $giornate->whereNotNull('water_litres');
        $righe[] = $conAcqua->isEmpty()
            ? '- Acqua: nessun giorno registrato.'
            : sprintf('- Acqua: media %.1f litri su %d giorni.', $conAcqua->avg('water_litres'), $conAcqua->count());

        $righe[] = $pesi->isEmpty()
            ? '- Peso: nessuna misurazione.'
            : sprintf('- Peso: da %.1f a %.1f kg.', (float) $pesi->first()->weight_kg, (float) $pesi->last()->weight_kg);

        return ToolResult::ok(implode("\n", $righe), "Riepilogo salute {$dal} → {$al}");
    }
}
