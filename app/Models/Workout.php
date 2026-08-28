<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Observers\WorkoutObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(WorkoutObserver::class)]
class Workout extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'workouts';

    protected $fillable = [
        'kind',
        'authored_by',
        'performed_on',
        'started_at',
        'activity',
        'minutes',
        'distance_km',
        'intensity',
        'calories',
        'notes',
    ];

    /**
     * L'allenamento davvero fatto.
     *
     * Ogni conto calorico passa di qui, per la stessa ragione per cui i pasti
     * passano da `eaten()`: una seduta in programma per giovedì non ha
     * bruciato niente, e sommarla al fabbisogno di giovedì annuncia un margine
     * guadagnato con un allenamento che non è ancora stato fatto. Il numero
     * resta plausibile, quindi nessuno se ne accorge.
     */
    public function scopeDone(Builder $query): Builder
    {
        return $query->where('kind', 'done');
    }

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('kind', 'planned');
    }

    /** Proposta dal consulente, non decisa da una persona. */
    public function proposedByAssistant(): bool
    {
        return $this->authored_by === 'assistant';
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('position');
    }

    protected function casts(): array
    {
        return ['performed_on' => 'date', 'distance_km' => 'decimal:2'];
    }
}
