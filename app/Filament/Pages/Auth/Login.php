<?php

namespace App\Filament\Pages\Auth;

use App\Mail\TwoFactorCodeMail;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Checkbox;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Pre-fill email from cookie if it exists
        if ($rememberedEmail = request()->cookie('remembered_email')) {
            $this->form->fill([
                'email' => $rememberedEmail,
                'remember_email' => true,
            ]);
        }
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getImageComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberEmailComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getRememberEmailComponent(): Component
    {
        return Checkbox::make('remember_email')
            ->label('Запамти е-пошту');
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

        // Handle "Remember Email" cookie
        if ($data['remember_email'] ?? false) {
            // Save email to cookie for 30 days
            cookie()->queue('remembered_email', $data['email'], 43200); // 30 days in minutes
        } else {
            // Remove the cookie if unchecked
            cookie()->queue(cookie()->forget('remembered_email'));
        }

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

            // Store only user ID in session (no remember)
            session(['2fa_user_id' => $user->id]);

            // Redirect to 2FA verification page
            $this->redirect(route('two-factor.login'));
            return null;
        } else {
            // 2FA is NOT enabled, login normally WITHOUT remember me
            if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), false)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            session()->regenerate();

            return app(LoginResponse::class);
        }
    }
}
