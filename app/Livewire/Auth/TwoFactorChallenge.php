<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Filament\Notifications\Notification;

class TwoFactorChallenge extends Component
{
    public string $code = '';
    public ?int $userId = null;

    public function mount()
    {
        // Get user ID from session
        $this->userId = session('2fa_user_id');

        // If no 2FA session, redirect to login
        if (!$this->userId) {
            // Clear any leftover 2FA session data
            session()->forget(['2fa_user_id', '2fa_remember']);
            return redirect()->route('filament.admin.auth.login');
        }
    }

    public function verify()
    {
        // Rate limiting: max 5 attempts per 10 minutes
        $rateLimitKey = '2fa_verify:' . $this->userId;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Notification::make()
                ->title('Превише покушаја')
                ->body("Покушајте поново за {$seconds} секунди.")
                ->danger()
                ->send();
            return;
        }

        RateLimiter::hit($rateLimitKey, 600); // 10 minutes decay

        $this->validate([
            'code' => 'required|digits:6',
        ]);

        $user = User::find($this->userId);

        if (!$user) {
            Notification::make()
                ->title('Неважећа сесија')
                ->danger()
                ->send();
            return redirect()->route('filament.admin.auth.login');
        }

        // Check if code matches and not expired (using hash_equals to prevent timing attacks)
        if (!hash_equals($user->two_factor_code ?? '', $this->code)) {
            Notification::make()
                ->title('Неважећи верификациони код')
                ->danger()
                ->send();
            $this->code = '';
            return;
        }

        if (now()->isAfter($user->two_factor_expires_at)) {
            Notification::make()
                ->title('Верификациони код је истекао')
                ->danger()
                ->send();
            $this->code = '';
            return;
        }

        // Clear rate limiter on successful verification
        RateLimiter::clear($rateLimitKey);

        // Clear 2FA code and session
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        // Get remember preference from session
        $remember = session('2fa_remember', false);

        // Clear 2FA session data
        session()->forget(['2fa_user_id', '2fa_remember']);

        // Log the user in with remember preference
        // This will trigger Login event and LogAuthenticationEvents listener will log it
        Auth::login($user, $remember);

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge')
            ->layout('components.layouts.guest', ['title' => 'Two-Factor Authentication']);
    }
}
