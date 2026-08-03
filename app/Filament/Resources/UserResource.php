<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Администрација';

    protected static ?string $navigationLabel = 'Корисници';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Име')
                    ->required()
                    ->maxLength(191),
                TextInput::make('email')
                    ->label('Е-пошта')
                    ->email()
                    ->required()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Корисник са овом е-поштом већ постоји.',
                    ]),
                TextInput::make('password')
                    ->label('Лозинка')
                    ->password()
                    ->required()
                    ->maxLength(191)
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Select::make('roles')
                    ->label('Улоге')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('organ_id')
                    ->label('Орган')
                    ->relationship('organ', 'organ')
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Е-пошта')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color('success')
                    ->label('Улоге'),
                TextColumn::make('organ.organ')
                    ->label('Орган')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('impersonate')
                        ->label('Уђи као корисник')
                        ->icon('heroicon-o-user-circle')
                        ->color('warning')
                        ->visible(fn (User $record): bool =>
                            auth()->user()?->isSuperAdmin()
                            && $record->isNot(auth()->user())
                            && $record->canBeImpersonated())
                        ->requiresConfirmation()
                        ->modalHeading('Уђи у преглед као овај корисник?')
                        ->modalDescription('Видећеш апликацију онако како је види овај корисник. Режим је само за преглед — измене нису могуће.')
                        ->modalSubmitActionLabel('Уђи')
                        ->action(fn (User $record) => redirect()->route('impersonate', [
                            'id' => $record->getKey(),
                            'guardName' => 'web',
                        ])),
                    ViewAction::make()
                        ->label('Преглед'),
                    EditAction::make()
                        ->label('Измени'),
                    DeleteAction::make()
                        ->label('Обриши'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Обриши означене'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
