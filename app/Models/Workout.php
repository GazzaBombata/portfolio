<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'workouts';

    protected $fillable = [
        'performed_on',
        'started_at',
        'activity',
        'minutes',
        'distance_km',
        'sets',
        'reps',
        'load_kg',
        'intensity',
        'calories',
        'notes',
    ];

    protected function casts(): array
    {
        return ['performed_on' => 'date', 'distance_km' => 'decimal:2', 'load_kg' => 'decimal:2'];
    }
}
