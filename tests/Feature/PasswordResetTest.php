<?php

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('espone la pagina di richiesta reset', function () {
    $this->get('/admin/password-reset/request')->assertSuccessful();
});

it('invia il link di reset a chi ha un account', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'giorgio@example.test']);

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request');

    Notification::assertSentTo($user, ResetPassword::class);
});

/*
 * Un reset che si comporta diversamente su un indirizzo sconosciuto trasforma
 * la pagina in un modo per scoprire chi ha un account qui.
 */
it('non manda niente a un indirizzo che non ha un account', function () {
    Notification::fake();
    User::factory()->create(['email' => 'esiste@example.test']);

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => 'mai-visto@example.test'])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertNothingSent();
});

it('parla italiano', function () {
    expect(app()->getLocale())->toBe('it');
});
