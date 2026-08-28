<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'workout_exercises';

    protected $fillable = [
        'workout_id',
        'position',
        'name',
        'sets',
        'reps',
        'load_kg',
        'seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return ['load_kg' => 'decimal:2'];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Il tonnellaggio: serie × ripetizioni × carico.
     *
     * È il numero su cui si legge una progressione, e si calcola invece di
     * scriverlo perché scritto sarebbe una quarta cifra da tenere allineata
     * alle altre tre — cioè una che prima o poi le contraddice.
     *
     * Null quando manca un fattore: a corpo libero il volume in chili non
     * esiste, e metterci zero lo farebbe sembrare un allenamento a vuoto.
     */
    public function volumeKg(): ?float
    {
        if ($this->sets === null || $this->reps === null || $this->load_kg === null) {
            return null;
        }

        return round($this->sets * $this->reps * (float) $this->load_kg, 1);
    }

    /** I chili come si scrivono in italiano: 62,5 e non 62.50. */
    public static function kg(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    }

    /** Come si legge in una riga: «panca 4×8 a 60 kg». */
    public function summary(): string
    {
        $parti = [];

        if ($this->sets !== null && $this->reps !== null) {
            $parti[] = "{$this->sets}×{$this->reps}";
        } elseif ($this->sets !== null) {
            $parti[] = "{$this->sets} serie";
        } elseif ($this->reps !== null) {
            $parti[] = "{$this->reps} ripetizioni";
        }

        if ($this->seconds !== null) {
            $parti[] = "{$this->seconds}\"";
        }

        if ($this->load_kg !== null) {
            $parti[] = 'a '.static::kg((float) $this->load_kg).' kg';
        }

        return $this->name.($parti === [] ? '' : ' '.implode(' ', $parti));
    }
}
