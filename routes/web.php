<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\Auth\RegisterInvite;

Route::middleware('guest')->group(function () {
    // Invite registration route
    Route::get('/register-invite/{token}', RegisterInvite::class)->name('register.invite');
});

// Filament handles all authenticated routes including logout
