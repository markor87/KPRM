<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use App\Notifications\ResetPassword as CyrillicResetPassword;
use Lab404\Impersonate\Events\TakeImpersonation;
use Lab404\Impersonate\Events\LeaveImpersonation;

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

        // ── Импресонација: строго READ-ONLY током прегледа туђе улоге ──
        // Док Super Admin „гледа као" други корисник, забрани СВЕ мутационе способности
        // (и Shield-стил „Create:Model" и policy-method стил „create"). Прегледне
        // способности (View/ViewAny/view/viewAny) пролазе. Ефекат: Filament сам сакрива
        // акције за измену/брисање/креирање, а те странице враћају 403.
        Gate::before(function ($user, string $ability) {
            if (! app('impersonate')->isImpersonating()) {
                return null;
            }

            $policyMutations = [
                'create', 'update', 'delete', 'deleteAny', 'restore', 'restoreAny',
                'forceDelete', 'forceDeleteAny', 'replicate', 'reorder',
            ];

            $shieldPrefixi = [
                'Create:', 'Update:', 'Delete:', 'DeleteAny:', 'Restore:', 'RestoreAny:',
                'ForceDelete:', 'ForceDeleteAny:', 'Replicate:', 'Reorder:',
            ];

            if (in_array($ability, $policyMutations, true) || Str::startsWith($ability, $shieldPrefixi)) {
                return false; // забрани мутацију током импресонације
            }

            return null; // не дирај остале провере
        });

        // Аудит: улазак/излазак из импресонације у Евиденцију активности.
        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event) {
            // AuthenticateSession middleware чува хеш лозинке у сесији и одјављује на
            // промену корисника. Ускладимо хеш са импресонираним корисником да не избаци.
            session()->put('password_hash_web', $event->impersonated->getAuthPassword());

            activity('impersonation')
                ->performedOn($event->impersonated)
                ->causedBy($event->impersonator)
                ->withProperties(['impersonated_email' => $event->impersonated->email])
                ->tap(fn ($activity) => $activity->ip_address = request()->ip())
                ->log('Ušao u ulogu korisnika ' . $event->impersonated->email);
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event) {
            // Врати хеш лозинке на стварног корисника (Super Admina) при изласку.
            session()->put('password_hash_web', $event->impersonator->getAuthPassword());

            activity('impersonation')
                ->performedOn($event->impersonated)
                ->causedBy($event->impersonator)
                ->withProperties(['impersonated_email' => $event->impersonated->email])
                ->tap(fn ($activity) => $activity->ip_address = request()->ip())
                ->log('Izašao iz uloge korisnika ' . $event->impersonated->email);
        });

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
