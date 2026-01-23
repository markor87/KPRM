<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvitationResource\Pages;
use App\Filament\Resources\InvitationResource\RelationManagers;
use App\Models\Invitation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static ?string $navigationGroup = 'Администрација';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Позивнице';

    protected static ?string $modelLabel = 'Позивница';

    protected static ?string $pluralModelLabel = 'Позивнице';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_invitation')
            || auth()->user()?->can('view_any_invitation')
            || false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('emails')
                    ->label('Email адресе')
                    ->required()
                    ->rows(5)
                    ->helperText('Унесите email адресе одвојене новим редом или зарезом. Пример: email1@example.com, email2@example.com')
                    ->rules([
                        'required',
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                // Podeli email-ove po novim redovima ili zarezima
                                $emails = preg_split('/[\n,]+/', $value);
                                $emails = array_filter(array_map('trim', $emails));

                                foreach ($emails as $email) {
                                    // Proveri da li je validan email
                                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                        $fail("Email adresa '{$email}' nije validna.");
                                        return;
                                    }

                                    // Proveri da li email već postoji u users tabeli
                                    if (\App\Models\User::where('email', $email)->exists()) {
                                        $fail("Корисник са email адресом '{$email}' већ постоји.");
                                        return;
                                    }

                                    // Proveri da li već postoji aktivna pozivnica za ovaj email
                                    $existingInvitation = \App\Models\Invitation::where('email', $email)
                                        ->whereNull('accepted_at')
                                        ->where('expires_at', '>', now())
                                        ->first();

                                    if ($existingInvitation) {
                                        $fail("Већ постоји активна позивница за email адресу '{$email}'.");
                                        return;
                                    }
                                }
                            };
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invitedBy.name')
                    ->label('Послао')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Статус')
                    ->getStateUsing(function ($record) {
                        if ($record->isAccepted()) {
                            return 'accepted';
                        } elseif ($record->isExpired()) {
                            return 'expired';
                        }
                        return 'pending';
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'accepted' => 'heroicon-o-check-circle',
                        'expired' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'expired' => 'danger',
                        'pending' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Истиче')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Прихваћено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Није прихваћено'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Креирано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На чекању',
                        'accepted' => 'Прихваћено',
                        'expired' => 'Истекло',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'pending' => $query->whereNull('accepted_at')->where('expires_at', '>', now()),
                            'accepted' => $query->whereNotNull('accepted_at'),
                            'expired' => $query->whereNull('accepted_at')->where('expires_at', '<=', now()),
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Поново пошаљи')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => $record->isExpired() && !$record->isAccepted())
                    ->action(function ($record) {
                        $record->update(['expires_at' => now()->addDays(7)]);
                        \Mail::to($record->email)->send(new \App\Mail\InvitationMail($record));
                        \Filament\Notifications\Notification::make()
                            ->title('Позивница поново послата')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Обриши'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Обриши означене'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvitations::route('/'),
        ];
    }
}
