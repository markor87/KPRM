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
                    // Rate limiting: max 20 pozivnica po korisniku na sat.
                    // Limit se troši po POKUŠAJU (ne po uspešnom slanju) da neispravan
                    // SMTP ne bi mogao da se zloupotrebi za neograničene pokušaje slanja.
                    $rateLimitKey = 'invitations:' . auth()->id();
                    $maxPerHour = 20;

                    if (RateLimiter::tooManyAttempts($rateLimitKey, $maxPerHour)) {
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
                    $failedEmails = [];
                    $skippedForLimit = 0;

                    foreach ($emails as $email) {
                        // Provera i unutar petlje da jedan veliki batch ne zaobiđe limit odjednom
                        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxPerHour)) {
                            $skippedForLimit++;
                            continue;
                        }

                        // Broji pokušaj pre slanja - neuspeh takođe troši limit
                        RateLimiter::hit($rateLimitKey, 3600);

                        $invitation = Invitation::create([
                            'email'      => $email,
                            'token'      => Str::random(64),
                            'expires_at' => now()->addDays(7),
                            'invited_by' => auth()->id(),
                            'organ_id'   => $data['organ_id'],
                        ]);

                        try {
                            Mail::to($invitation->email)->send(new InvitationMail($invitation));
                            $invitationCount++;
                        } catch (\Exception $e) {
                            $invitation->delete();
                            $failedEmails[] = $email;
                        }
                    }

                    if ($invitationCount > 0) {
                        Notification::make()
                            ->title('Позивнице успешно послате')
                            ->body("Послато је {$invitationCount} позивница.")
                            ->success()
                            ->send();
                    }

                    if (!empty($failedEmails)) {
                        Notification::make()
                            ->title('Грешка при слању')
                            ->body('Слање није успело за: ' . implode(', ', $failedEmails))
                            ->danger()
                            ->persistent()
                            ->send();
                    }

                    if ($skippedForLimit > 0) {
                        Notification::make()
                            ->title('Лимит слања достигнут')
                            ->body("{$skippedForLimit} позивница није послато јер је достигнут лимит од {$maxPerHour} на сат. Покушајте поново касније.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }

                    return null; // Ne vraća record jer kreiramo više
                })
                ->successNotification(null), // Onemogući default notifikaciju
        ];
    }
}
