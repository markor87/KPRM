<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Admin Panel';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Име')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('email')
                    ->label('Е-пошта')
                    ->email()
                    ->required()
                    ->maxLength(191),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Е-пошта верификована'),
                Forms\Components\TextInput::make('password')
                    ->label('Лозинка')
                    ->password()
                    ->required()
                    ->maxLength(191)
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Select::make('roles')
                    ->label('Улоге')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('organ_id')
                    ->label('Орган')
                    ->relationship('organ', 'organ')
                    ->preload()
                    ->searchable(),
                Forms\Components\Toggle::make('is_super_admin')
                    ->label('Супер Администратор')
                    ->helperText('Супер администратори имају приступ свему'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Е-пошта')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color('success')
                    ->label('Улоге'),
                Tables\Columns\TextColumn::make('organ.organ')
                    ->label('Орган')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->boolean()
                    ->label('Супер Администратор'),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Преглед'),
                Tables\Actions\EditAction::make()
                    ->label('Измени'),
                Tables\Actions\DeleteAction::make()
                    ->label('Обриши'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
