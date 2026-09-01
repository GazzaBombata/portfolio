<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Category;

class CreateCategoryTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'crea_categoria';
    }

    public function description(): string
    {
        return 'Crea una categoria di spesa o di entrata, se davvero non ne esiste già una adatta. Usala solo quando te lo viene chiesto o approvato esplicitamente: le categorie sono il modo in cui legge i suoi soldi, e crearne troppe le rende inutili.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nome' => ['type' => 'string'],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['expense', 'income', 'transfer'],
                    'description' => 'expense = una spesa, income = un\'entrata, transfer = uno spostamento fra conti propri',
                ],
                'dentro' => [
                    'type' => ['string', 'null'],
                    'description' => 'Il nome esatto di una categoria principale esistente, per creare una sottocategoria',
                ],
            ],
            'required' => ['nome', 'tipo'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $nome = trim($input['nome']);

        if (mb_strlen($nome) < 3) {
            return ToolResult::error('Un nome di due caratteri non dice niente a nessuno: dammene uno più esplicito.');
        }

        $padre = null;

        if (filled($input['dentro'] ?? null)) {
            $padre = Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($input['dentro']))])->first();

            if ($padre === null) {
                $disponibili = Category::query()->whereNull('parent_id')->orderBy('name')->pluck('name')->implode(', ');

                return ToolResult::error("Non esiste una categoria principale «{$input['dentro']}». Quelle che ci sono: {$disponibili}.");
            }

            if ($padre->kind !== $input['tipo']) {
                // Una sottocategoria di entrata dentro una di spesa produce
                // totali che non tornano e nessuno capisce perché.
                return ToolResult::error(
                    "«{$padre->name}» è una categoria di ".($padre->kind === 'income' ? 'entrata' : 'spesa')
                    .', non ci può stare dentro una di tipo diverso.'
                );
            }
        }

        /*
         * Se ne esiste già una che si chiama così, si riusa.
         *
         * Due categorie con lo stesso nome sono il modo più rapido per rendere
         * illeggibile un riepilogo: i totali si spezzano in due righe identiche
         * e chi guarda pensa a un errore di calcolo.
         */
        $esistente = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($nome)])
            ->where('parent_id', $padre?->id)
            ->first();

        if ($esistente !== null) {
            return ToolResult::ok(
                "«{$esistente->fullName()}» esiste già: la uso invece di crearne una uguale.",
                'già esistente: '.$esistente->name,
            );
        }

        $categoria = Category::create([
            'name' => $nome,
            'kind' => $input['tipo'],
            'parent_id' => $padre?->id,
        ]);

        return ToolResult::ok(
            "Creata la categoria «{$categoria->fullName()}» ("
            .match ($categoria->kind) {
                'income' => 'entrata',
                'transfer' => 'giroconto',
                default => 'spesa',
            }.').',
            'nuova: '.$categoria->name,
        );
    }
}
