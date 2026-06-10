<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
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
use Andreia\FilamentNordTheme\FilamentNordThemePlugin;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Support\Facades\Route;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->passwordReset()
            ->brandName('КПРМ')
            ->favicon(secure_asset('images/favicon.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets će biti automatski otkriveni kroz discoverWidgets()
            ])
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
            ->routes(function () {
                Route::get('/two-factor-challenge', TwoFactorChallenge::class)
                    ->name('two-factor-challenge');
            })
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugin(FilamentNordThemePlugin::make())
            ->plugin(FilamentShieldPlugin::make())
            ->plugin(FilamentApexChartsPlugin::make())
            ->renderHook(PanelsRenderHook::BODY_END, fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    document.addEventListener('click', function (e) {
                        const tab = e.target.closest('[role="tab"]');
                        if (tab) {
                            setTimeout(() => tab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }), 10);
                        }
                    });
                </script>
            HTML));
    }
}
