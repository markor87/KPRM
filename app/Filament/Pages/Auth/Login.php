<?php

namespace App\Filament\Pages\Auth;

use App\Mail\TwoFactorCodeMail;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        // Get the authenticated user
        $user = Filament::auth()->user();

        // Check if 2FA is enabled globally
        $twoFactorEnabled = Setting::get('two_factor_enabled_global', '0') === '1';

        // If 2FA is enabled globally, generate code and redirect to verification
        if ($twoFactorEnabled) {
            // Logout the user temporarily
            Filament::auth()->logout();

            // Generate 6-digit code
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Save code and expiration to user
            $user->update([
                'two_factor_code' => $code,
                'two_factor_expires_at' => now()->addMinutes(10),
            ]);

            // Send email with code
            Mail::to($user->email)->send(new TwoFactorCodeMail($code, $user->name));

            // Store user ID in session for verification page
            session(['2fa_user_id' => $user->id]);

            // Redirect to 2FA verification page
            $this->redirect(route('two-factor.login'));
            return null;
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
