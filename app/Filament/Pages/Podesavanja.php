<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Podesavanja extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.podesavanja';

    protected static ?string $navigationGroup = 'Admin Panel';

    protected static ?string $navigationLabel = 'Podešavanja';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'two_factor_enabled_global' => Setting::get('two_factor_enabled_global', '0') === '1',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Security Settings')
                    ->description('Global security settings that apply to all users')
                    ->schema([
                        Toggle::make('two_factor_enabled_global')
                            ->label('Enable Two-Factor Authentication (2FA) for All Users')
                            ->helperText('When enabled, ALL users will be required to enter a 6-digit code sent to their email after entering their credentials.')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                Setting::set('two_factor_enabled_global', $state ? '1' : '0');

                                Notification::make()
                                    ->title('Global 2FA ' . ($state ? 'Enabled' : 'Disabled'))
                                    ->body($state ? 'All users must now use 2FA to login.' : '2FA is no longer required for login.')
                                    ->success()
                                    ->send();
                            }),
                    ]),
            ])
            ->statePath('data');
    }
}
