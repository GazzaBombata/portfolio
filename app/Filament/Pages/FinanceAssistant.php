<?php

namespace App\Filament\Pages;

use App\Assistant\Topic;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Il consulente per le spese.
 *
 * Porta con sé solo gli strumenti dei soldi. Non è una divisione formale: un
 * assistente che non può toccare i pasti non ha motivo di portarsi dietro
 * dodici schemi su come si registrano — e quegli schemi si pagano a ogni
 * domanda.
 */
class FinanceAssistant extends Assistant
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Spese';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Consulente spese';

    protected static ?string $title = 'Consulente spese';

    public function topic(): Topic
    {
        return Topic::Finance;
    }
}
