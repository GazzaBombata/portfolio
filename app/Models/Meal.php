<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'meals';

    protected $fillable = [
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

    protected function casts(): array
    {
        return ['eaten_on' => 'date', 'nutrition_estimated' => 'boolean', 'eaten_out' => 'boolean'];
    }
}
