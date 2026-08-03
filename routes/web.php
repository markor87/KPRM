<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\Auth\RegisterInvite;

Route::middleware('guest')->group(function () {
    // Invite registration route
    Route::get('/register-invite/{token}', RegisterInvite::class)->name('register.invite');
});

// Impersonacija (lab404): rute /impersonate/take/{id} i /impersonate/leave.
// Ko sme koga -> User::canImpersonate()/canBeImpersonated(); read-only garantuje
// Gate::before u AppServiceProvider.
Route::middleware('auth')->group(function () {
    Route::impersonate();
});

// Filament handles all authenticated routes including logout
