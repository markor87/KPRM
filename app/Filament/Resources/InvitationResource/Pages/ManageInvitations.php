<?php

namespace App\Filament\Resources\InvitationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Str;
use App\Filament\Resources\InvitationResource;
use App\Models\Invitation;
use App\Mail\InvitationMail;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ManageInvitations extends ManageRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Пошаљи позивнице')
                ->icon('heroicon-o-paper-airplane')
                ->using(function (array $data) {
                    // Rate limiting: max 20 pozivnica po korisniku na sat
                    $rateLimitKey = 'invitations:' . auth()->id();

                    if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
                        $seconds = RateLimiter::availableIn($rateLimitKey);
                        $minutes = ceil($seconds / 60);

                        Notification::make()
                            ->title('Превише позивница')
                            ->body("Достигли сте лимит слања позивница. Покушајте поново за {$minutes} минута.")
                            ->danger()
                            ->send();

                        return null;
                    }

                    // Podeli email-ove po novim redovima ili zarezima
                    $emails = preg_split('/[\n,]+/', $data['emails']);
                    $emails = array_filter(array_map('trim', $emails));

                    $invitationCount = 0;

                    foreach ($emails as $email) {
                        // Kreiraj pozivnicu
                        $invitation = Invitation::create([
                            'email' => $email,
                            'token' => Str::random(64),
                            'expires_at' => now()->addDays(7),
                            'invited_by' => auth()->id(),
                        ]);

                        // Pošalji email
                        Mail::to($invitation->email)->send(new InvitationMail($invitation));

                        // Inkrementiraj rate limiter za svaku poslatu pozivnicu
                        RateLimiter::hit($rateLimitKey, 3600); // 1 sat

                        $invitationCount++;
                    }

                    // Notifikacija
                    Notification::make()
                        ->title('Позивнице успешно послате')
                        ->body("Послато је {$invitationCount} позивница.")
                        ->success()
                        ->send();

                    return null; // Ne vraća record jer kreiramo više
                })
                ->successNotification(null), // Onemogući default notifikaciju
        ];
    }
}
