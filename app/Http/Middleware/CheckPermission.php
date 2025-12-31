<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Format: 'table_name.action'
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Proveri da li je korisnik ulogovan
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Morate biti prijavljeni.');
        }

        // Proveri da li korisnik ima permis (Spatie Permission)
        if (!auth()->user()->hasPermissionTo($permission)) {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        return $next($request);
    }
}
