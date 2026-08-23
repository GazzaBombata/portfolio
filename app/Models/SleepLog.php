<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SleepLog extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'sleep_logs';

    protected $fillable = [
        'night_of',
        'fell_asleep_at',
        'woke_up_at',
        'minutes',
        'quality',
        'awakenings',
        'notes',
    ];

    protected function casts(): array
    {
        return ['night_of' => 'date', 'fell_asleep_at' => 'datetime', 'woke_up_at' => 'datetime'];
    }
}
