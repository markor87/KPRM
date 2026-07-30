<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Setting;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Podesavanja extends Page
{

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.podesavanja';

    protected static string | \UnitEnum | null $navigationGroup = 'Admin Panel';

    protected static ?string $navigationLabel = 'Подешавања';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'two_factor_enabled_global' => Setting::get('two_factor_enabled_global', '0') === '1',
            'cirilica_naziv_radnog_mesta' => Setting::get('cirilica_naziv_radnog_mesta', '1') === '1',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Безбедносна подешавања')
                    ->description('Глобална безбедносна подешавања која важе за све кориснике')
                    ->schema([
                        Toggle::make('two_factor_enabled_global')
                            ->label('Омогући двофакторску аутентификацију (2ФА) за све кориснике')
                            ->helperText('Када је омогућено, сви корисници ће морати да унесу 6-цифрени код послат на њихову е-пошту након уноса акредитива.')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                Setting::set('two_factor_enabled_global', $state ? '1' : '0');

                                Notification::make()
                                    ->title('Глобална 2ФА ' . ($state ? 'Омогућена' : 'Онемогућена'))
                                    ->body($state ? 'Сви корисници сада морају да користе 2ФА за пријаву.' : '2ФА више није потребна за пријаву.')
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull(),
                Section::make('Унос података')
                    ->description('Подешавања везана за унос података о радним местима')
                    ->schema([
                        Toggle::make('cirilica_naziv_radnog_mesta')
                            ->label('Дозволи само ћирилицу у пољу „Назив радног места“')
                            ->helperText('Када је укључено, поље „Назив радног места“ прихвата само ћирилична слова (и бројеве/интерпункцију). Када је искључено, прихвата сва слова.')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                Setting::set('cirilica_naziv_radnog_mesta', $state ? '1' : '0');

                                Notification::make()
                                    ->title('Ћирилична провера ' . ($state ? 'укључена' : 'искључена'))
                                    ->body($state ? 'Поље „Назив радног места“ сада прихвата само ћирилицу.' : 'Поље „Назив радног места“ сада прихвата сва слова.')
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }
}
