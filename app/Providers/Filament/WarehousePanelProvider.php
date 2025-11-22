<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Http\Middleware\CheckAccountStatus;
use App\Http\Middleware\SecurityHeaders;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class WarehousePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('warehouse')
            ->path('warehouse')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)

            // ========== BRANDING ==========
            ->brandName('Warex Management')
            ->brandLogo(asset('images/logo.svg'))
            ->darkModeBrandLogo(asset('images/logo-dark.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))

            // ========== COLORS ==========
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])

            // ========== UI SETTINGS ==========
            ->font('Inter')
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->collapsedSidebarWidth('4rem')
            ->topNavigation(false)

            // ========== NAVIGATION GROUPS ==========
            ->navigationGroups([
                'User Management',
                'Warehouse',
                'Reports',
                'Settings',
            ])

            // ========== RESOURCES & PAGES ==========
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])

            // ========== WIDGETS ==========
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])

            // ========== MIDDLEWARE ==========
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
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckAccountStatus::class,
            ])

            // ========== NOTIFICATIONS ==========
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')

            // ========== SPA MODE ==========
            ->spa();
    }
}
