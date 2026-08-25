<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'logged_on', 'water_litres', 'steps', 'nutrition_adherence', 'notes',
        'target_calories', 'target_protein_g', 'planned_meals', 'activity_calories', 'targets_manual',
    ];

    protected function casts(): array
    {
        return ['logged_on' => 'date', 'water_litres' => 'decimal:2', 'targets_manual' => 'boolean'];
    }
}
