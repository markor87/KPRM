<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\DatePicker;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Globalna konfiguracija DatePicker komponenti
        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->native(false)           // Koristi Filament kalendar
                ->displayFormat('d/m/Y')  // dd/mm/yyyy format
                ->format('Y-m-d');        // Baza koristi ISO format (yyyy-mm-dd)
        });
    }
}
