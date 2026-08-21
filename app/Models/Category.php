<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['parent_id', 'name', 'color', 'icon', 'kind', 'position'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CategoryRule::class);
    }

    /** "Casa · Bollette", so a category is identifiable without its tree. */
    public function fullName(): string
    {
        return $this->parent === null ? $this->name : $this->parent->name.' · '.$this->name;
    }
}
