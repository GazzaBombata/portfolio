<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Category;
use App\Models\Transaction;

class CategoriseTransactionsTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'classifica_movimenti';
    }

    public function description(): string
    {
        return 'Assegna una categoria a uno o più movimenti, per id. La scelta viene marcata come decisa da una persona e la classificazione automatica non la tocca più.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Gli id restituiti da cerca_movimenti'],
                'categoria' => ['type' => 'string', 'description' => 'Il nome esatto di una categoria esistente'],
            ],
            'required' => ['ids', 'categoria'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $categoria = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($input['categoria']))])
            ->first();

        if ($categoria === null) {
            $disponibili = Category::query()->orderBy('name')->pluck('name')->implode(', ');

            // Non si sceglie "la più simile": la più simile è comunque una che
            // l'utente non ha chiesto, e la differenza la vede solo lui dopo.
            return ToolResult::error(
                "Non esiste una categoria che si chiama «{$input['categoria']}». Quelle disponibili sono: {$disponibili}."
            );
        }

        $movimenti = Transaction::query()->whereIn('id', $input['ids'])->get();

        if ($movimenti->isEmpty()) {
            return ToolResult::error('Nessuno di quegli id corrisponde a un movimento.');
        }

        // Il segno decide: una categoria di entrata su un'uscita è un errore
        // silenzioso che sposta i totali.
        $incompatibili = $movimenti->filter(fn (Transaction $t): bool => match ($categoria->kind) {
            'income' => (float) $t->amount < 0,
            'expense' => (float) $t->amount > 0,
            default => false,
        });

        if ($incompatibili->isNotEmpty()) {
            return ToolResult::error(sprintf(
                '«%s» è una categoria di %s, ma %d dei movimenti indicati vanno nel verso opposto (#%s). Non ho cambiato niente.',
                $categoria->name,
                $categoria->kind === 'income' ? 'entrata' : 'uscita',
                $incompatibili->count(),
                $incompatibili->pluck('id')->implode(', #'),
            ));
        }

        $movimenti->each(fn (Transaction $t) => $t->update([
            'category_id' => $categoria->id,
            'category_locked' => true,
        ]));

        return ToolResult::ok(
            sprintf('%d movimenti classificati come «%s».', $movimenti->count(), $categoria->fullName()),
            sprintf('%d → %s', $movimenti->count(), $categoria->name),
        );
    }
}
