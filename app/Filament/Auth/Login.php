<?php

namespace App\Filament\Auth;

use App\Auth\TrustedDevices;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

/**
 * Il login, con l'aggiunta di «ricorda questo dispositivo».
 *
 * Il cookie si emette in un caso solo: il login in cui la sfida è stata
 * davvero superata. `userUndertakingMultiFactorAuthentication` è pieno solo
 * quando la pagina sta mostrando il campo del codice, quindi leggerlo PRIMA di
 * `parent::authenticate()` dice esattamente questo.
 *
 * Se lo si emettesse a ogni login riuscito, gli accessi che saltano la sfida
 * rinnoverebbero la propria scadenza da soli: la settimana non finirebbe mai
 * sul dispositivo che si usa tutti i giorni, che è il contrario di quello che
 * un limite serve a fare.
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $sfidaInCorso = filled($this->userUndertakingMultiFactorAuthentication);

        $risposta = parent::authenticate();

        if ($risposta !== null && $sfidaInCorso && ($utente = Filament::auth()->user()) !== null) {
            TrustedDevices::remember($utente);
        }

        return $risposta;
    }
}
