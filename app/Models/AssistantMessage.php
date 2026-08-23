<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['role', 'content', 'steps', 'status'];

    protected function casts(): array
    {
        return ['steps' => 'array'];
    }
}
