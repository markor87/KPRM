<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SifarnikOrganiResource\Pages\EditSifarnikOrgani;
use App\Filament\Resources\SifarnikOrganiResource\Pages\ListSifarnikOrgani;
use App\Filament\Resources\SifarnikOrganiResource\RelationManagers\OrganPristupiRelationManager;
use App\Models\SifarnikOrgani;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SifarnikOrganiResource extends Resource
{
    protected static ?string $model = SifarnikOrgani::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static string | \UnitEnum | null $navigationGroup = 'Администрација';

    protected static ?string $navigationLabel = 'Органи';

    protected static ?string $modelLabel = 'орган';

    protected static ?string $pluralModelLabel = 'Органи';

    protected static ?string $slug = 'organi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('organ')
                    ->label('Назив органа')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('vrsta_organ_id')
                    ->label('Врста органа')
                    ->relationship('vrstaOrgana', 'vrsta_organa')
                    ->preload()
                    ->searchable(),
                // „У саставу органа" се не бира овде — уписује се са стране надређеног органа,
                // у панелу „Органи у саставу". Приказано само ради прегледа.
                Placeholder::make('nadredjeni_organ_prikaz')
                    ->label('У саставу органа')
                    ->content(fn (?SifarnikOrgani $record): string => $record?->nadredjeniOrgan?->organ ?? '—')
                    ->helperText('Мења се тако што се овај орган дода у састав неког другог органа.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Редослед из шифарника: министарства, управе у саставу, посебне организације,
            // службе Владе, управни окрузи — унутар сваке врсте азбучно.
            ->defaultSort('vrsta_organ_id')
            ->columns([
                TextColumn::make('organ')
                    ->label('Орган')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('vrsta_organ_id')
                    ->label('Врста органа')
                    ->badge()
                    ->placeholder('—')
                    ->state(fn (SifarnikOrgani $record): ?string => $record->vrstaOrgana?->vrsta_organa)
                    // Сортира се по редном броју врсте, не азбучно по њеном називу.
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('vrsta_organ_id', $direction)
                        ->orderBy('organ')),
                TextColumn::make('nadredjeniOrgan.organ')
                    ->label('У саставу органа')
                    ->placeholder('—')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('organ_pristupi_count')
                    ->label('Органа у саставу')
                    ->counts('organPristupi')
                    ->alignCenter(),
                TextColumn::make('korisnici_count')
                    ->label('Корисника')
                    ->counts('korisnici')
                    ->alignCenter(),
                TextColumn::make('radna_mesta_count')
                    ->label('Радних места')
                    ->counts('radnaMesta')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('vrsta_organ_id')
                    ->label('Врста органа')
                    ->relationship('vrstaOrgana', 'vrsta_organa')
                    ->preload()
                    ->multiple(),
                SelectFilter::make('bez_nadredjenog')
                    ->label('Хијерархија')
                    ->options([
                        'ima' => 'Има надређени орган',
                        'nema' => 'Без надређеног органа',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'ima' => $query->whereNotNull('nadredjeni_organ_id'),
                        'nema' => $query->whereNull('nadredjeni_organ_id'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Измени'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrganPristupiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSifarnikOrgani::route('/'),
            'edit' => EditSifarnikOrgani::route('/{record}/edit'),
        ];
    }
}
