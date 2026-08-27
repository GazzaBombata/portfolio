<?php

use App\Auth\RememberedAppAuthentication;
use App\Auth\TrustedDevices;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * «Ricorda questo dispositivo» tocca l'autenticazione, quindi ogni confine va
 * dimostrato: che valga solo per chi l'ha guadagnato, solo entro la settimana,
 * e che non si rinnovi da solo.
 */
beforeEach(function () {
    $this->user = User::factory()->create([
        'app_authentication_secret' => encrypt('ABCDEFGHIJKLMNOP'),
    ]);
});

function fidati(User $user, ?callable $tocca = null): string
{
    $token = Str::random(64);

    $riga = TrustedDevice::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(TrustedDevices::DAYS),
    ]);

    if ($tocca !== null) {
        $tocca($riga);
    }

    return $token;
}

it('salta la sfida sul dispositivo che ha già superato il codice', function () {
    $token = fidati($this->user);

    request()->cookies->set(TrustedDevices::COOKIE, $token);

    expect(RememberedAppAuthentication::make()->isEnabled($this->user))->toBeFalse();
});

it('chiede il codice quando il cookie non c\'è', function () {
    expect(RememberedAppAuthentication::make()->isEnabled($this->user))->toBeTrue();
});

it('chiede il codice quando la settimana è finita', function () {
    $token = fidati($this->user, fn (TrustedDevice $d) => $d->forceFill(['expires_at' => now()->subMinute()])->save());

    request()->cookies->set(TrustedDevices::COOKIE, $token);

    expect(RememberedAppAuthentication::make()->isEnabled($this->user))->toBeTrue();
});

/*
 * Il confine fra le due persone che usano l'applicazione. Un token valido per
 * uno NON deve saltare la sfida all'altro: è la stessa riga che separa i loro
 * dati, e qui varrebbe di più perché salta un fattore di autenticazione.
 */
it('non lascia che il dispositivo di una persona valga per un\'altra', function () {
    $altra = User::factory()->create(['app_authentication_secret' => encrypt('QRSTUVWXYZABCDEF')]);
    $token = fidati($this->user);

    request()->cookies->set(TrustedDevices::COOKIE, $token);

    expect(RememberedAppAuthentication::make()->isEnabled($altra))->toBeTrue();
});

it('non tiene il token in chiaro nel database', function () {
    $token = fidati($this->user);

    expect(TrustedDevice::sole()->token_hash)->not->toBe($token)
        ->toBe(hash('sha256', $token));
});

/*
 * La scadenza non è scorrevole: usare il dispositivo non allunga la fiducia.
 * Se lo facesse, «al massimo una volta a settimana» diventerebbe «mai più»
 * proprio sul portatile che si usa tutti i giorni.
 */
it('non allunga la scadenza a ogni accesso', function () {
    $token = fidati($this->user);
    $scadenza = TrustedDevice::sole()->expires_at;

    request()->cookies->set(TrustedDevices::COOKIE, $token);
    TrustedDevices::trusts($this->user);

    expect(TrustedDevice::sole()->expires_at->timestamp)->toBe($scadenza->timestamp)
        // L'ultimo uso invece si aggiorna: serve a capire quali revocare.
        ->and(TrustedDevice::sole()->last_used_at)->not->toBeNull();
});

it('revoca tutti i dispositivi di una persona e lascia stare gli altri', function () {
    $altra = User::factory()->create(['app_authentication_secret' => encrypt('QRSTUVWXYZABCDEF')]);
    fidati($this->user);
    fidati($altra);

    expect(TrustedDevices::forgetAll($this->user))->toBe(1)
        ->and(TrustedDevice::count())->toBe(1)
        ->and(TrustedDevice::sole()->user_id)->toBe($altra->id);
});

it('resta obbligatorio per chi non ha mai configurato il secondo fattore', function () {
    $senza = User::factory()->create(['app_authentication_secret' => null]);
    $token = fidati($senza);

    request()->cookies->set(TrustedDevices::COOKIE, $token);

    // Il cookie non deve poter sostituire una configurazione che non c'è.
    expect(RememberedAppAuthentication::make()->isEnabled($senza))->toBeFalse();
});
