<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Exception;
use App\Services\OrganFilterService;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use App\Models\SifarnikOrgani;
use App\Models\SifarnikZvanje;
use Filament\Forms\Components\Repeater;
use App\Models\SifarnikMesta;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PodaciORadnomMestuResource\Pages\ListPodaciORadnomMestus;
use App\Filament\Resources\PodaciORadnomMestuResource\Pages\CreatePodaciORadnomMestu;
use App\Filament\Resources\PodaciORadnomMestuResource\Pages\EditPodaciORadnomMestu;
use App\Filament\Resources\PodaciORadnomMestuResource\Pages;
use App\Filament\Resources\PodaciORadnomMestuResource\RelationManagers;
use App\Models\PodaciORadnomMestu;
use App\Exports\PodaciORadnomMestuExport;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class PodaciORadnomMestuResource extends Resource
{
    protected static ?string $model = PodaciORadnomMestu::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Радна Места';

    protected static ?string $modelLabel = 'Радно Место';

    protected static ?string $pluralModelLabel = 'Радна Места';

    protected static ?string $slug = 'podaci-o-radnom-mestu';

    protected static ?int $navigationSort = 10;

    /**
     * Kreira validacionu logiku za proveru da li zbir polja odgovara ukupnom broju.
     */
    protected static function sumValidationRule(
        string $totalField,
        array $sumFields,
        string $errorMessage
    ): Closure {
        return function ($get) use ($totalField, $sumFields, $errorMessage) {
            return function (string $attribute, $value, $fail) use ($get, $totalField, $sumFields, $errorMessage) {
                $ukupan = $get($totalField) ?? 0;
                $sum = 0;

                foreach ($sumFields as $field) {
                    $sum += $get($field) ?? 0;
                }

                if ($ukupan > 0 && $sum != $ukupan) {
                    $fail($errorMessage);
                }
            };
        };
    }

    /**
     * Validacija: uspešno završen javni konkurs mora imati popunjena sva datumska polja (osim 3 izuzeta).
     */
    protected static function uspesnoZavrsenValidationRule(): Closure
    {
        $requiredDates = [
            'datum_dobijanja_saglasnosti_vlade'               => 'Датум добијања сагласности Владе',
            'datum_donosenja_resenja_o_pokretanju_postupka'   => 'Датум доношења решења о покретању поступка',
            'datum_dobijanja_obavestenja_od_suka'             => 'Датум добијања обавештења од СУКа',
            'datum_odrzavanja_prvog_sastanka'                 => 'Датум одржавања првог састанка',
            'datum_oglasavanja'                               => 'Датум оглашавања',
            'datum_pregleda_prijava'                          => 'Датум прегледа пријава',
            'datum_pocetka_provere_ofk'                       => 'Датум спровођења провере ОФК',
            'datum_ofk_izvestaja'                             => 'Датум ОФК извештаја',
            'datum_pocetka_provere_pfk'                       => 'Датум почетка провере ПФК',
            'datum_pfk_izvestaja'                             => 'Датум ПФК извештаја',
            'datum_pocetka_provere_pk'                        => 'Датум почетка провере ПК',
            'datum_pk_izvestaja'                              => 'Датум ПК извештаја',
            'datum_predaje_dokumentacije'                     => 'Датум предаје документације',
            'datum_pocetka_sprovodjenja_intervjua'            => 'Датум спровођења завршног интервјуа',
            'datum_izvestaja_sa_zavrsnog_intervjua'           => 'Датум извештаја са завршног интервјуа',
            'datum_dostavljanja_liste_rukovodiocu_organa'     => 'Датум достављања листе руководиоцу органа',
            'datum_donosenja_resenja_o_izabranom_kandidatu'   => 'Датум доношења решења о изабраном кандидату',
            'datum_stupanja_na_rad'                           => 'Датум ступања на рад',
            'datum_formiranja_liste_kandidata'                => 'Дан формирања листе кандидата',
        ];

        return fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $requiredDates) {
            if ($value != 1) return;
            if ($get('tip_konkursa') != 1) return;

            $missing = [];
            foreach ($requiredDates as $field => $label) {
                if (empty($get($field))) {
                    $missing[] = $label;
                }
            }

            if (!empty($missing)) {
                $fail('Успешно завршен јавни конкурс мора да има попуњена следећа датумска поља: ' . implode(', ', $missing) . '.');
            }
        };
    }

    protected static function makeDateField(string $name, string $label, string $afterField = null, string $afterLabel = null): TextInput
    {
        // Submit-time validation (prevents saving bad data)
        $rules = [
            fn () => function (string $attribute, $value, Closure $fail) {
                if (!$value) return;
                if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value)) {
                    $fail('Датум мора бити у формату дд.мм.гггг');
                    return;
                }
                [$day, $month, $year] = explode('.', $value);
                if (!checkdate((int)$month, (int)$day, (int)$year)) {
                    $fail('Унети датум није валидан');
                }
            },
        ];

        if ($afterField && $afterLabel) {
            $rules[] = fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $afterField, $afterLabel) {
                if (!$value || !$get($afterField)) return;
                try {
                    $current  = Carbon::createFromFormat('d.m.Y', $value);
                    $prevRaw2 = $get($afterField);
                    $previous = null;
                    if ($prevRaw2) {
                        try { $previous = Carbon::createFromFormat('d.m.Y', $prevRaw2); } catch (\Exception $ex2) {}
                        if (!$previous || !$previous->isValid()) { try { $previous = Carbon::createFromFormat('Y-m-d', $prevRaw2); } catch (\Exception $ex3) {} }
                    }
                    if ($previous && $current->lt($previous)) {
                        $fail("Датум мора бити после или једнак датуму {$afterLabel}");
                    }
                } catch (Exception $e) {}
            };
        }

        return TextInput::make($name)
            ->label($label)
            ->mask('99.99.9999')
            ->placeholder('дд.мм.гггг')
            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
            ->rules($rules)
            ->afterStateUpdated(function ($state, $get, $component, $livewire) use ($afterField, $afterLabel) {
                $path = $component->getStatePath();

                // Always clear previous error
                $livewire->resetValidation($path);

                if (!$state) return;

                // Real-time format validation
                if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $state)) {
                    $livewire->addError($path, 'Датум мора бити у формату дд.мм.гггг');
                    return;
                }
                [$day, $month, $year] = explode('.', $state);
                if (!checkdate((int)$month, (int)$day, (int)$year)) {
                    $livewire->addError($path, 'Унети датум није валидан');
                    return;
                }

                // Real-time comparison validation
                if ($afterField && $afterLabel && $get($afterField)) {
                    try {
                        $current  = Carbon::createFromFormat('d.m.Y', $state);
                        $prevRaw = $get($afterField);
                        $previous = null;
                        if ($prevRaw) {
                            try { $previous = Carbon::createFromFormat('d.m.Y', $prevRaw); } catch (\Exception $e2) {}
                            if (!$previous || !$previous->isValid()) { try { $previous = Carbon::createFromFormat('Y-m-d', $prevRaw); } catch (\Exception $e3) {} }
                        }
                        if ($previous && $current->lt($previous)) {
                            $livewire->addError($path, "Датум мора бити после илиједнак датуму {$afterLabel}");
                        }
                    } catch (Exception $e) {}
                }

            })
            ->dehydrateStateUsing(fn ($state) => $state ? Carbon::createFromFormat('d.m.Y', $state)->format('Y-m-d') : null)
            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d.m.Y') : null);
    }

    protected static function dateDiffInDays($record, string $startField, string $endField): string
    {
        if ($record->$startField && $record->$endField) {
            return Carbon::parse($record->$startField)->diffInDays(Carbon::parse($record->$endField)) . ' дана';
        }
        return 'Н/Д';
    }

    private static function ofkScoreOptions(): array { return [7 => '7', 8 => '8', 9 => '9']; }
    private static function pfkScoreOptions(): array { return [3=>'3',5=>'5',8=>'8'] + array_combine(range(10,20),range(10,20)); }
    private static function pkScoreOptions(): array { return array_combine(range(10,30),range(10,30)); }
    private static function zavrsniScoreOptions(): array { return [2 => '2', 4 => '4', 6 => '6']; }

    /**
     * Apply organ-based filtering globally to all queries
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'mestaRada',
            'vrstaOrganaRelation',
            'organRelation',
            'zvanjeRelation',
        ]);
        return app(OrganFilterService::class)->applyOrganFilter($query, 'organ');
    }

    /**
     * Allow access to list page with either 'view' or 'view_any' permission
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('View:PodaciORadnomMestu')
            || auth()->user()?->can('ViewAny:PodaciORadnomMestu')
            || false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([

                Tab::make('Основни подаци о конкурсу')
                    ->schema([
                        TextInput::make('naziv_radnog_mesta')
                            ->label('Назив радног места')
                            ->maxLength(500)
                            ->required()
                            ->regex('/^[А-Ша-шЂЈЉЊЋЏђјљњћџ0-9\s.,\-–—():;\/\*\"\']+$/u')
                            ->validationMessages([
                                'regex' => 'Назив радног места може садржати само ћирилична слова.',
                            ])
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->columnSpanFull(),
                        Select::make('vrsta_organa')
                            ->label('Врста органа')
                            ->relationship('vrstaOrganaRelation', 'vrsta_organa', fn($query) => $query->orderBy('id', 'asc'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->disabled()
                            ->dehydrated()
                            ->default(function () {
                                $user = auth()->user();
                                return $user && $user->organ ? $user->organ->vrsta_organ_id : null;
                            }),
                        Select::make('organ')
                            ->label('Орган')
                            ->options(function (callable $get) {
                                $vrstaOrganaId = $get('vrsta_organa');
                                if (!$vrstaOrganaId) {
                                    return SifarnikOrgani::pluck('organ', 'id');
                                }
                                return SifarnikOrgani::where('vrsta_organ_id', $vrstaOrganaId)
                                    ->pluck('organ', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->default(function () {
                                return auth()->user()?->organ_id;
                            }),
                        Select::make('tip_konkursa')
                            ->label('Тип конкурса')
                            ->relationship('tipKonkursaRelation', 'tip_konkursa')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('broj_izvrsilaca')
                            ->label('Број извршилаца')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->live(onBlur: true)->afterStateUpdated(function ($state, Get $get, Set $set, $component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                if ($state !== null && $state !== '') {
                                    $mestaRada = $get('mestaRada') ?? [];
                                    if (count($mestaRada) === 1) {
                                        $firstKey = array_key_first($mestaRada);
                                        $set("mestaRada.{$firstKey}.broj_izvrsilaca", (int) $state);
                                    }
                                }
                            }),
                        Select::make('zvanje')
                            ->label('Звање')
                            ->options(function (Get $get) {
                                $organId = $get('organ');
                                return SifarnikZvanje::when(
                                    $organId,
                                    fn ($q) => $q->where('organ_id', $organId)
                                )
                                ->orderBy('id')
                                ->pluck('zvanje', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Select::make('oblastiRada')
                            ->label('Претежна област рада')
                            ->relationship('oblastiRada', 'oblast_rada', fn($query) => $query->orderBy('id', 'asc'))
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Repeater::make('mestaRada')
                            ->label('Места рада са бројем извршилаца')
                            ->schema([
                                Select::make('sifarnik_mesta_id')
                                    ->label('Место рада')
                                    ->options(SifarnikMesta::whereNotNull('mesto')->where('mesto', '!=', '')->orderBy('mesto')->pluck('mesto', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->live(),

                                TextInput::make('broj_izvrsilaca')
                                    ->label('Број извршилаца')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                                    ->default(1),
                            ])
                            ->columns(2)
                            ->addActionLabel('Додај место рада')
                            ->defaultItems(1)
                            ->required()
                            ->live()->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->collapsed()
                            ->itemLabel(function (array $state): ?string {
                                $mestoId = $state['sifarnik_mesta_id'] ?? null;
                                $brojIzvrsilaca = $state['broj_izvrsilaca'] ?? 1;

                                if ($mestoId) {
                                    $mesto = SifarnikMesta::find($mestoId);
                                    if ($mesto) {
                                        return "{$mesto->mesto} ({$brojIzvrsilaca})";
                                    }
                                }

                                return 'Место рада';
                            })
                            ->collapsible()
                            ->afterStateHydrated(function (Repeater $component, $state, $record) {
                                if ($record && $record->mestaRada) {
                                    $data = $record->mestaRada->map(function ($mesto) {
                                        return [
                                            'sifarnik_mesta_id' => $mesto->id,
                                            'broj_izvrsilaca' => $mesto->pivot->broj_izvrsilaca ?? 1,
                                        ];
                                    })->toArray();

                                    $component->state($data);
                                }
                            })
                            ->dehydrated(false)
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupanBrojIzvrsilaca = (int) $get('broj_izvrsilaca') ?: 0;

                                    if (!is_array($value) || empty($value)) {
                                        return;
                                    }

                                    // Провера дупликата
                                    $gradovi = array_column($value, 'sifarnik_mesta_id');
                                    $gradovi = array_filter($gradovi);
                                    if (count($gradovi) !== count(array_unique($gradovi))) {
                                        $fail("Не можете изабрати исти град више пута.");
                                    }

                                    // Збир извршилаца по градовима
                                    $zbirPoGradovima = array_sum(array_column($value, 'broj_izvrsilaca'));

                                    if ($zbirPoGradovima !== $ukupanBrojIzvrsilaca) {
                                        $fail("Збир извршилаца по градовима ($zbirPoGradovima) мора бити једнак укупном броју извршилаца ($ukupanBrojIzvrsilaca).");
                                    }
                                },
                            ])
                            ->helperText(fn (Get $get) => 'Укупан број извршилаца: ' . ($get('broj_izvrsilaca') ?: 0) . '. Збир по градовима мора бити једнак овом броју.'),
                        Select::make('status_konkursa_na_dan_1')
                            ->label('Статус конкурса на дан 31/12/' . (now()->year - 1))
                            ->relationship('statusKonkursaNaDan1Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->hidden(fn () => auth()->user()->hasRole('User'))
                            ->rules([static::uspesnoZavrsenValidationRule()]),
                        Select::make('status_konkursa_na_dan_2')
                            ->label('Статус конкурса на дан 31/12/' . now()->year)
                            ->relationship('statusKonkursaNaDan2Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->live()
                            ->rules([static::uspesnoZavrsenValidationRule()]),
                        Select::make('razlog_neuspelog_konkursa')
                            ->label('Разлог неуспелог конкурса')
                            ->relationship('razlogNeuspelogKonkursaRelation', 'razlog', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->disabled(fn (Get $get) => $get('status_konkursa_na_dan_2') != 2)
                            ->dehydrated(),
                    ])->columns(3),

                Tab::make('Покретање поступка и пријаве')
                    ->schema([
                        Section::make('Покретање поступка')
                            ->schema([
                        static::makeDateField('datum_dobijanja_saglasnosti_vlade', 'Датум добијања сагласности Владе'),
                        static::makeDateField('datum_donosenja_resenja_o_pokretanju_postupka', 'Датум доношења решења о покретању поступка', 'datum_dobijanja_saglasnosti_vlade', 'добијања сагласности Владе'),
                        static::makeDateField('datum_dobijanja_obavestenja_od_suka', 'Датум добијања обавештења од СУКа', 'datum_donosenja_resenja_o_pokretanju_postupka', 'доношења решења о покретању поступка'),
                        static::makeDateField('datum_odrzavanja_prvog_sastanka', 'Датум одржавања првог састанка', 'datum_dobijanja_obavestenja_od_suka', 'добијања обавештења од СУКа'),
                        static::makeDateField('datum_oglasavanja', 'Датум оглашавања', 'datum_odrzavanja_prvog_sastanka', 'одржавања првог sastanka'),
                        static::makeDateField('datum_pregleda_prijava', 'Датум прегледа пријава', 'datum_oglasavanja', 'оглашавања'),
                    ])->columns(3),

                        Section::make('Пристигле пријаве')
                            ->schema([
                        TextInput::make('ukupan_broj_prijava')
                            ->label('Укупан број пријава')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath())),
                        TextInput::make('broj_prijava_iz_organa')
                            ->label('Број пријава из органа који расписује конкурс')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број пријава из органа не може бити већи од укупног броја пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'ukupan_broj_prijava',
                                    ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa'],
                                    'Збир пријава мора бити једнак укупном броју пријава.'
                                ),
                            ]),
                        TextInput::make('broj_prijava_iz_drugih_organa')
                            ->label('Број пријава из других органа државне управе')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број пријава из других органа не може бити већи од укупног броја пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'ukupan_broj_prijava',
                                    ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa'],
                                    'Збир пријава мора бити једнак укупном броју пријава.'
                                ),
                            ]),
                        TextInput::make('broj_prijava_van_drzavnih_organa')
                            ->label('Број пријава ван државних органа и/или незапослена лица')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број пријава ван државних органа не може бити већи од укупног броја пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'ukupan_broj_prijava',
                                    ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa'],
                                    'Збир пријава мора бити једнак укупном броју пријава.'
                                ),
                            ]),
                    ])->columns(2),

                        Section::make('Старосна структура кандидата')
                            ->headerActions([
                        Action::make('info_starosna')
                            ->icon('heroicon-m-information-circle')
                            ->label('')
                            ->color('gray')
                            ->modalHeading('Старосна структура кандидата')
                            ->modalContent(new HtmlString('<p class="text-sm">Ови подаци се прикупљају ради израчунавања кључних показатеља учинка (КПИ) у изборном поступку, пре свега у циљу праћења старосне структуре пријављених кандидата и анализе атрактивности радних места за млађе категорије становништва. Прикупљање и извештавање о овим подацима представља захтев Министарства државне управе и локалне самоуправе (МДУЛС). Подаци се могу израчунати на основу података из образаца пријаве, односно из матичног броја кандидата (датум рођења).</p>'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Затвори')
                            ->modalWidth('lg'),
                    ])
                    ->schema([
                        TextInput::make('prosecna_starost_kandidata')
                            ->label('Просечна старост кандидата у изборном поступку')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->suffix('година')
                            ->hintAction(
                                Action::make('info_prosecna_starost')
                                    ->icon('heroicon-m-information-circle')
                                    ->label('')
                                    ->color('gray')
                                    ->extraAttributes(['style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;color:rgb(156,163,175);'])
                                    ->modalHeading('Просечна старост кандидата у изборном поступку')
                                    ->modalContent(new HtmlString('<div class="space-y-3 text-sm"><div><p class="font-semibold">Шта представља?</p><p>Просечан број година свих кандидата који су се пријавили за конкретно радно место.</p></div><div><p class="font-semibold">Како се рачуна?</p><ol class="list-decimal list-inside space-y-2 mt-1"><li>Из матичног броја (ЈМБГ) издвојити датум рођења кандидата.</li><li>За сваког кандидата израчунати старост: Старост кандидата = Текућа година − Година рођења</li><li>Израчунава се просек:<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin:10px 0;flex-wrap:nowrap;"><em>Просечна старост</em> = <span style="display:inline-flex;flex-direction:column;text-align:center;"><span style="border-bottom:1px solid currentColor;padding:2px 12px;font-style:italic;">Збир година свих кандидата</span><span style="padding:2px 12px;font-style:italic;">Укупан број кандидата</span></span></div></li></ol></div></div>'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Затвори')
                                    ->modalWidth('lg')
                            ),
                        TextInput::make('udeo_kandidata_mladjih_od_30')
                            ->label('Удео кандидата млађих од 30 година')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->suffix('%')
                            ->hintAction(
                                Action::make('info_udeo_mladjih')
                                    ->icon('heroicon-m-information-circle')
                                    ->label('')
                                    ->color('gray')
                                    ->extraAttributes(['style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;color:rgb(156,163,175);'])
                                    ->modalHeading('Удео кандидата млађих од 30 година')
                                    ->modalContent(new HtmlString('<div class="space-y-3 text-sm"><div><p class="font-semibold">Шта представља?</p><p>Проценат кандидата који у тренутку расписивања конкурса имају мање од 30 година.</p></div><div><p class="font-semibold">Како се рачуна?</p><ol class="list-decimal list-inside space-y-2 mt-1"><li>На основу датума рођења утврдити који кандидати су млађи од 30 година.</li><li>Применити формулу:<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin:10px 0;flex-wrap:nowrap;"><em>Удео млађих од 30</em> = <span style="display:inline-flex;flex-direction:column;text-align:center;"><span style="border-bottom:1px solid currentColor;padding:2px 12px;font-style:italic;">Број кандидата млађих од 30 година</span><span style="padding:2px 12px;font-style:italic;">Укупан број кандидата</span></span><span style="white-space:nowrap;">× 100</span></div></li></ol></div><p>Резултат се исказује у процентима (%).</p></div>'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Затвори')
                                    ->modalWidth('lg')
                            ),
                    ])->columns(2),

                        Section::make('Валидне пријаве')
                            ->schema([
                        TextInput::make('broj_validnih_prijava')
                            ->label('Број валидних пријава')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број валидних пријава не може бити већи од укупног броја пријава.',
                            ]),
                        TextInput::make('broj_validnih_prijava_iz_organa')
                            ->label('Број валидних пријава из органа који расписује конкурс')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број валидних пријава из органа не може бити већи од укупног броја валидних пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_validnih_prijava',
                                    ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa'],
                                    'Збир валидних пријава мора бити једнак укупном броју валидних пријава.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupne = (int) $get('broj_prijava_iz_organa');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupne) {
                                        $fail('Број валидних пријава из органа не може бити већи од укупног броја пријава из органа.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_validnih_prijava_iz_drugog_organa')
                            ->label('Број валидних пријава из других органа државне управе')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број валидних пријава из другог органа не може бити већи од укупног броја валидних пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_validnih_prijava',
                                    ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa'],
                                    'Збир валидних пријава мора бити једнак укупном броју валидних пријава.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupne = (int) $get('broj_prijava_iz_drugih_organa');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupne) {
                                        $fail('Број валидних пријава из других органа не може бити већи од укупног броја пријава из других органа.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_validnih_prijava_van_drzavnih_organa')
                            ->label('Број валидних пријава ван органа државне управе и/или незапослена лица')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број валидних пријава ван државних органа не може бити већи од укупног броја валидних пријава.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_validnih_prijava',
                                    ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa'],
                                    'Збир валидних пријава мора бити једнак укупном броју валидних пријава.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupne = (int) $get('broj_prijava_van_drzavnih_organa');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupne) {
                                        $fail('Број валидних пријава ван државних органа не може бити већи од укупног броја пријава ван државних органа.');
                                    }
                                },
                            ]),
                    ])->columns(2),
                    ]),

                Tab::make('Провере компетенција кандидата')
                    ->schema([
                        Section::make('ОФК провера')
                            ->schema([
                        TextInput::make('broj_kandidata_za_koje_se_zakazuju_ofk')
                            ->label('Број кандидата за које се заказују ОФК')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupne = (int) $get('ukupan_broj_prijava');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupne) {
                                        $fail('Број кандидата за ОФК не може бити већи од укупног броја пријава.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_pocetka_provere_ofk', 'Датум спровођења провере ОФК', 'datum_pregleda_prijava', 'прегледа пријава')
                            ->helperText('Уколико је било више дана провере, унети први датум'),
                        TextInput::make('broj_neodazvanih_kandidata_ofk')
                            ->label('Број кандидата који се није одазвао позиву на ОФК')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $zakazani = (int) $get('broj_kandidata_za_koje_se_zakazuju_ofk');
                                    $ispunili = (int) $get('broj_kandidata_koji_su_ispunlii_merila_ofk');
                                    if ($value !== null && $value !== '' && $zakazani && ($ispunili + (int)$value) > $zakazani) {
                                        $fail('Збир кандидата који су испунили мерила ОФК и кандидата који се нису одазвали на ОФК не сме бити већи од броја кандидата за које се заказују ОФК.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_kandidata_koji_su_ispunlii_merila_ofk')
                            ->label('Број кандидата који су испунили мерила ОФК')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->helperText('Укључујући и кандидате којима су се оцене признале')
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Укупан број кандидата се може пронаћи у извештају СУК-а')
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $zakazani = (int) $get('broj_kandidata_za_koje_se_zakazuju_ofk');
                                    $neodazvani = (int) $get('broj_neodazvanih_kandidata_ofk');
                                    if ($value !== null && $value !== '' && $zakazani && ($neodazvani + (int)$value) > $zakazani) {
                                        $fail('Збир кандидата који су испунили мерила ОФК и кандидата који се нису одазвали на ОФК не сме бити већи од броја кандидата за које се заказују ОФК.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_ofk_izvestaja', 'Датум ОФК извештаја', 'datum_pocetka_provere_ofk', 'спровођења провере ОФК')
                            ->helperText('Датум креирања извештаја СУКа'),
                    ])->columns(2),

                        Section::make('ПФК провера')
                            ->schema([
                        TextInput::make('broj_kandidata_za_koje_se_zakazuju_pfk')
                            ->label('Број кандидата за које се заказују ПФК')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath())),
                        static::makeDateField('datum_pocetka_provere_pfk', 'Датум почетка провере ПФК', 'datum_ofk_izvestaja', 'ОФК извештаја')
                            ->helperText('Уколико је било више дана провере, унети први датум'),
                        static::makeDateField('datum_pfk_izvestaja', 'Датум ПФК извештаја', 'datum_pocetka_provere_pfk', 'почетка провере ПФК')
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Иако се ова форма извештаја тренутно не израђује, њено увођење омогућава праћење времена вредновања одговора кандидата и представља важан показатељ ефикасности изборног поступка.'),
                        TextInput::make('broj_kandidata_koji_su_ispunlii_merila_pfk')
                            ->label('Број кандидата који су испунили мерила ПФК')
                            ->numeric()->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ofk = (int) $get('broj_kandidata_koji_su_ispunlii_merila_ofk');
                                    if ($value && $ofk && (int)$value > $ofk) {
                                        $fail('Број кандидата који су испунили мерила ПФК не сме бити већи од броја кандидата који су испунили мерила ОФК.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_neodazvanih_kandidata_pfk')
                            ->label('Број кандидата који се није одазвао позиву на ПФК')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pfk = (int) $get('broj_kandidata_koji_su_ispunlii_merila_pfk');
                                    if ($value !== null && $value !== '' && $pfk && (int)$value > $pfk) {
                                        $fail('Број кандидата који се није одазвао позиву на ПФК не сме бити већи од броја кандидата који су испунили мерила ПФК.');
                                    }
                                },
                            ]),
                        Select::make('provera_pfk')
                            ->label('Провера ПФК')
                            ->relationship('proveraPfkRelation', 'provera_pfk', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                    ])->columns(2),

                        Section::make('ПК провера')
                            ->schema([
                        TextInput::make('broj_kandidata_za_koje_se_zakazuju_pk')
                            ->label('Број кандидата за које се заказују ПК')
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pfk = (int) $get('broj_kandidata_koji_su_ispunlii_merila_pfk');
                                    if ($value !== null && $value !== '' && $pfk && (int)$value > $pfk) {
                                        $fail('Број кандидата за које се заказују ПК не сме бити већи од броја кандидата koji су испунили мерила ПФК.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_pocetka_provere_pk', 'Датум почетка провере ПК', 'datum_pfk_izvestaja', 'ПФК извештаја')
                            ->helperText('Уколико је било више дана провере, унети први датум.'),
                        static::makeDateField('datum_pk_izvestaja', 'Датум ПК извештаја', 'datum_pocetka_provere_pk', 'почетка провере ПК')
                            ->helperText('Датум креирања извештаја СУКа'),
                        TextInput::make('broj_kandidata_ispunili_merila_pk')
                            ->label('Број кандидата који су испунили мерила на ПК')
                            ->numeric()->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pfk = (int) $get('broj_kandidata_koji_su_ispunlii_merila_pfk');
                                    if ($value && $pfk && (int)$value > $pfk) {
                                        $fail('Број кандидата који су испунили мерила ПК не сме бити већи од броја кандидата који су испунили мерила ПФК.');
                                    }
                                },
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pk = (int) $get('broj_kandidata_za_koje_se_zakazuju_pk');
                                    if ($value !== null && $value !== '' && $pk && (int)$value > $pk) {
                                        $fail('Број кандидата koji су испунили мерила на ПК не сме бити већи од броја кандидата за које се заказују ПК.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_neodazvanih_kandidata_pk')
                            ->label('Број кандидата који се нису одазвали на проверу ПК')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $zakazani = (int) $get('broj_kandidata_za_koje_se_zakazuju_pk');
                                    $ispunili = (int) $get('broj_kandidata_ispunili_merila_pk');
                                    if ($value !== null && $value !== '' && $zakazani && ($ispunili + (int)$value) > $zakazani) {
                                        $fail('Збир кандидата koji су испунили мерила ПК и кандидата koji се нису одазвали на ПК не сме бити већи од броја кандидата за које се заказују ПК.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_dana_sprovodjenja_pk_provera')
                            ->label('Број дана спровођења ПК провера')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath())),
                    ])->columns(2),

                    ]),

                Tab::make('Завршетак поступка и кандидати')
                    ->schema([
                        Section::make('Завршна фаза поступка')
                            ->schema([
                        static::makeDateField('datum_predaje_dokumentacije', 'Датум предаје документације', 'datum_pocetka_provere_pk', 'почетка провере ПК')
                            ->helperText('Докази које прилажу кандидати који су успешно прошли фазе изборног поступка.'),
                        TextInput::make('broj_neodazvanih_kandidata_dokumentacija')
                            ->label('Број кандидата који се није одазвао позиву на доставу документације')
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pk = (int) $get('broj_kandidata_ispunili_merila_pk');
                                    if ($value !== null && $value !== '' && $pk && (int)$value > $pk) {
                                        $fail('Број кандидата који се није одазвао позиву на доставу документације не сме бити већи од броја кандидата који су испунили мерила ПК.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_pocetka_sprovodjenja_intervjua', 'Датум спровођења завршног интервјуа', 'datum_predaje_dokumentacije', 'предаје документације'),
                        static::makeDateField('datum_izvestaja_sa_zavrsnog_intervjua', 'Датум извештаја са завршног интервјуа', 'datum_pocetka_sprovodjenja_intervjua', 'спровођења завршног интервјуа')
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Иако се ова форма извештаја тренутно не израђује, њено увођење омогућава праћење времена вредновања одговора кандидата и представља важан показатељ ефикасности изборног поступка.'),
                        TextInput::make('broj_odazvanih_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број одазваних кандидата на завршном разговору')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pk = (int) $get('broj_kandidata_ispunili_merila_pk');
                                    $neodazvani = (int) $get('broj_neodazvanih_kandidata_dokumentacija');
                                    $max = $pk - $neodazvani;
                                    if ($value !== null && $value !== '' && $pk && (int)$value > $max) {
                                        $fail('Број одазваних кандидата на завршном разговору не сме бити већи од разлике броја кандидата koji су испунили мерила ПК и броја кандидата koji се нису одазвали на доставу документације.');
                                    }
                                },
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pk = (int) $get('broj_kandidata_ispunili_merila_pk');
                                    $neodazvani_zavrsni = (int) $get('broj_neodazvanih_kandidata_zavrsni_razgovor');
                                    if ($value !== null && $value !== '' && $pk && ($neodazvani_zavrsni + (int)$value) > $pk) {
                                        $fail('Збир одазваних и неодазваних кандидата на завршном разговору не сме бити већи од броја кандидата koji су испунили мерила ПК.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_neodazvanih_kandidata_zavrsni_razgovor')
                            ->label('Број кандидата који се није одазвао позиву на завршном разговору')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $pk = (int) $get('broj_kandidata_ispunili_merila_pk');
                                    $odazvani_zavrsni = (int) $get('broj_odazvanih_kandidata_na_zavrsnom_razgovoru');
                                    if ($value !== null && $value !== '' && $pk && ($odazvani_zavrsni + (int)$value) > $pk) {
                                        $fail('Збир одазваних и неодазваних кандидата на завршном разговору не сме бити већи од броја кандидата koji су испунили мерила ПК.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_dostavljanja_liste_rukovodiocu_organa', 'Датум достављања листе руководиоцу органа', 'datum_pocetka_sprovodjenja_intervjua', 'спровођења завршног интервјуа'),
                        static::makeDateField('datum_donosenja_resenja_o_izabranom_kandidatu', 'Датум доношења решења о изабраном кандидату', 'datum_dostavljanja_liste_rukovodiocu_organa', 'достављања листе руководиоцу органа'),
                        static::makeDateField('datum_stupanja_na_rad', 'Датум ступања на рад', 'datum_donosenja_resenja_o_izabranom_kandidatu', 'доношења решења о изабраном кандидату')
                            ->helperText('Датум ступања на рад првог извршиоца'),
                    ])->columns(3),

                        Section::make('Листа кандидата који су испунили мерила за избор')
                            ->schema([
                        static::makeDateField('datum_formiranja_liste_kandidata', 'Дан формирања листе кандидата који су испунили мерила у изборном поступку')
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Члан 57, став 7, Закона о државним службеницима каже: На интернет презентацији органа државне управе који је огласио конкурс и Службе за управљање кадровима објављује се листа кандидата под шифром њихове пријаве и име и презиме кандидата који је изабран у конкурсном поступку.')
                            ->columnSpanFull(),
                        TextInput::make('broj_kandidata_na_listi')
                            ->label('Број кандидата на листи')
                            ->numeric()->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $odazvani = (int) $get('broj_odazvanih_kandidata_na_zavrsnom_razgovoru');
                                    if ($value !== null && $value !== '' && $odazvani && (int)$value > $odazvani) {
                                        $fail('Број кандидата на листи не сме бити већи од броја одазваних кандидата на завршном разговору.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_kandidata_iz_organa_na_listi')
                            ->label('Број кандидата из органа који расписује конкурс на листи')
                            ->numeric()->minValue(0)
                            ->lte('broj_kandidata_na_listi')
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_kandidata_iz_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_iz_drugog_drzavnog_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_van_drzavnih_organa_na_listi');
                            })
                            ->validationMessages([
                                'lte' => 'Број кандидата из органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора битиједнак укупном броју кандидата на листи.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $max = (int) $get('broj_validnih_prijava_iz_organa');
                                    if ($value !== null && $value !== '' && $max > 0 && (int)$value > $max) {
                                        $fail('Број кандидата из органа на листи не може бити већи од броја валидних пријава из органа.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_kandidata_iz_drugog_drzavnog_organa_na_listi')
                            ->label('Број кандидата из других органа државне управе на листи')
                            ->numeric()->minValue(0)
                            ->lte('broj_kandidata_na_listi')
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_kandidata_iz_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_iz_drugog_drzavnog_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_van_drzavnih_organa_na_listi');
                            })
                            ->validationMessages([
                                'lte' => 'Број кандидата из другог државног органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора битиједнак укупном броју кандидата на листи.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $max = (int) $get('broj_validnih_prijava_iz_drugog_organa');
                                    if ($value !== null && $value !== '' && $max > 0 && (int)$value > $max) {
                                        $fail('Број кандидата из других органа на листи не може бити већи од броја валидних пријава из других органа.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_kandidata_van_drzavnih_organa_na_listi')
                            ->label('Број кандидата ван органа државне управе и/или незапослена лица на листи')
                            ->numeric()->minValue(0)
                            ->lte('broj_kandidata_na_listi')
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_kandidata_iz_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_iz_drugog_drzavnog_organa_na_listi');
                                $livewire->validateOnly('data.broj_kandidata_van_drzavnih_organa_na_listi');
                            })
                            ->validationMessages([
                                'lte' => 'Број кандидата ван државних органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора битиједнак укупном броју кандидата на листи.'
                                ),
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $max = (int) $get('broj_validnih_prijava_van_drzavnih_organa');
                                    if ($value !== null && $value !== '' && $max > 0 && (int)$value > $max) {
                                        $fail('Број кандидата ван државних органа на листи не може бити већи од броја валидних пријава ван државних органа.');
                                    }
                                },
                            ]),
                    ])->columns(2),

                        Section::make('Изабрани кандидат')
                            ->schema([
                        Select::make('izabrani_kandidat')
                            ->label('Изабрани кандидат је из:')
                            ->relationship('izabraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Select::make('broj_bodova_izabranog_kandidata_na_ofk')
                            ->label('Број бодова на ОФК')
                            ->options(static::ofkScoreOptions()),
                        Select::make('broj_bodova_izabranog_kandidata_na_pfk')
                            ->label('Број бодова на ПФК')
                            ->options(static::pfkScoreOptions()),
                        Select::make('broj_bodova_izabranog_kandidata_na_pk')
                            ->label('Број бодова на ПК')
                            ->options(static::pkScoreOptions()),
                        Select::make('broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број бодова на завршном разговору')
                            ->options(static::zavrsniScoreOptions()),
                    ])->columns(3),

                        Section::make('Другопласирани кандидат')
                            ->schema([
                        Select::make('drugoplasirani_kandidat')
                            ->label('Другопласирани кандидат је из:')
                            ->relationship('drugoplasiraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Select::make('broj_bodova_drugplasiranog_kandidata_na_ofk')
                            ->label('Број бодова на ОФК')
                            ->options(static::ofkScoreOptions()),
                        Select::make('broj_bodova_drugplasiranog_kandidata_na_pfk')
                            ->label('Број бодова на ПФК')
                            ->options(static::pfkScoreOptions()),
                        Select::make('broj_bodova_drugplasiranog_kandidata_na_pk')
                            ->label('Број бодова на ПК')
                            ->options(static::pkScoreOptions()),
                        Select::make('broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број бодова на завршном разговору')
                            ->options(static::zavrsniScoreOptions()),
                    ])->columns(3),

                    ]),

                Tab::make('Статус и жалбе')
                    ->schema([
                        TextInput::make('broj_primljenih_izvrsilaca')
                            ->label('Број примљених извршилаца')
                            ->numeric()->minValue(0)
                            ->lte('broj_izvrsilaca')
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->validationMessages([
                                'lte' => 'Број примљених извршилаца не може бити већи од броја извршилаца.',
                            ])
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $lista = (int) $get('broj_kandidata_na_listi');
                                    if ($value !== null && $value !== '' && $lista && (int)$value > $lista) {
                                        $fail('Број примљених извршилаца не сме бити већи од броја кандидата на листи.');
                                    }
                                },
                            ]),
                        TextInput::make('ocena_sa_vrednovanja')
                            ->label('Оцена са вредновања')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->helperText('Уколико је кандидат радио дуже од 6 месеци након ступања на рад.'),
                        TextInput::make('broj_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Број жалби на решење о одбацивању пријаве')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupno = (int) $get('ukupan_broj_prijava');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupno) {
                                        $fail('Број жалби на решење о одбацивању пријаве не може бити већи од укупног броја пријава.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_zalbi_na_resenje_o_prijemu_u_radni_odnos')
                            ->label('Број жалби на решење о пријему у радни однос')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ukupno = (int) $get('ukupan_broj_prijava');
                                    if ($value !== null && $value !== '' && (int)$value > $ukupno) {
                                        $fail('Број жалби на решење о пријему у радни однос не може бити већи од укупног броја пријава.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Број усвојених жалби на решење о одбацивању пријаве')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $zalbi = (int) $get('broj_zalbi_na_resenje_o_odbacaju_prijave');
                                    if ($value !== null && $value !== '' && (int)$value > $zalbi) {
                                        $fail('Број усвојених жалби не може бити већи од броја жалби на решење о одбацивању пријаве.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_izvrsilaca_ponovno_oglasavanje')
                            ->label('Број извршилаца - поновно оглашавање')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Односи се на број извршилаца за радна места која су у току исте календарске године поново оглашена, услед чињенице да претходним огласом није попуњен планирани број извршилаца.'),
                    ])->columns(2),

                ])->columnSpanFull(),
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
                TextColumn::make('vrstaOrganaRelation.vrsta_organa')
                    ->label('Врста органа')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('organRelation.organ')
                    ->label('Орган')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('naziv_radnog_mesta')
                    ->label('Назив радног места')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('zvanjeRelation.zvanje')
                    ->label('Звање')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mestaRada.mesto')
                    ->label('Место рада')
                    ->searchable()
                    ->state(fn ($record) => $record->mestaRada->unique('id')->pluck('mesto')->join(', '))
                    ->tooltip(function ($record) {
                        $mesta = $record->mestaRada->unique('id')->pluck('mesto');
                        return $mesta->count() > 3 ? $mesta->join(', ') : null;
                    }),
            ])
            ->filters([
                SelectFilter::make('organ')
                    ->label('Орган')
                    ->relationship('organRelation', 'organ')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('zvanje')
                    ->label('Звање')
                    ->relationship('zvanjeRelation', 'zvanje', fn ($query) => $query->orderBy('id', 'asc'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('mestaRada')
                    ->label('Место рада')
                    ->relationship('mestaRada', 'mesto')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('godina_oglasavanja')
                    ->label('Датум оглашавања (година)')
                    ->options(fn () => PodaciORadnomMestu::query()
                        ->whereNotNull('datum_oglasavanja')
                        ->selectRaw('YEAR(datum_oglasavanja) as godina')
                        ->distinct()
                        ->orderBy('godina', 'desc')
                        ->pluck('godina', 'godina')
                        ->toArray()
                    )
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn ($q, $value) =>
                            $q->whereYear('datum_oglasavanja', $value)
                        )
                    ),
                SelectFilter::make('tip_konkursa')
                    ->label('Тип конкурса')
                    ->relationship('tipKonkursaRelation', 'tip_konkursa')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('statistika')
                        ->label('Статистика')
                        ->icon('heroicon-o-chart-bar')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Статистика конкурса')
                        ->modalSubheading(fn ($record) => 'Преглед статистичких података за овај конкурс')
                        ->modalWidth('7xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Затвори')
                        ->schema(fn ($record) => [
                            // SEKCIJA 1: Временски периоди
                            Section::make('Временски периоди')
                                ->description('Периоди трајања конкурсних поступака')
                                ->icon('heroicon-o-clock')
                                ->schema([
                                    TextEntry::make('vreme_trajanja')
                                        ->label('Време трајања конкурсног поступка')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_donosenja_resenja_o_pokretanju_postupka', 'datum_stupanja_na_rad'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између доношења решења и ступања на рад'),

                                    TextEntry::make('vreme_trajanja_izbornog_postupka')
                                        ->label('Време трајања изборног поступка')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pregleda_prijava', 'datum_dostavljanja_liste_rukovodiocu_organa'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и достављања листе'),

                                    TextEntry::make('vreme_od_saglasnosti_do_resenja')
                                        ->label('Време од сагласности Владе до решења')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_dobijanja_saglasnosti_vlade', 'datum_donosenja_resenja_o_pokretanju_postupka'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између добијања сагласности и решења'),

                                    TextEntry::make('vreme_od_obavestenja_suka_do_resenja')
                                        ->label('Време од обавештења СУК-а до решења')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_dobijanja_obavestenja_od_suka', 'datum_donosenja_resenja_o_pokretanju_postupka'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између обавештења СУК-а и решења'),

                                    TextEntry::make('vreme_od_obavestenja_suka_do_prvog_sastanka')
                                        ->label('Време од обавештења СУК-а до првог састанка')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_dobijanja_obavestenja_od_suka', 'datum_odrzavanja_prvog_sastanka'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између обавештења и првог састанка'),

                                    TextEntry::make('vreme_od_prvog_sastanka_do_oglasavanja')
                                        ->label('Време од првог састанка до оглашавања')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_odrzavanja_prvog_sastanka', 'datum_oglasavanja'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између првог састанка и оглашавања конкурса'),

                                    TextEntry::make('vreme_od_oglasavanja_do_pregleda_prijava')
                                        ->label('Време од оглашавања до прегледа пријава')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_oglasavanja', 'datum_pregleda_prijava'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између оглашавања и прегледа пријава'),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(false),

                            // SEKCIJA 2: Интервали између датума
                            Section::make('Интервали између датума')
                                ->description('Временски размаци између кључних догађаја')
                                ->icon('heroicon-o-calendar-days')
                                ->schema([
                                    TextEntry::make('vreme_od_pregleda_prijava_do_pocetka_provere_ofk')
                                        ->label('Време од прегледа пријава до почетка провере ОФК')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pregleda_prijava', 'datum_pocetka_provere_ofk'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и почетка провере ОФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 2),

                                    TextEntry::make('vreme_od_pocetka_provere_ofk_do_pocetka_provere_pfk')
                                        ->label('Време од почетка провере ОФК до почетка провере ПФК')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pocetka_provere_ofk', 'datum_pocetka_provere_pfk'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ОФК и почетка провере ПФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 2),

                                    TextEntry::make('vreme_od_pregleda_prijava_do_pocetka_provere_pfk')
                                        ->label('Време трајања од прегледа пријава до ПФК')
                                        ->state(function ($record) {
                                            if ($record->tip_konkursa == 2) {
                                                return static::dateDiffInDays($record, 'datum_pregleda_prijava', 'datum_pocetka_provere_pfk');
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и почетка провере ПФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 1),

                                    TextEntry::make('vreme_od_pocetka_provere_pfk_do_pocetka_provere_pk')
                                        ->label('Време од почетка провере ПФК до почетка провере ПК')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pocetka_provere_pfk', 'datum_pocetka_provere_pk'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ПФК и почетка провере ПК'),

                                    TextEntry::make('vreme_od_pocetka_provere_pk_do_predaje_dokumentacije')
                                        ->label('Време од почетка провере ПК до предаје документације')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pocetka_provere_pk', 'datum_predaje_dokumentacije'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ПК и предаје документације'),

                                    TextEntry::make('vreme_od_predaje_dokumentacije_do_intervjua')
                                        ->label('Време од предаје документације до спровођења интервјуа')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_predaje_dokumentacije', 'datum_pocetka_sprovodjenja_intervjua'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између предаје документације и почетка спровођења интервјуа'),

                                    TextEntry::make('vreme_od_intervjua_do_dostavljanja_liste')
                                        ->label('Време од спровођења интервјуа до достављања листе')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_pocetka_sprovodjenja_intervjua', 'datum_dostavljanja_liste_rukovodiocu_organa'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка спровођења интервјуа и достављања листе руководиоцу'),

                                    TextEntry::make('vreme_od_dostavljanja_liste_do_resenja')
                                        ->label('Време од достављања листе до доношења решења')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_dostavljanja_liste_rukovodiocu_organa', 'datum_donosenja_resenja_o_izabranom_kandidatu'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између достављања листе и доношења решења о изабраном кандидату'),

                                    TextEntry::make('vreme_od_resenja_do_stupanja_na_rad')
                                        ->label('Време од доношења решења до ступања на рад')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_donosenja_resenja_o_izabranom_kandidatu', 'datum_stupanja_na_rad'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између доношења решења и ступања на рад'),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(false),

                            // SEKCIJA 3: Додатне статистике
                            Section::make('Додатне статистике')
                                ->description('Додатне анализе конкурсног поступка')
                                ->icon('heroicon-o-chart-bar')
                                ->schema([
                                    TextEntry::make('vreme_trajanja_iz_ugla_kandidata')
                                        ->label('Време трајања из угла кандидата')
                                        ->state(fn ($record) => static::dateDiffInDays($record, 'datum_oglasavanja', 'datum_stupanja_na_rad'))
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између оглашавања и ступања на рад')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 2),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(false),

                            // SEKCIJA 4: Напредна анализа (за више статистика)
                            Section::make('Напредна анализа')
                                ->description('Детаљна анализа података')
                                ->icon('heroicon-o-calculator')
                                ->schema([
                                    // Ovde će se dodavati dodatne statistike
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(true)
                                ->hidden(fn () => true), // Sakrij dok je prazna
                        ]),
                    ViewAction::make()
                        ->label('Преглед'),
                    ReplicateAction::make()
                        ->label('Дуплирај')
                        ->modalHeading('Дуплирај радно место?')
                        ->modalSubmitActionLabel('Дуплирај')
                        ->modalCancelActionLabel('Откажи')
                        ->after(function ($replica, $record) {
                            // Kopiraj mestaRada relaciju (many-to-many)
                            $mestaRadaIds = $record->mestaRada()->pluck('sifarnik_mesta.id')->toArray();
                            $replica->mestaRada()->sync($mestaRadaIds);

                            // Log aktivnost
                            activity('podaci_o_radnom_mestu')
                                ->performedOn($replica)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'attributes' => $replica->attributesToArray(),
                                    'original_id' => $record->id,
                                    'mesta_rada' => $mestaRadaIds,
                                ])
                                ->tap(function ($activity) {
                                    $activity->ip_address = request()->ip();
                                })
                                ->log('Duplirano radno mesto');
                        }),
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
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->persistFiltersInSession()
            ->deferLoading()
            ->paginated([10, 25, 50, 100]);
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
            'index' => ListPodaciORadnomMestus::route('/'),
            'create' => CreatePodaciORadnomMestu::route('/create'),
            'edit' => EditPodaciORadnomMestu::route('/{record}/edit'),
        ];
    }
}
