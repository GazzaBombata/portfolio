<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'meals';

    protected $fillable = [
        'kind',
        'eaten_on',
        'moment',
        'eaten_at',
        'description',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'nutrition_estimated',
        'eaten_out',
        'notes',
    ];

    /**
     * Il cibo davvero mangiato.
     *
     * Ogni conto calorico passa di qui. Dimenticarlo somma il piano al
     * consumato e raddoppia la giornata — un errore che non si vede, perché il
     * numero resta plausibile.
     */
    public function scopeEaten(Builder $query): Builder
    {
        return $query->where('kind', 'eaten');
    }

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('kind', 'planned');
    }

    protected function casts(): array
    {
        return ['eaten_on' => 'date', 'nutrition_estimated' => 'boolean', 'eaten_out' => 'boolean'];
    }
}
