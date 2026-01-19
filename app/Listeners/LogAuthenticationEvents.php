<?php

namespace App\Listeners;

use Spatie\Activitylog\Facades\Activity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogAuthenticationEvents
{
    private static $loggedEvents = [];

    /**
     * Prevent duplicate logs within same request
     */
    private function isDuplicate(string $eventType, string $identifier): bool
    {
        $key = $eventType . ':' . $identifier;

        if (isset(self::$loggedEvents[$key])) {
            return true;
        }

        self::$loggedEvents[$key] = true;
        return false;
    }

    /**
     * Handle the Login event
     */
    public function handleLogin(Login $event): void
    {
        if ($this->isDuplicate('login', $event->user->id)) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'email' => $event->user->email,
                'guard' => $event->guard,
                'remember' => $event->remember,
            ])
            ->tap(function ($activity) {
                $activity->ip_address = Request::ip();
            })
            ->log('Uspešan login');
    }

    /**
     * Handle the Logout event
     */
    public function handleLogout(Logout $event): void
    {
        if ($this->isDuplicate('logout', $event->user->id)) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'email' => $event->user->email,
                'guard' => $event->guard,
            ])
            ->tap(function ($activity) {
                $activity->ip_address = Request::ip();
            })
            ->log('Logout');
    }

    /**
     * Handle the Failed event
     */
    public function handleFailed(Failed $event): void
    {
        $identifier = ($event->credentials['email'] ?? 'unknown') . '_' . Request::ip();

        if ($this->isDuplicate('failed', $identifier)) {
            return;
        }

        activity('auth')
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'unknown',
                'guard' => $event->guard,
                'attempted_at' => now()->toDateTimeString(),
            ])
            ->tap(function ($activity) {
                $activity->ip_address = Request::ip();
            })
            ->log('Neuspešan pokušaj prijave');
    }
}
