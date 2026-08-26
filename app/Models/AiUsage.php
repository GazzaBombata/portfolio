<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'kind', 'model', 'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens', 'cost',
    ];

    protected function casts(): array
    {
        return ['cost' => 'decimal:6'];
    }
}
