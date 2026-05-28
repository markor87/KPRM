<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspiciousLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ip,
        public string $country,
        public string $city,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Пријава са стране локације — КПРМ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.suspicious-login',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
