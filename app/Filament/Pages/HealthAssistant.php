<?php

namespace App\Filament\Pages;

use App\Assistant\Topic;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Il consulente per la salute: sonno, movimento, alimentazione, peso.
 */
class HealthAssistant extends Assistant
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = 'Consulente salute';

    protected static ?string $title = 'Consulente salute';

    public function topic(): Topic
    {
        return Topic::Health;
    }
}
