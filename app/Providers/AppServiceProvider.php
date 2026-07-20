<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use App\Notifications\ResetPassword as CyrillicResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Filament reset-password нотификацију замени ћириличном, синхроном верзијом
        $this->app->bind(FilamentResetPassword::class, CyrillicResetPassword::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Vremena se u bazi cuvaju u UTC, a prikazuju u lokalnoj zoni (DST-svesno).
        // Postavlja default zonu za sve Filament dateTime kolone/entry-je/pickere.
        FilamentTimezone::set(config('app.display_timezone'));

        if (config('app.env') === 'production') {
            // Генерисање URL-ова увек преко https
            URL::forceScheme('https');

            // Продукција је иза TLS-терминације која апликацији не шаље ни X-Forwarded-Proto,
            // па Laravel захтев види као http. То руши потпис signed URL-ова (нпр. ресет лозинке → 403).
            // Натерамо да се захтев третира као безбедан да би $request->url() био https.
            if (! $this->app->runningInConsole()) {
                $this->app['request']->server->set('HTTPS', 'on');
            }
        }

        // Globalna konfiguracija DatePicker komponenti
        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->native(false)           // Koristi Filament kalendar
                ->displayFormat('d/m/Y')  // dd/mm/yyyy format
                ->format('Y-m-d');        // Baza koristi ISO format (yyyy-mm-dd)
        });
    }
}
