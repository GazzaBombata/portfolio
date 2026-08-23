<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Everything in this application belongs to one person: their money, their
 * nights, their meals. This trait is what keeps two people's data apart.
 *
 * It does two things — scopes every query to the current user, and stamps
 * `user_id` on create so a forgotten assignment cannot silently file a row
 * under nobody.
 *
 * The important decision is what happens with no authenticated user. The scope
 * fails CLOSED: it returns nothing rather than everything. A query that runs in
 * a job or a console command with no user set is a bug either way, but one
 * version of that bug shows an empty list and the other shows someone else's
 * bank statement.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('user', function (Builder $query): void {
            $id = Auth::id();

            if ($id === null) {
                // Not "no filter" — no rows. See the class docblock.
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($query->getModel()->getTable().'.user_id', $id);
        });

        static::creating(function (self $model): void {
            if ($model->user_id !== null) {
                return;
            }

            $id = Auth::id();

            if ($id === null) {
                throw new RuntimeException(
                    static::class.': impossibile creare una riga senza utente. '
                    .'Se il codice gira in un job o in un comando, imposta prima l\'utente con Auth::setUser().'
                );
            }

            $model->user_id = $id;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Deliberately step outside the scope — for a job that has to work across
     * users, or for a maintenance command. Spelled out at the call site so it
     * shows up in a review, which `withoutGlobalScope('user')` scattered in a
     * repository does not.
     */
    public static function acrossAllUsers(): Builder
    {
        return static::query()->withoutGlobalScope('user');
    }
}
