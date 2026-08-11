<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
use App\Models\SifarnikVrstaOrgana;
use App\Models\SifarnikOrgani;
use App\Models\SifarnikTipKonkursa;
use App\Models\SifarnikZvanje;
use App\Models\SifarnikStatusKonkursa;
use App\Models\SifarnikRazlogNeuspelihKonkursa;
use App\Models\SifarnikProveraPfk;
use App\Models\SifarnikIzabraniKandidat;

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
        return auth()->user()?->can('View:Activity')
            || auth()->user()?->can('ViewAny:Activity')
            || false;
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

                TextColumn::make('log_name')
                    ->label('Тип')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->color(fn ($state) => match($state) {
                        'auth' => 'primary',
                        'users' => 'success',
                        'podaci_o_radnom_mestu' => 'warning',
                        'organ_pristupi' => 'info',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'auth' => 'Аутентификација',
                        'users' => 'Корисници',
                        'podaci_o_radnom_mestu' => 'Радна места',
                        'organ_pristupi' => 'Органи у саставу',
                        default => ucfirst($state),
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
                        'organ_pristupi' => 'Органи у саставу',
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

    /**
     * Мапа FK поља -> [шифарник модел, колона са називом]. Користи се да се
     * уз ID прикаже и његово значење у логу старих/нових вредности.
     */
    protected static function fkMapa(): array
    {
        return [
            'vrsta_organa'              => [SifarnikVrstaOrgana::class, 'vrsta_organa'],
            'organ'                     => [SifarnikOrgani::class, 'organ'],
            'nadredjeni_organ_id'      => [SifarnikOrgani::class, 'organ'],
            'podredjeni_organ_id'      => [SifarnikOrgani::class, 'organ'],
            'tip_konkursa'             => [SifarnikTipKonkursa::class, 'tip_konkursa'],
            'zvanje'                    => [SifarnikZvanje::class, 'zvanje'],
            'ishod_konkursa'           => [SifarnikStatusKonkursa::class, 'status_konkursa'],
            'razlog_neuspelog_konkursa' => [SifarnikRazlogNeuspelihKonkursa::class, 'razlog'],
            'provera_pfk'              => [SifarnikProveraPfk::class, 'provera_pfk'],
            'izabrani_kandidat'        => [SifarnikIzabraniKandidat::class, 'izabrani_kandidat'],
            'drugoplasirani_kandidat'  => [SifarnikIzabraniKandidat::class, 'izabrani_kandidat'],
        ];
    }

    /**
     * Значење ID-а за FK поље (нпр. organ=6 -> „Управа за спречавање прања новца").
     * За поља која нису FK враћа празан стринг.
     */
    protected static function znacenjeVrednosti(string $polje, $vrednost): string
    {
        $mapa = self::fkMapa();
        if (! isset($mapa[$polje]) || $vrednost === null || $vrednost === '') {
            return '';
        }
        [$model, $kolona] = $mapa[$polje];
        return (string) ($model::where('id', $vrednost)->value($kolona) ?? '(непознат ID)');
    }

    /**
     * Припрема мапу поље => вредност за KeyValueEntry, при чему се за FK поља
     * уз ID допише и значење из шифарника (нпр. „73 — Национална академија…").
     */
    protected static function vrednostiSaZnacenjem($props): array
    {
        $props = is_iterable($props) ? (array) $props : [];

        $out = [];
        foreach ($props as $polje => $vrednost) {
            // Логичке вредности иначе стижу као „1" односно празно поље.
            if (is_bool($vrednost)) {
                $out[(string) $polje] = $vrednost ? 'Да' : 'Не';
                continue;
            }

            $znacenje = self::znacenjeVrednosti((string) $polje, $vrednost);
            $out[(string) $polje] = ($znacenje !== '')
                ? $vrednost . ' — ' . $znacenje
                : $vrednost;
        }

        return $out;
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
                                'organ_pristupi' => 'info',
                                default => 'danger',
                            })
                            ->formatStateUsing(function ($state) {
                                return match($state) {
                                    'auth' => 'Аутентификација',
                                    'users' => 'Корисници',
                                    'podaci_o_radnom_mestu' => 'Радна места',
                                    'organ_pristupi' => 'Органи у саставу',
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
                        KeyValueEntry::make('stare_vrednosti')
                            ->hiddenLabel()
                            ->keyLabel('Поље')
                            ->valueLabel('Вредност')
                            ->state(fn ($record) => self::vrednostiSaZnacenjem($record->properties['old'] ?? []))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties['old'] ?? null)),

                Section::make('Нове вредности')
                    ->schema([
                        KeyValueEntry::make('nove_vrednosti')
                            ->hiddenLabel()
                            ->keyLabel('Поље')
                            ->valueLabel('Вредност')
                            ->state(fn ($record) => self::vrednostiSaZnacenjem($record->properties['attributes'] ?? []))
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
