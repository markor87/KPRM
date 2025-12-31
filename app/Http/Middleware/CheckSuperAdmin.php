<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Morate biti prijavljeni.');
        }

        // Proveri da li korisnik ima Super Admin rolu (Spatie Permission)
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Ova stranica je dostupna samo Super Adminima.');
        }

        return $next($request);
    }
}
