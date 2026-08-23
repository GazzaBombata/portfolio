<?php

namespace App\Finance;

use Carbon\CarbonImmutable;

/**
 * L'intervallo di date che i riquadri stanno guardando.
 *
 * Tradurre "questo mese" in due date è una cosa sola e sta in un posto solo:
 * fatta in ogni riquadro, prima o poi uno interpreta "ultimi 3 mesi" come 90
 * giorni e un altro come tre mesi di calendario, e i due totali accanto non
 * tornano.
 */
class Period
{
    public function __construct(
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $to,
        public readonly string $label,
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromFilters(?array $filters): self
    {
        $oggi = CarbonImmutable::now();

        return match ($filters['periodo'] ?? 'anno') {
            'mese' => new self($oggi->startOfMonth(), $oggi->endOfMonth(), 'questo mese'),
            'scorso' => new self(
                $oggi->subMonth()->startOfMonth(),
                $oggi->subMonth()->endOfMonth(),
                'il mese scorso',
            ),
            'trimestre' => new self($oggi->subMonths(2)->startOfMonth(), $oggi->endOfMonth(), 'gli ultimi 3 mesi'),
            'tutto' => new self(null, null, 'tutto lo storico'),
            'personalizzato' => self::custom($filters),
            default => new self($oggi->startOfYear(), $oggi->endOfYear(), "il {$oggi->year}"),
        };
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    private static function custom(?array $filters): self
    {
        $dal = filled($filters['dal'] ?? null) ? CarbonImmutable::parse($filters['dal']) : null;
        $al = filled($filters['al'] ?? null) ? CarbonImmutable::parse($filters['al'])->endOfDay() : null;

        $label = match (true) {
            $dal !== null && $al !== null => 'dal '.$dal->format('d/m/Y').' al '.$al->format('d/m/Y'),
            $dal !== null => 'dal '.$dal->format('d/m/Y'),
            $al !== null => 'fino al '.$al->format('d/m/Y'),
            default => 'tutto lo storico',
        };

        return new self($dal, $al, $label);
    }

    /** Lo stesso intervallo, spostato indietro della propria durata: il "prima". */
    public function previous(): self
    {
        if ($this->from === null || $this->to === null) {
            return new self(null, null, 'nessun confronto');
        }

        // Sui giorni interi: `to` porta con sé le 23:59 di fine giornata, e
        // contarle darebbe un intervallo precedente lungo un giorno in più.
        $giorni = (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;

        return new self(
            $this->from->subDays($giorni),
            $this->from->subDay()->endOfDay(),
            'il periodo precedente',
        );
    }
}
