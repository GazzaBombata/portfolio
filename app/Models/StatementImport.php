<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatementImport extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'account_id', 'filename', 'disk_path', 'period_start', 'period_end',
        'rows_total', 'rows_imported', 'rows_duplicate', 'rows_failed', 'status', 'error',
    ];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
