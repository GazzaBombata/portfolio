<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Finance\Reporting;
use App\Models\Transaction;

class SearchTransactionsTool implements Tool
{
    public function name(): string
    {
        return 'cerca_movimenti';
    }

    public function description(): string
    {
        return 'Cerca movimenti bancari per testo, periodo o stato di classificazione. Restituisce al massimo 30 righe con il loro id.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'testo' => ['type' => ['string', 'null'], 'description' => 'Parte della descrizione o del mittente'],
                'dal' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
                'al' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
                'solo_da_classificare' => ['type' => ['boolean', 'null']],
            ],
        ];
    }

    public function run(array $input): ToolResult
    {
        $query = Reporting::realMovements([
            'periodo' => 'personalizzato',
            'dal' => $input['dal'] ?? null,
            'al' => $input['al'] ?? null,
        ])->with(['account', 'category']);

        if (filled($input['testo'] ?? null)) {
            $testo = '%'.$input['testo'].'%';
            $query->where(fn ($q) => $q
                ->where('description', 'like', $testo)
                ->orWhere('raw_description', 'like', $testo)
                ->orWhere('notes', 'like', $testo)
                ->orWhere('counterparty', 'like', $testo));
        }

        if ($input['solo_da_classificare'] ?? false) {
            $query->whereNull('category_id');
        }

        $totale = (clone $query)->count();
        $movimenti = $query->orderByDesc('booked_on')->limit(30)->get();

        if ($movimenti->isEmpty()) {
            return ToolResult::ok('Nessun movimento trovato con questi criteri.', 'nessun risultato');
        }

        $righe = $movimenti->map(fn (Transaction $t): string => sprintf(
            '#%d | %s | %s | %s | %s | %s',
            $t->id,
            $t->booked_on->format('d/m/Y'),
            Reporting::euro((float) $t->amount),
            (string) $t->description,
            $t->account->name,
            $t->category?->name ?? 'DA CLASSIFICARE',
        ));

        $intestazione = $totale > 30
            // Detto, non taciuto: una risposta costruita su un campione
            // spacciato per l'insieme è il modo in cui nasce un numero sbagliato.
            ? "Trovati {$totale} movimenti, ne mostro i 30 più recenti:"
            : "Trovati {$totale} movimenti:";

        return ToolResult::ok(
            $intestazione."\n".$righe->implode("\n"),
            "{$totale} movimenti trovati",
        );
    }
}
