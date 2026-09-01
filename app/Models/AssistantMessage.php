<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['topic', 'model', 'role', 'content', 'steps', 'status', 'out_of_rounds'];

    protected function casts(): array
    {
        return ['steps' => 'array', 'out_of_rounds' => 'boolean'];
    }
}
