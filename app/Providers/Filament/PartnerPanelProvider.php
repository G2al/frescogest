<?php

namespace App\Providers\Filament;

use App\Filament\Partner\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PartnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('partner')
            ->path('partner')
            ->authGuard('admin')
            ->login()
            ->brandName('Area partner')
            ->brandLogo(asset('assets/images/new-logo-primary.png'))
            ->darkModeBrandLogo(asset('assets/images/new-logo-white.png'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('assets/images/icona-web.png'))
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.admin-login-branding')->render(),
            )
            ->colors(['primary' => '#007060'])
            ->discoverResources(
                in: app_path('Filament/Partner/Resources'),
                for: 'App\Filament\Partner\Resources',
            )
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
