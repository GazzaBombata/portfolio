<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Finance\Reporting;
use App\Models\Category;

class SpendingSummaryTool implements Tool
{
    public function name(): string
    {
        return 'riepilogo_spese';
    }

    public function description(): string
    {
        return 'Entrate, uscite e ripartizione per categoria in un periodo. I giroconti fra conti propri sono sempre esclusi.';
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
        $filtri = ['periodo' => 'personalizzato', 'dal' => $input['dal'], 'al' => $input['al']];

        $entrate = (float) Reporting::income($filtri)->sum('amount');
        $uscite = (float) Reporting::expenses($filtri)->sum('amount');
        $scoperti = Reporting::expenses($filtri)->whereNull('category_id')->count();

        $righe = ["Dal {$input['dal']} al {$input['al']} (giroconti esclusi):"];
        $righe[] = '- Entrate: '.Reporting::euro($entrate);
        $righe[] = '- Uscite: '.Reporting::euro(abs($uscite));
        $righe[] = '- Saldo: '.Reporting::euro($entrate + $uscite);

        $perCategoria = Reporting::expenses($filtri)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as totale, COUNT(*) as n')
            ->groupBy('category_id')
            ->orderByRaw('SUM(amount)')
            ->limit(15)
            ->get();

        if ($perCategoria->isNotEmpty()) {
            $righe[] = 'Per categoria:';
            $categorie = Category::query()->whereIn('id', $perCategoria->pluck('category_id'))->with('parent')->get()->keyBy('id');

            foreach ($perCategoria as $r) {
                $righe[] = sprintf('  - %s: %s (%d movimenti)',
                    $categorie[$r->category_id]?->fullName() ?? '?',
                    Reporting::euro(abs((float) $r->totale)),
                    $r->n);
            }
        }

        if ($scoperti > 0) {
            // Va detto: un totale per categoria che copre metà dei movimenti
            // non è una risposta completa, e chi legge deve saperlo.
            $righe[] = "Attenzione: {$scoperti} movimenti di spesa non hanno ancora una categoria, quindi la ripartizione qui sopra non è completa.";
        }

        return ToolResult::ok(implode("\n", $righe), 'Riepilogo spese');
    }
}
