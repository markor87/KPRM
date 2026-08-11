<?php

namespace App\Filament\Resources\SifarnikOrganiResource\RelationManagers;

use App\Models\OrganPristup;
use App\Models\SifarnikOrgani;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Органи у саставу овог органа, заједно са правима која над њима има.
 *
 * Додавање органа овде уписује и сам састав (sifarnik_organi.nadredjeni_organ_id) — то се
 * намерно не бира са стране подређеног органа, да мапирање буде на једном месту.
 *
 * Постојање реда значи право прегледа. Прекидачи додају унос, измену и брисање, и сваки важи
 * тек ако корисникова улога већ носи одговарајућу дозволу — улога даје ШТА, овај ред ГДЕ.
 */
class OrganPristupiRelationManager extends RelationManager
{
    protected static string $relationship = 'organPristupi';

    protected static ?string $title = 'Органи у саставу';

    protected static ?string $modelLabel = 'орган у саставу';

    protected static ?string $pluralModelLabel = 'органи у саставу';

    /**
     * Панел се везује за право измене шифарника — тако неко може добити преглед органа без
     * могућности да мења састав и права.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('Update:SifarnikOrgani');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('podredjeni_organ_id')
            ->defaultSort('id')
            ->columns([
                TextColumn::make('podredjeniOrgan.organ')
                    ->label('Орган у саставу')
                    ->wrap(),
                ToggleColumn::make('moze_kreiranje')
                    ->label('Унос'),
                ToggleColumn::make('moze_izmenu')
                    ->label('Измена'),
                ToggleColumn::make('moze_brisanje')
                    ->label('Брисање'),
            ])
            ->emptyStateHeading('Ниједан орган није у саставу овог органа')
            ->emptyStateDescription('Кликни „Додај органе" и означи управе које припадају овом органу. Док овде нема ниједног реда, корисници овог органа виде само сопствени орган.')
            ->headerActions([
                Action::make('dodajOrgane')
                    ->label('Додај органе')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Додај органе у састав')
                    ->modalSubmitActionLabel('Додај')
                    ->modalWidth('xl')
                    ->disabled(fn (): bool => $this->slobodniOrgani() === [])
                    ->schema([
                        CheckboxList::make('organi')
                            ->label('Органи')
                            ->options(fn (): array => $this->slobodniOrgani())
                            ->required()
                            ->searchable()
                            ->bulkToggleable()
                            ->columnSpanFull()
                            ->helperText('Може се означити више њих одједном. Не нуде се органи који су већ у саставу неког другог органа.'),
                        Toggle::make('moze_kreiranje')
                            ->label('Може да уноси нова радна места')
                            ->helperText('Тренутно без дејства — поље „Орган" у форми је закључано на сопствени орган корисника. Стоји спреман за случај да се унос отвори.'),
                        Toggle::make('moze_izmenu')
                            ->label('Може да мења постојећа радна места'),
                        Toggle::make('moze_brisanje')
                            ->label('Може да брише радна места')
                            ->helperText('Важи само ако улога корисника већ има дозволу за брисање.'),
                    ])
                    // Изабрани органи добијају иста права; појединачно се после мењају
                    // прекидачима у табели.
                    ->action(function (array $data): void {
                        $prava = [
                            'moze_kreiranje' => (bool) ($data['moze_kreiranje'] ?? false),
                            'moze_izmenu' => (bool) ($data['moze_izmenu'] ?? false),
                            'moze_brisanje' => (bool) ($data['moze_brisanje'] ?? false),
                        ];

                        $vlasnik = $this->getOwnerRecord();
                        $izabrani = array_map('intval', $data['organi'] ?? []);

                        foreach ($izabrani as $organId) {
                            $vlasnik->organPristupi()
                                ->firstOrCreate(['podredjeni_organ_id' => $organId], $prava);
                        }

                        // Састав се уписује заједно са приступом, да мапирање буде на једном месту.
                        SifarnikOrgani::whereIn('id', $izabrani)
                            ->update(['nadredjeni_organ_id' => $vlasnik->getKey()]);

                        $dodato = count($izabrani);

                        Notification::make()
                            ->success()
                            ->title($dodato === 1 ? 'Орган додат у састав' : "Додато органа у састав: {$dodato}")
                            ->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Укини')
                    ->modalHeading('Избаци орган из састава?')
                    ->modalDescription('Корисници овог органа више неће видети његове податке, а орган постаје слободан да се дода неком другом. Већ унети записи остају нетакнути.')
                    ->after(fn (OrganPristup $record) => SifarnikOrgani::whereKey($record->podredjeni_organ_id)
                        ->update(['nadredjeni_organ_id' => null])),
            ]);
    }

    /**
     * Органи који се могу додати: сви осим самог власника и оних који су већ у саставу неког
     * органа (укључујући овај).
     *
     * @return array<int, string> [id => назив органа]
     */
    private function slobodniOrgani(): array
    {
        return SifarnikOrgani::query()
            ->whereKeyNot($this->getOwnerRecord()->getKey())
            ->whereNull('nadredjeni_organ_id')
            ->orderBy('organ')
            ->pluck('organ', 'id')
            ->map(fn ($naziv): string => (string) $naziv)
            ->all();
    }
}
