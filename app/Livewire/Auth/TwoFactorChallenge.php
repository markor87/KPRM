<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('filament.admin.auth.login');
        }
    }

    public function verify()
    {
        $this->validate([
            'code' => 'required|digits:6',
        ]);

        $user = User::find($this->userId);

        if (!$user) {
            Notification::make()
                ->title('Invalid session')
                ->danger()
                ->send();
            return redirect()->route('filament.admin.auth.login');
        }

        // Check if code matches and not expired
        if ($user->two_factor_code !== $this->code) {
            Notification::make()
                ->title('Invalid verification code')
                ->danger()
                ->send();
            $this->code = '';
            return;
        }

        if (now()->isAfter($user->two_factor_expires_at)) {
            Notification::make()
                ->title('Verification code has expired')
                ->danger()
                ->send();
            $this->code = '';
            return;
        }

        // Clear 2FA code and session
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        session()->forget('2fa_user_id');

        // Log the user in
        Auth::login($user);

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge')
            ->layout('components.layouts.guest', ['title' => 'Two-Factor Authentication']);
    }
}
