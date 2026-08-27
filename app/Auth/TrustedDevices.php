<?php

namespace App\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * «Su questo dispositivo il codice l'ho già messo, non chiedermelo ogni volta.»
 *
 * Come funziona: superata la sfida, il browser riceve un cookie con un token
 * casuale; in tabella ne resta solo l'impronta SHA-256, con una scadenza. Al
 * login successivo, se il cookie corrisponde a una riga ancora valida di
 * QUELL'utente, la sfida si salta.
 *
 * Tre scelte che tengono in piedi la cosa:
 *
 * - **La fiducia si guadagna solo con un codice.** Il cookie si emette
 *   soltanto nel login in cui la sfida è stata davvero superata. Se lo si
 *   riemettesse anche negli accessi che la saltano, la scadenza si
 *   rinnoverebbe da sola e «una volta a settimana» diventerebbe «mai più».
 * - **Il token sta in chiaro solo nel browser.** Chi legge il database non
 *   trova niente con cui entrare.
 * - **È legato all'utente.** Un token valido per una persona non salta la
 *   sfida a un'altra: sono due account su questa applicazione, ed è la stessa
 *   riga di confine che vale per i dati.
 *
 * Resta un cookie: chi ha accesso fisico al portatile sbloccato entra senza
 * codice per una settimana. È il compromesso che ogni «ricorda questo
 * dispositivo» accetta, e per questo la finestra è corta e revocabile.
 */
class TrustedDevices
{
    public const COOKIE = 'dispositivo_fidato';

    /** Quanto dura la fiducia. Una settimana, come chiesto. */
    public const DAYS = 7;

    /** C'è un cookie valido per questo utente? */
    public static function trusts(Authenticatable $user): bool
    {
        $riga = static::current($user);

        if ($riga === null) {
            return false;
        }

        // Serve a vedere quali dispositivi sono ancora in uso quando si va a
        // revocarli: una lista di token senza date non dice niente a nessuno.
        $riga->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    /** Emessa dopo una sfida superata, e solo allora. */
    public static function remember(Authenticatable $user): void
    {
        $token = Str::random(64);

        TrustedDevice::create([
            'user_id' => $user->getAuthIdentifier(),
            'token_hash' => hash('sha256', $token),
            'label' => Str::limit((string) request()->userAgent(), 250, ''),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(static::DAYS),
        ]);

        Cookie::queue(Cookie::make(
            name: static::COOKIE,
            value: $token,
            minutes: static::DAYS * 24 * 60,
            secure: ! app()->environment('local'),
            httpOnly: true,
            sameSite: 'lax',
        ));
    }

    /**
     * Revoca tutti i dispositivi di una persona.
     *
     * Il gesto che serve quando un portatile si perde, e l'unico modo di
     * chiudere la finestra prima della scadenza.
     */
    public static function forgetAll(User $user): int
    {
        Cookie::queue(Cookie::forget(static::COOKIE));

        return TrustedDevice::query()->where('user_id', $user->getKey())->delete();
    }

    /** Le righe ancora valide, per mostrarle a chi le vuole revocare. */
    public static function activeFor(User $user): int
    {
        return TrustedDevice::query()
            ->where('user_id', $user->getKey())
            ->where('expires_at', '>', now())
            ->count();
    }

    private static function current(Authenticatable $user): ?TrustedDevice
    {
        $token = request()->cookie(static::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return TrustedDevice::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }
}
