<?php

namespace App\Notifications;

use Filament\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseNotification
{
    /**
     * Шаљи синхроно (као 2FA мејл) — не преко queue реда,
     * да би стигао и без покренутог queue worker-а.
     */
    public $connection = 'sync';

    /**
     * Ћирилични мејл за ресетовање лозинке (custom blade, Outlook-компатибилан).
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        $count = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Ресетовање лозинке — КПРМ')
            ->view('emails.reset-password', [
                'url' => $url,
                'count' => $count,
                'userName' => $notifiable->name ?? null,
            ]);
    }
}
