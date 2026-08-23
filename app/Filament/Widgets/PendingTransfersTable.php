<?php

namespace App\Filament\Widgets;

use App\Finance\Reporting;
use App\Finance\TransferMatcher;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/**
 * I travasi che il riconoscimento automatico non se l'è sentita di decidere.
 *
 * Compare solo quando ce n'è almeno uno: un riquadro vuoto che dice «niente da
 * fare» occupa lo stesso spazio di uno pieno e insegna a non guardarlo.
 */
class PendingTransfersTable extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.pending-transfers';

    public function getPending(): array
    {
        return app(TransferMatcher::class)->pending();
    }

    public static function canView(): bool
    {
        return app(TransferMatcher::class)->pending() !== [];
    }

    public function confirm(int $outId, int $inId): void
    {
        $out = Transaction::find($outId);
        $in = Transaction::find($inId);

        if ($out === null || $in === null) {
            Notification::make()->title('Movimento non trovato')->danger()->send();

            return;
        }

        app(TransferMatcher::class)->confirm($out, $in);

        Notification::make()
            ->title('Segnati come giroconto')
            ->body('Escono dai totali di spesa: erano '.Reporting::euro(abs((float) $out->amount)).' contati due volte.')
            ->success()
            ->send();
    }

    public function euro(float $importo): string
    {
        return Reporting::euro($importo);
    }
}
