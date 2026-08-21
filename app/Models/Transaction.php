<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'account_id', 'category_id', 'statement_import_id', 'booked_on', 'valued_on',
        'amount', 'currency', 'raw_description', 'description', 'counterparty',
        'fingerprint', 'occurrence', 'category_locked', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'booked_on' => 'date',
            'valued_on' => 'date',
            'amount' => 'decimal:2',
            'category_locked' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(StatementImport::class, 'statement_import_id');
    }

    /**
     * The identity of a statement line, for spotting a row already imported.
     *
     * The description is normalised first — banks pad and re-space the same
     * merchant differently between exports, and an unnormalised hash would call
     * the same transaction new every month.
     */
    public static function fingerprintFor(int $accountId, string $bookedOn, string $amount, string $rawDescription): string
    {
        $normalised = Str::of($rawDescription)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->replaceMatches('/[^a-z0-9 ]/', '')
            ->trim()
            ->value();

        return sha1(implode('|', [$accountId, $bookedOn, $amount, $normalised]));
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('booked_on', [$from, $to]);
    }

    public function scopeUncategorised(Builder $query): Builder
    {
        return $query->whereNull('category_id');
    }
}
