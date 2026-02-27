<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Pages\ViewActivity;
use App\Filament\Resources\ActivityResource\Pages;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Евиденција активности';

    protected static ?string $modelLabel = 'Активност';

    protected static ?string $pluralModelLabel = 'Евиденција активности';

    protected static string | \UnitEnum | null $navigationGroup = 'Администрација';

    protected static ?int $navigationSort = 4;

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('log_name')
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

                TextColumn::make('description')
                    ->label('Акција')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('causer.email')
                    ->label('Корисник')
                    ->searchable()
                    ->sortable()
                    ->default('Систем')
                    ->tooltip(fn ($record) => $record->causer?->name),

                TextColumn::make('subject_type')
                    ->label('Табела')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Адреса')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Датум и време')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Тип активности')
                    ->options([
                        'auth' => 'Аутентификација',
                        'users' => 'Корисници',
                        'podaci_o_radnom_mestu' => 'Радна места',
                    ])
                    ->multiple(),

                SelectFilter::make('causer_id')
                    ->label('Корисник')
                    ->options(fn () => Cache::remember('activity_users_filter', 3600, fn () => User::pluck('email', 'id')->toArray()))
                    ->searchable(),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Од датума'),
                        DatePicker::make('created_until')
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
                            $indicators['created_from'] = 'Од: ' . Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'До: ' . Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед'),
            ])
            ->toolbarActions([
                // No bulk actions for audit log
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Опште информације')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('log_name')
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
                        TextEntry::make('description')
                            ->label('Опис'),
                        TextEntry::make('causer.email')
                            ->label('Извршио')
                            ->default('Систем'),
                        TextEntry::make('causer.name')
                            ->label('Име корисника')
                            ->default('Систем'),
                        TextEntry::make('ip_address')
                            ->label('IP Адреса')
                            ->default('N/A'),
                        TextEntry::make('created_at')
                            ->label('Датум и време')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2),

                Section::make('Информације о субјекту')
                    ->schema([
                        TextEntry::make('subject_type')
                            ->label('Тип модела')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'Н/Д'),
                        TextEntry::make('subject_id')
                            ->label('ID модела')
                            ->default('Н/Д'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->subject_type !== null),

                Section::make('Старе вредности')
                    ->schema([
                        KeyValueEntry::make('properties.old')
                            ->label('')
                            ->keyLabel('Поље')
                            ->valueLabel('Вредност')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties['old'] ?? null)),

                Section::make('Нове вредности')
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('')
                            ->keyLabel('Поље')
                            ->valueLabel('Вредност')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties['attributes'] ?? null)),

                Section::make('Додатне информације')
                    ->schema([
                        KeyValueEntry::make('properties')
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
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
