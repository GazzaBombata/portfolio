<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['name', 'bank', 'iban_last4', 'currency', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(StatementImport::class);
    }
}
