<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('non espone nessuna pagina di registrazione', function () {
    $this->get('/admin/register')->assertNotFound();
});

it('manda al login chi non è autenticato', function () {
    $this->get('/admin')->assertRedirect();
});

it('obbliga a configurare il secondo fattore chi non ce l\'ha', function () {
    $user = User::factory()->create(['app_authentication_secret' => null]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect('/admin/multi-factor-authentication/set-up');
});

/*
 * Il seme TOTP genera codici validi per sempre e i codici di recupero saltano
 * del tutto il secondo fattore: in un dump del database non devono essere
 * leggibili, altrimenti il secondo fattore protegge fino al primo backup.
 */
it('salva cifrati il segreto e i codici di recupero', function () {
    $user = User::factory()->create();
    $user->saveAppAuthenticationSecret('SEGRETOINCHIARO');
    $user->saveAppAuthenticationRecoveryCodes(['codice-uno', 'codice-due']);

    $stored = DB::table('users')->where('id', $user->id)->first();

    expect($stored->app_authentication_secret)->not->toContain('SEGRETOINCHIARO')
        ->and($stored->app_authentication_recovery_codes)->not->toContain('codice-uno')
        ->and($user->fresh()->getAppAuthenticationSecret())->toBe('SEGRETOINCHIARO')
        ->and($user->fresh()->getAppAuthenticationRecoveryCodes())->toBe(['codice-uno', 'codice-due']);
});
