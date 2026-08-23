<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['logged_on', 'water_litres', 'nutrition_adherence', 'notes'];

    protected function casts(): array
    {
        return ['logged_on' => 'date', 'water_litres' => 'decimal:2'];
    }
}
