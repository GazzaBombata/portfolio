<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            /*
             * Recupero password via email.
             *
             * Senza, l'unico modo per rientrare dopo aver perso la password è
             * un comando sul server — e su questo server c'è la produzione di
             * un'altra applicazione, che non è un posto dove andare a frugare
             * per riprendersi il proprio account.
             */
            ->passwordReset()
            /*
             * Two-factor authentication, required rather than offered.
             *
             * Behind this login there are bank statements and health records
             * for two people. A password alone is one leaked reuse away from
             * all of it, so the second factor is not a setting someone can
             * forget to switch on: `isRequired` sends anyone without it to the
             * set-up screen at their next login.
             */
            ->multiFactorAuthentication(
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName('Giorgio Giotto'),
                isRequired: true,
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            /*
             * I riquadri della dashboard vengono scoperti da soli in
             * app/Filament/Widgets. Qui non resta nessuno di quelli
             * dimostrativi di Filament: occupavano il posto migliore della
             * pagina per dire come ti chiami e che versione gira.
             */
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
