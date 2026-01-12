<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\TwoFactorChallenge;

// Custom 2FA verification route (Filament handles login, register, forgot-password)
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('two-factor.login');
});

// Filament handles all authenticated routes including logout
