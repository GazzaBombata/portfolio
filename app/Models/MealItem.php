<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Observers\MealItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un ingrediente dentro un pasto.
 *
 * Esiste perché il totale di un pasto smetta di essere una cifra che qualcuno
 * ha detto e diventi una somma che qualcuno può controllare.
 */
#[ObservedBy(MealItemObserver::class)]
class MealItem extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'meal_items';

    protected $fillable = [
        'meal_id', 'position', 'name', 'quantity',
        'calories', 'protein_g', 'carbs_g', 'fat_g',
    ];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    /** Come si legge in una riga: «olio extravergine · 3 cucchiai · 270 kcal». */
    public function summary(): string
    {
        return collect([
            $this->name,
            $this->quantity,
            $this->calories !== null ? "{$this->calories} kcal" : null,
        ])->filter()->implode(' · ');
    }
}
