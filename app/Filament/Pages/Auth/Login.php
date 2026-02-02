<?php

namespace App\Filament\Pages\Auth;

use App\Mail\TwoFactorCodeMail;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getImageComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getImageComponent(): Component
    {
        return \Filament\Forms\Components\Placeholder::make('')
            ->label('')
            ->content(new HtmlString('
                <div style="text-align: center; margin-bottom: 1rem;">
                    <img src="' . asset('images/suk.png') . '" alt="" class="login-logo-light" style="max-width: 100%; height: auto;" />
                    <img src="' . asset('images/suk-dark.png') . '" alt="" class="login-logo-dark" style="max-width: 100%; height: auto;" />
                </div>
                <style>
                    .login-logo-dark {
                        display: none;
                    }
                    .dark .login-logo-light {
                        display: none;
                    }
                    .dark .login-logo-dark {
                        display: block;
                    }
                </style>
            '));
    }

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

        // Check if 2FA is enabled globally BEFORE attempting login
        $twoFactorEnabled = Setting::get('two_factor_enabled_global', '0') === '1';

        if ($twoFactorEnabled) {
            // Use validate() instead of attempt() - only checks credentials, doesn't login
            $credentials = $this->getCredentialsFromFormData($data);

            if (! Filament::auth()->validate($credentials)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            // Get user without logging them in
            $user = \App\Models\User::where('email', $credentials['email'])->first();

            // Generate 6-digit code
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Save code and expiration to user
            $user->update([
                'two_factor_code' => $code,
                'two_factor_expires_at' => now()->addMinutes(10),
            ]);

            // Send email with code
            Mail::to($user->email)->send(new TwoFactorCodeMail($code, $user->name));

            // Store user ID and remember preference in session for verification page
            session([
                '2fa_user_id' => $user->id,
                '2fa_remember' => $data['remember'] ?? false,
            ]);

            // Redirect to 2FA verification page
            $this->redirect(route('two-factor.login'));
            return null;
        } else {
            // 2FA is NOT enabled, login normally
            if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            session()->regenerate();

            return app(LoginResponse::class);
        }
    }
}
