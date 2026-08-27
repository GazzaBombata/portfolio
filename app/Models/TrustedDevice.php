<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un dispositivo su cui il secondo fattore è già stato superato.
 *
 * Volutamente SENZA il trait BelongsToUser: queste righe vanno lette quando
 * non c'è ancora nessun utente autenticato — è tutto il punto, si guarda
 * proprio per decidere se chiedere il codice. Lo scoping qui lo fa la query,
 * che parte sempre dall'id dell'utente che sta entrando.
 */
class TrustedDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'label', 'last_used_at', 'expires_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
