<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public $email = '';

    public $emailSent = false;

    public function sendResetLink()
    {
        $this->validate();

        $status = Password::sendResetLink(
            ['email' => $this->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            $this->emailSent = true;
            $this->reset('email');
        } else {
            $this->addError('email', 'Ne možemo pronaći korisnika sa ovom email adresom.');
        }
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
