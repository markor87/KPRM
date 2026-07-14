<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registruj middleware aliase
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'super.admin' => \App\Http\Middleware\CheckSuperAdmin::class,
        ]);

        // Aplikacija je iza reverse proxy-ja (10.2.39.21). Bez ovoga request()->ip()
        // vraca adresu proxy-ja za sve korisnike, pa svi u evidenciji aktivnosti
        // imaju isti IP. Verujemo iskljucivo toj adresi - sa 'at: *' bi bilo ko mogao
        // da posalje lazno X-Forwarded-For zaglavlje i upise tudji IP u evidenciju.
        $middleware->trustProxies(
            at: ['10.2.39.21'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
