<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'Aktivnost';

    protected static ?string $pluralModelLabel = 'Audit Log';

    protected static ?string $navigationGroup = 'Admin Panel';

    protected static ?int $navigationSort = 99;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('log_name')
                    ->label('Тип')
                    ->sortable()
                    ->searchable()
                    ->colors([
                        'primary' => 'auth',
                        'success' => 'users',
                        'warning' => 'podaci_o_radnom_mestu',
                        'danger' => fn ($state) => !in_array($state, ['auth', 'users', 'podaci_o_radnom_mestu']),
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'auth' => 'Аутентификација',
                            'users' => 'Корисници',
                            'podaci_o_radnom_mestu' => 'Радна места',
                            default => ucfirst($state),
                        };
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Акција')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('causer.email')
                    ->label('Корисник')
                    ->searchable()
                    ->sortable()
                    ->default('Систем')
                    ->tooltip(fn ($record) => $record->causer?->name),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Табела')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Адреса')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Датум и време')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Тип активности')
                    ->options([
                        'auth' => 'Аутентификација',
                        'users' => 'Корисници',
                        'podaci_o_radnom_mestu' => 'Радна места',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Корисник')
                    ->options(fn () => User::pluck('email', 'id')->toArray())
                    ->searchable(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Од датума'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('До датума'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Од: ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'До: ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Преглед'),
            ])
            ->bulkActions([
                // No bulk actions for audit log
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Опште информације')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('log_name')
                            ->label('Тип')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'auth' => 'primary',
                                'users' => 'success',
                                'podaci_o_radnom_mestu' => 'warning',
                                default => 'danger',
                            })
                            ->formatStateUsing(function ($state) {
                                return match($state) {
                                    'auth' => 'Аутентификација',
                                    'users' => 'Корисници',
                                    'podaci_o_radnom_mestu' => 'Радна места',
                                    default => ucfirst($state),
                                };
                            }),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Опис'),
                        Infolists\Components\TextEntry::make('causer.email')
                            ->label('Извршио')
                            ->default('Систем'),
                        Infolists\Components\TextEntry::make('causer.name')
                            ->label('Име корисника')
                            ->default('Систем'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Адреса')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Датум и време')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Subject Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Model Type')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A'),
                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('Model ID')
                            ->default('N/A'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->subject_type !== null),

                Infolists\Components\Section::make('Старе вредности')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties.old')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties['old'] ?? null)),

                Infolists\Components\Section::make('Нове вредности')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties.attributes')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties['attributes'] ?? null)),

                Infolists\Components\Section::make('Додатне информације')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties')
                            ->label('')
                            ->columnSpanFull()
                            ->hiddenLabel(),
                    ])
                    ->visible(fn ($record) =>
                        empty($record->properties['old'] ?? null) &&
                        empty($record->properties['attributes'] ?? null) &&
                        !empty($record->properties)
                    ),
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
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }
}
