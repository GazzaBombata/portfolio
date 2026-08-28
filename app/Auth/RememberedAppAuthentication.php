<?php

namespace App\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Il secondo fattore, che però non si ripete su un dispositivo già verificato.
 *
 * `isEnabled()` in Filament risponde a DUE domande diverse con lo stesso
 * metodo: «devo fare la sfida adesso?» al login, e «questa persona il secondo
 * fattore ce l'ha configurato?» dappertutto altrove — il middleware
 * `EnsureMultiFactorAuthenticationIsEnabled`, la pagina di configurazione
 * obbligatoria, il profilo. Rispondere «no» alla prima significava rispondere
 * «no» anche alla seconda: sul dispositivo fidato il pannello concludeva che
 * il secondo fattore non c'era e rimandava alla schermata di configurazione,
 * cioè faceva il contrario di quello che «ricorda questo dispositivo» promette.
 * È successo in produzione il 28/08/2026.
 *
 * Quindi la risposta è vera ovunque tranne che nel punto esatto in cui la
 * domanda è l'altra: il login la delimita con `whileDecidingTheChallenge()`,
 * e solo lì dentro un dispositivo fidato vale come sfida già superata.
 *
 * Attenzione a cosa NON cambia: il secondo fattore resta obbligatorio da
 * configurare (`isRequired`) e resta obbligatorio da superare la prima volta
 * su ogni dispositivo nuovo. Quello che si salta è solo la ripetizione entro
 * la settimana.
 */
class RememberedAppAuthentication extends AppAuthentication
{
    /** Vero solo mentre il login sta scegliendo se fare la sfida. */
    private static bool $decidingTheChallenge = false;

    /**
     * Delimita la domanda «devo chiedere il codice a questo accesso?».
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function whileDecidingTheChallenge(callable $callback): mixed
    {
        static::$decidingTheChallenge = true;

        try {
            return $callback();
        } finally {
            static::$decidingTheChallenge = false;
        }
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (! parent::isEnabled($user)) {
            return false;
        }

        // Fuori dal login la domanda è «ce l'ha configurato?», e la risposta
        // è sì: un cookie non può far sparire un segreto che esiste.
        if (! static::$decidingTheChallenge) {
            return true;
        }

        return ! TrustedDevices::trusts($user);
    }
}
