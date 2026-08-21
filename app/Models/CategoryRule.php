<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CategoryRule extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['category_id', 'pattern', 'match_type', 'priority', 'auto_learned', 'times_applied'];

    protected function casts(): array
    {
        return ['auto_learned' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * A regex rule is the only one that can be written wrong in a way that
     * throws at match time, so it is guarded here rather than at every call.
     */
    public function matches(string $description): bool
    {
        $haystack = Str::lower($description);
        $needle = Str::lower($this->pattern);

        return match ($this->match_type) {
            'exact' => $haystack === $needle,
            'starts_with' => str_starts_with($haystack, $needle),
            'regex' => @preg_match('/'.$this->pattern.'/i', $description) === 1,
            default => str_contains($haystack, $needle),
        };
    }
}
