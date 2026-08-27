<?php

namespace App\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Il secondo fattore, che però non si ripete su un dispositivo già verificato.
 *
 * Filament decide se fare la sfida chiedendo `isEnabled()`: rispondere «no»
 * quando il dispositivo è fidato è tutto quello che serve, e lascia intatto il
 * resto del meccanismo — configurazione, codici di recupero, limite ai
 * tentativi.
 *
 * Attenzione a cosa NON cambia: il secondo fattore resta obbligatorio da
 * configurare (`isRequired`) e resta obbligatorio da superare la prima volta
 * su ogni dispositivo nuovo. Quello che si salta è solo la ripetizione entro
 * la settimana.
 */
class RememberedAppAuthentication extends AppAuthentication
{
    public function isEnabled(Authenticatable $user): bool
    {
        if (! parent::isEnabled($user)) {
            return false;
        }

        return ! TrustedDevices::trusts($user);
    }
}
