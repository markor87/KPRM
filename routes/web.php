<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Filament\Pages\Auth\RegisterInvite;

// Custom 2FA verification route (Filament handles login, register, forgot-password)
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('two-factor.login');

    // Invite registration route
    Route::get('/register-invite/{token}', RegisterInvite::class)->name('register.invite');
});

// Filament handles all authenticated routes including logout
