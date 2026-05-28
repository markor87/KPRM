<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspiciousLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $userEmail,
        public string $ip,
        public string $country,
        public string $city,
        public bool $successful,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->successful
            ? '⚠️ Пријава са стране локације — КПРМ'
            : '🚨 Неуспешан покушај пријаве са стране локације — КПРМ';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.suspicious-login');
    }

    public function attachments(): array
    {
        return [];
    }
}
