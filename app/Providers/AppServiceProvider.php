<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\DatePicker;
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

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
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
