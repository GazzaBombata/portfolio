<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodyMetric extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'body_metrics';

    protected $fillable = [
        'measured_on',
        'weight_kg',
        'body_fat_pct',
        'muscle_mass_kg',
        'resting_hr',
        'notes',
    ];

    protected function casts(): array
    {
        return ['measured_on' => 'date', 'weight_kg' => 'decimal:2', 'body_fat_pct' => 'decimal:1', 'muscle_mass_kg' => 'decimal:2'];
    }
}
