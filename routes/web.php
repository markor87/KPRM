<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\Auth\RegisterInvite;
use App\Filament\Resources\UserResource;
use Filament\Facades\Filament;

Route::middleware('guest')->group(function () {
    // Invite registration route
    Route::get('/register-invite/{token}', RegisterInvite::class)->name('register.invite');
});

// Impersonacija (lab404): sopstvene rute da bismo kontrolisali redirect na izlazu.
// Ko sme koga -> User::canImpersonate()/canBeImpersonated(); read-only garantuje
// Gate::before u AppServiceProvider.
Route::middleware('auth')->group(function () {
    // Ulazak u pregled tudje uloge.
    Route::get('/impersonate/take/{id}', function (string $id) {
        $from = auth()->user();
        $to = app('impersonate')->findUserById($id, 'web');

        abort_unless(
            $from && $to && $from->isNot($to) && $from->canImpersonate() && $to->canBeImpersonated(),
            403,
        );

        $from->impersonate($to, 'web');

        return redirect(Filament::getUrl());
    })->name('impersonate');

    // Izlazak iz pregleda -> nazad na listu svih korisnika (ne na kontrolnu tablu).
    Route::get('/impersonate/leave', function () {
        if (auth()->check()) {
            auth()->user()->leaveImpersonation();
        }

        return redirect(UserResource::getUrl('index'));
    })->name('impersonate.leave');
});

// Filament handles all authenticated routes including logout
