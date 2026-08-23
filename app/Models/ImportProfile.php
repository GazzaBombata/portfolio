<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportProfile extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'account_id', 'name', 'header_row', 'sheet_name', 'delimiter',
        'date_format', 'decimal_separator', 'thousands_separator', 'amount_mode', 'columns',
    ];

    protected function casts(): array
    {
        return ['columns' => 'array'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** The source column mapped to a field, or null when the file has none. */
    public function column(string $field): ?string
    {
        $value = $this->columns[$field] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
