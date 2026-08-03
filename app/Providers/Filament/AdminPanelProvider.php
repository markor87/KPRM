<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Controllers\KorisnickoUputstvoController;
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
            ->authenticatedRoutes(function () {
                Route::get('/korisnicko-uputstvo/pdf', [KorisnickoUputstvoController::class, 'pdf'])
                    ->name('korisnicko-uputstvo.pdf');
                Route::get('/korisnicko-uputstvo/video', [KorisnickoUputstvoController::class, 'videoStream'])
                    ->name('korisnicko-uputstvo.video');
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
            HTML))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    @keyframes kprm-hint-pulse {
                        0%, 100% { transform: scale(1);    box-shadow: 0 0 0 0 rgba(251,191,36,.55); }
                        50%      { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(251,191,36,0); }
                    }
                    .kprm-hint-pulse {
                        animation: kprm-hint-pulse 1.8s ease-in-out infinite;
                        transform-origin: center;
                        border-radius: 9999px;
                    }
                    /* иста боја иконице на обе теме (амбер са црне теме) */
                    .kprm-hint-pulse svg { color: #fbbf24 !important; }
                    /* мирна на прелаз миша да се лакше кликне */
                    .kprm-hint-pulse:hover { animation-play-state: paused; }
                    @media (prefers-reduced-motion: reduce) {
                        .kprm-hint-pulse { animation: none; }
                    }
                    /* Дозволи преламање дугог badge-а у колони „Исход конкурса" */
                    .kprm-badge-wrap .fi-badge {
                        white-space: normal;
                        text-align: left;
                    }
                </style>
            HTML))
            ->renderHook(PanelsRenderHook::BODY_START, function (): string {
                if (! app('impersonate')->isImpersonating()) {
                    return '';
                }

                $user = auth()->user();
                $ime = e($user?->name);
                $email = e($user?->email);
                $leave = route('impersonate.leave');

                return <<<HTML
                    <div style="position:sticky;top:0;z-index:9999;background:#b45309;color:#fff;padding:8px 16px;display:flex;align-items:center;justify-content:center;gap:16px;font-size:.875rem;flex-wrap:wrap;">
                        <span>🔍 Прегледаш као <strong>{$ime}</strong> ({$email}) — <strong>само за преглед</strong></span>
                        <a href="{$leave}" style="background:#fff;color:#b45309;padding:4px 12px;border-radius:6px;font-weight:600;text-decoration:none;white-space:nowrap;">Изађи из прегледа</a>
                    </div>
                HTML;
            });
    }
}
