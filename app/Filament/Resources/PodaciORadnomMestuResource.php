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
use App\Models\Setting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use App\Models\SifarnikKodoviGradova;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
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
                $ukupan = $get($totalField);

                // Не проверавај збир док укупан број није унет (и већи од 0).
                if ($ukupan === null || $ukupan === '' || (int) $ukupan <= 0) {
                    return;
                }

                // Проверу приказуј тек када су сва три поља попуњена.
                $sum = 0;
                foreach ($sumFields as $field) {
                    $val = $get($field);
                    if ($val === null || $val === '') {
                        return;
                    }
                    $sum += (int) $val;
                }

                if ($sum != (int) $ukupan) {
                    $fail($errorMessage);
                }
            };
        };
    }

    /**
     * afterStateUpdated хендлер за поља збир-групе: при промени било ког поља
     * ревалидира цео сет (укупно + сабирке) да застареле поруке нестану са
     * осталих поља кад збир постане исправан.
     */
    protected static function revalidateSumGroup(string $totalField, array $sumFields): Closure
    {
        return function ($livewire) use ($totalField, $sumFields) {
            // Прво сабирке (да застареле поруке нестану кад збир постане исправан),
            // па укупно поље. Кад је све исправно ниједно не баца изузетак и све се
            // очисти; ако збир није исправан, прво неисправно поље прикаже поруку.
            foreach (array_merge($sumFields, [$totalField]) as $field) {
                $livewire->validateOnly("data.{$field}");
            }
        };
    }

    /**
     * Validacija: uspešno završen javni konkurs mora imati popunjena sva datumska polja (osim 3 izuzeta).
     */
    protected static function uspesnoZavrsenValidationRule(): Closure
    {
        $requiredDates = [
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

            // Pripravnici ne prolaze PFK proveru, pa se ti datumi od njih ne traze.
            // 5 = Mladji savetnik - pripravnik, 31 = Mladji poreski savetnik - pripravnik.
            if (in_array((int) $get('zvanje'), [5, 31], true)) {
                unset($requiredDates['datum_pocetka_provere_pfk'], $requiredDates['datum_pfk_izvestaja']);
            }

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

    protected static function dateChainLabels(): array
    {
        return [
            'datum_dobijanja_saglasnosti_vlade'             => 'Датум добијања сагласности Владе',
            'datum_donosenja_resenja_o_pokretanju_postupka' => 'Датум доношења решења о покретању поступка',
            'datum_dobijanja_obavestenja_od_suka'           => 'Датум добијања обавештења од СУКа',
            'datum_odrzavanja_prvog_sastanka'               => 'Датум одржавања првог sastanka',
            'datum_oglasavanja'                             => 'Датум оглашавања',
            'datum_pregleda_prijava'                        => 'Датум прегледа пријава',
            'datum_pocetka_provere_ofk'                     => 'Датум спровођења провере ОФК',
            'datum_ofk_izvestaja'                           => 'Датум ОФК извештаја',
            'datum_pocetka_provere_pfk'                     => 'Датум почетка провере ПФК',
            'datum_pfk_izvestaja'                           => 'Датум ПФК извештаја',
            'datum_pocetka_provere_pk'                      => 'Датум почетка провере ПК',
            'datum_pk_izvestaja'                            => 'Датум ПК извештаја',
            'datum_predaje_dokumentacije'                   => 'Датум предаје документације',
            'datum_pocetka_sprovodjenja_intervjua'          => 'Датум спровођења завршног интервјуа',
            'datum_izvestaja_sa_zavrsnog_intervjua'         => 'Датум извештаја са завршног интервјуа',
            'datum_dostavljanja_liste_rukovodiocu_organa'   => 'Датум достављања листе руководиоцу органа',
            'datum_donosenja_resenja_o_izabranom_kandidatu' => 'Датум доношења решења о изабраном кандидату',
            'datum_stupanja_na_rad'                         => 'Датум ступања на рад',
        ];
    }

    /**
     * Пулсирајућа инфо-иконица уз поље (замена за сиви hintIconTooltip који корисници
     * не примете). Клик отвара читљив модал са пуним текстом. CSS класа `kprm-hint-pulse`
     * је дефинисана у AdminPanelProvider (renderHook STYLES_AFTER).
     */
    protected static function infoHintAction(string $name, string $tekst, string $heading = 'Објашњење'): Action
    {
        return Action::make($name)
            ->icon('heroicon-m-information-circle')
            ->label('')
            ->color('warning')
            ->extraAttributes([
                'class' => 'kprm-hint-pulse',
                'style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;',
            ])
            ->modalHeading($heading)
            ->modalContent(new HtmlString('<div style="font-size:.875rem;line-height:1.5;white-space:pre-line;">'.e($tekst).'</div>'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Затвори')
            ->modalWidth('md');
    }

    /**
     * Рачуна старосну структуру из датума рођења (дд.мм.гггг, нови ред или зарез).
     * Користи се и за живи преглед у калкулатору и за упис. Ништа се не чува.
     * Враћа: ['ok'=>bool, 'greska'?, 'n','avg','mladji','pct','preskoceni'].
     */
    protected static function izracunajStarosnuStrukturu(?string $refRaw, ?string $datumiRaw): array
    {
        $parse = function (?string $s): ?Carbon {
            $s = trim((string) $s);
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/', $s, $m)) {
                [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
                [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            } else {
                return null;
            }
            return checkdate($mo, $d, $y) ? Carbon::create($y, $mo, $d) : null;
        };

        $ref = $parse($refRaw);
        if (! $ref) {
            return ['ok' => false, 'greska' => 'Унесите датум оглашавања конкурса (дд.мм.гггг).', 'preskoceni' => []];
        }

        $stavke = array_filter(array_map('trim', preg_split('/[\n,]+/', (string) $datumiRaw)));

        $validni = [];
        $preskoceni = [];
        foreach ($stavke as $s) {
            $b = $parse($s);
            if (! $b || $b->gt($ref)) {
                $preskoceni[] = $s;
                continue;
            }
            // Санити границе: старост на датум оглашавања између 18 и 90 година
            $starost = $b->diffInYears($ref);
            if ($starost < 18 || $starost > 90) {
                $preskoceni[] = $s;
                continue;
            }
            $validni[] = $b;
        }

        $n = count($validni);
        if ($n === 0) {
            return ['ok' => false, 'greska' => 'Нема исправних датума рођења (или су сви после датума оглашавања).', 'preskoceni' => $preskoceni];
        }

        $zbirStarosti = 0.0;
        $mladji = 0;
        $granica = $ref->copy()->subYears(30);
        foreach ($validni as $b) {
            // Прецизна старост на датум оглашавања (година + месец + дан)
            $zbirStarosti += $b->floatDiffInYears($ref);
            if ($b->gt($granica)) {
                $mladji++;
            }
        }

        return [
            'ok' => true,
            'n' => $n,
            'avg' => round($zbirStarosti / $n, 2),
            'mladji' => $mladji,
            'pct' => round($mladji / $n * 100, 2),
            'preskoceni' => $preskoceni,
        ];
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

        // Global chain check: catches non-adjacent violations (e.g. last date < first date with all middle dates empty)
        $chainLabels = static::dateChainLabels();
        $chainKeys   = array_keys($chainLabels);
        $pos         = array_search($name, $chainKeys);

        if ($pos !== false && $pos > 1) {
            $preceding = array_reverse(array_slice($chainLabels, 0, $pos - 1, true), true);

            $rules[] = fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $preceding) {
                if (!$value) return;

                $current = null;
                try { $current = Carbon::createFromFormat('d.m.Y', $value); } catch (\Exception $e) {}
                if (!$current || !$current->isValid()) {
                    try { $current = Carbon::createFromFormat('Y-m-d', $value); } catch (\Exception $e) {}
                }
                if (!$current || !$current->isValid()) return;

                foreach ($preceding as $field => $label) {
                    $prevRaw = $get($field);
                    if (!$prevRaw) continue;

                    $prev = null;
                    try { $prev = Carbon::createFromFormat('d.m.Y', $prevRaw); } catch (\Exception $e) {}
                    if (!$prev || !$prev->isValid()) {
                        try { $prev = Carbon::createFromFormat('Y-m-d', $prevRaw); } catch (\Exception $e) {}
                    }
                    if (!$prev || !$prev->isValid()) continue;

                    if ($current->lt($prev)) {
                        $fail("Датум мора бити после илиједнак датуму: {$label}");
                        return;
                    }
                }
            };
        }

        $cascadeChainKeys = array_keys(static::dateChainLabels());
        $cascadeChainPos  = array_search($name, $cascadeChainKeys);
        $cascadeFields    = ($cascadeChainPos !== false && $cascadeChainPos < count($cascadeChainKeys) - 1)
            ? array_slice($cascadeChainKeys, $cascadeChainPos + 1)
            : [];

        return TextInput::make($name)
            ->label($label)
            ->mask('99.99.9999')
            ->placeholder('дд.мм.гггг')
            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) use ($cascadeFields) {
                $livewire->validateOnly($component->getStatePath());
                foreach ($cascadeFields as $subsequent) {
                    $livewire->validateOnly('data.' . $subsequent);
                }
            })
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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'mestaRada',
                'vrstaOrganaRelation',
                'organRelation',
                'zvanjeRelation',
                'unosZavrsioKorisnik',
                'statusKonkursaRelation',
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
                            ->maxLength(1000)
                            ->required()
                            // Ћириличну валидацију примени само када је укључено у Подешавањима.
                            // Провера у тренутку валидације да прати тренутну вредност подешавања.
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                    if (Setting::get('cirilica_naziv_radnog_mesta', '1') === '1'
                                        && ! preg_match('/^[А-Ша-шЂЈЉЊЋЏђјљњћџ0-9\s.,\-–—():;\/\*\"\']+$/u', (string) $value)) {
                                        $fail('Назив радног места може садржати само ћирилична слова.');
                                    }
                                },
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
                            ->searchable()
                            ->live(),
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
                            ->relationship('zvanjeRelation', 'zvanje', function ($query) {
                                $organId = auth()->user()?->organ_id;
                                if (!$organId) {
                                    return $query->orderBy('id');
                                }
                                $hasMapped = SifarnikZvanje::where('organ_id', $organId)->exists();
                                return $hasMapped
                                    ? $query->where('organ_id', $organId)->orderBy('id')
                                    : $query->whereNull('organ_id')->orderBy('id');
                            })
                            ->getOptionLabelUsing(fn($value) => SifarnikZvanje::find($value)?->zvanje ?? $value)
                            ->required()
                            ->preload()
                            ->searchable(),
                        Select::make('oblastiRada')
                            ->label('Претежна област рада')
                            ->relationship('oblastiRada', 'oblast_rada', fn($query) => $query->orderBy('id', 'asc'))
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Repeater::make('mestaRada')
                            ->label('Места рада са бројем извршилаца')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('sifarnik_kodovi_gradova_id')
                                    ->label('Место рада')
                                    ->options(SifarnikKodoviGradova::whereNotNull('grad')->orderBy('grad')->pluck('grad', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->columnSpanFull()
                                    ->live(),

                                TextInput::make('broj_izvrsilaca')
                                    ->label('Број извршилаца')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                                    ->default(1),

                                TextInput::make('region')
                                    ->label('Регион')
                                    ->readOnly(),

                                TextInput::make('oblast')
                                    ->label('Област')
                                    ->readOnly(),

                                TextInput::make('kod_grada')
                                    ->label('Код града')
                                    ->readOnly(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Додај место рада')
                            ->defaultItems(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                            })
                            ->collapsed()
                            ->itemLabel(function (array $state): ?string {
                                $gradId = $state['sifarnik_kodovi_gradova_id'] ?? null;
                                $brojIzvrsilaca = $state['broj_izvrsilaca'] ?? 1;

                                if ($gradId) {
                                    $grad = SifarnikKodoviGradova::find($gradId);
                                    if ($grad) {
                                        return "{$grad->grad} ({$brojIzvrsilaca})";
                                    }
                                }

                                return 'Место рада';
                            })
                            ->collapsible()
                            ->afterStateHydrated(function (Repeater $component, $state, $record) {
                                if ($record && $record->mestaRada) {
                                    $data = $record->mestaRada->map(function ($grad) {
                                        return [
                                            'sifarnik_kodovi_gradova_id' => $grad->id,
                                            'broj_izvrsilaca'            => $grad->pivot->broj_izvrsilaca ?? 1,
                                            'region'                     => $grad->pivot->region,
                                            'oblast'                     => $grad->pivot->oblast,
                                            'kod_grada'                  => $grad->pivot->kod_grada,
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
                                    $gradovi = array_column($value, 'sifarnik_kodovi_gradova_id');
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
                        Placeholder::make('trenutni_status_prikaz')
                            ->label('Тренутни статус')
                            ->content(function (Get $get) {
                                $status = (int) $get('ishod_konkursa');
                                if (in_array($status, [1, 2, 3, 5], true) && filled($get('datum_ishoda_konkursa'))) {
                                    return \App\Models\SifarnikStatusKonkursa::find($status)?->status_konkursa ?? '—';
                                }
                                if (filled($get('datum_donosenja_resenja_o_pokretanju_postupka'))) {
                                    return 'У току';
                                }
                                return '—';
                            })
                            ->helperText('Статус „У току" се поставља аутоматски када је унет датум решења о покретању поступка, а исход конкурса још није изабран.'),
                        Select::make('ishod_konkursa')
                            ->label('Исход конкурса')
                            ->relationship('statusKonkursaRelation', 'status_konkursa', fn($query) => $query->where('id', '!=', 4)->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->live()
                            ->formatStateUsing(fn ($state) => in_array((int) $state, [1, 2, 3, 5], true) ? $state : null)
                            ->required(fn (Get $get) => filled($get('datum_ishoda_konkursa')))
                            ->afterStateUpdated(fn (Set $set, $state) => $state != 2 ? $set('razlog_neuspelog_konkursa', null) : null)
                            ->rules([static::uspesnoZavrsenValidationRule()]),
                        static::makeDateField('datum_ishoda_konkursa', 'Датум', 'datum_donosenja_resenja_o_pokretanju_postupka', 'доношења решења о покретању поступка')
                            ->hintAction(
                                Action::make('info_datum_ishoda')
                                    ->icon('heroicon-m-information-circle')
                                    ->label('')
                                    ->color('warning')
                                    ->extraAttributes(['class' => 'kprm-hint-pulse', 'style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;'])
                                    ->modalHeading('Датум по исходу конкурса')
                                    ->modalContent(new HtmlString('<div style="font-size:0.875rem;"><p style="margin-bottom:10px;">Који датум се уписује у поље „Датум", у зависности од исхода конкурса:</p><table style="width:100%;border-collapse:collapse;"><thead><tr><th style="text-align:left;padding:6px 10px;border:1px solid rgba(156,163,175,0.4);font-weight:600;">Исход конкурса</th><th style="text-align:left;padding:6px 10px;border:1px solid rgba(156,163,175,0.4);font-weight:600;">Датум који се уписује</th></tr></thead><tbody><tr><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Успешно завршен конкурс</td><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Датум доношења Решења о ступању на рад</td></tr><tr><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Делимично успешно завршен конкурс</td><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Датум доношења Решења о ступању на рад изабраног кандидата који је први ступио на рад (од више оглашених извршилаца)</td></tr><tr><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Неуспео конкурс</td><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Датум доношења Решења о неуспелом конкурсу</td></tr><tr><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Обустављен конкурс</td><td style="padding:6px 10px;border:1px solid rgba(156,163,175,0.4);vertical-align:top;">Датум доношења Решења о обустави конкурса</td></tr></tbody></table></div>'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Затвори')
                                    ->modalWidth('lg')
                            )
                            ->required(fn (Get $get) => filled($get('ishod_konkursa'))),
                        Select::make('razlog_neuspelog_konkursa')
                            ->label('Разлог неуспелог конкурса')
                            ->relationship('razlogNeuspelogKonkursaRelation', 'razlog', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->disabled(fn (Get $get) => $get('ishod_konkursa') != 2)
                            ->required(fn (Get $get) => $get('ishod_konkursa') == 2)
                            ->dehydrated(),
                    ])->columns(3),

                Tab::make('Покретање поступка и пријаве')
                    ->schema([
                        Section::make('Покретање поступка')
                            ->schema([
                        Toggle::make('konkurs_bez_saglasnosti_vlade')
                            ->label('Конкурс покренут без сагласности Владе')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, $state) => $state ? $set('datum_dobijanja_saglasnosti_vlade', null) : null)
                            ->columnSpanFull(),
                        static::makeDateField('datum_dobijanja_saglasnosti_vlade', 'Датум добијања сагласности Владе')
                            ->disabled(fn (Get $get) => (bool) $get('konkurs_bez_saglasnosti_vlade')),
                        static::makeDateField('datum_donosenja_resenja_o_pokretanju_postupka', 'Датум доношења решења о покретању поступка', 'datum_dobijanja_saglasnosti_vlade', 'добијања сагласности Владе'),
                        static::makeDateField('datum_dobijanja_obavestenja_od_suka', 'Датум добијања обавештења од СУКа', 'datum_donosenja_resenja_o_pokretanju_postupka', 'доношења решења о покретању поступка'),
                        static::makeDateField('datum_odrzavanja_prvog_sastanka', 'Датум одржавања првог састанка', 'datum_dobijanja_obavestenja_od_suka', 'добијања обавештења од СУКа'),
                        static::makeDateField('datum_oglasavanja', 'Датум оглашавања', 'datum_odrzavanja_prvog_sastanka', 'одржавања првог sastanka'),
                        static::makeDateField('datum_pregleda_prijava', 'Датум прегледа пријава', 'datum_oglasavanja', 'оглашавања'),
                    ])->columns(3),

                        Section::make('Пристигле пријаве')
                            ->schema([
                        TextInput::make('ukupan_broj_prijava')
                            ->label('Укупан број пристиглих пријава')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('ukupan_broj_prijava', ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa'])),
                        TextInput::make('broj_prijava_iz_organa')
                            ->label('Број пристиглих пријава кандидата из органа')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('ukupan_broj_prijava', ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa']))
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
                            ->label('Број пристиглих пријава кандидата из других државних органа')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('ukupan_broj_prijava', ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa']))
                            ->hintAction(self::infoHintAction('info_prijave_iz_organa', 'Не односи се на органе локалне самоуправе и органе аутономне покрајине'))
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
                            ->label('Број пристиглих пријава кандидата ван државних органа, укључујући незапослена лица')
                            ->numeric()->minValue(0)
                            ->lte('ukupan_broj_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('ukupan_broj_prijava', ['broj_prijava_iz_organa', 'broj_prijava_iz_drugih_organa', 'broj_prijava_van_drzavnih_organa']))
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
                        Actions::make([
                            Action::make('kalkulator_starosti')
                                ->label('Калкулатор старости')
                                ->icon('heroicon-m-calculator')
                                ->color('primary')
                                ->modalHeading('Калкулатор старосне структуре')
                                ->fillForm(fn (Get $get) => ['referentni_datum' => $get('datum_oglasavanja')])
                                ->schema([
                                    TextInput::make('referentni_datum')
                                        ->label('Датум оглашавања конкурса')
                                        ->helperText('Поље „Датум оглашавања конкурса" је информативне природе. Уколико је празно морате га унети у одговарајуће поље у апликацији.')
                                        ->disabled()
                                        ->dehydrated(),
                                    Textarea::make('datumi_rodjenja')
                                        ->label('Датуми рођења кандидата')
                                        ->hintAction(self::infoHintAction('info_kalkulator', "Како се користи калкулатор:\n\n• Датум оглашавања се преузима из форме и служи као референтна тачка (ако је празан, унесите га прво у форми).\n• У поље испод унесите датуме рођења кандидата — сваки у нов ред или одвојене зарезом. Формат: дд.мм.гггг (нпр. 15.03.1990 или 5.5.1990).\n• „Резултат\" се сам освежава чим изађете из поља — приказује просечну старост и удео млађих од 30 година.\n• Неисправни уноси (погрешан формат, немогућ датум) се прескачу и наведени су под „Прескочено\".\n• Кликом на „Израчунај и упиши\" вредности се уписују у поља Просечна старост и Удео млађих од 30.\n\nНапомена: унети датуми се нигде не чувају — користе се само за израчунавање.", 'Упутство за калкулатор'))
                                        ->rows(8)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Унесите датуме рођења (дд.мм.гггг) — сваки у нов ред или одвојене зарезом (може и мешано). Пример: 15.03.1990, 22.11.1985.'),
                                    Placeholder::make('pregled_rezultata')
                                        ->label('Резултат')
                                        ->content(function (Get $get): HtmlString {
                                            $r = static::izracunajStarosnuStrukturu($get('referentni_datum'), $get('datumi_rodjenja'));
                                            if (! ($r['ok'] ?? false)) {
                                                return new HtmlString('<span style="color:rgb(120,120,120);">'.e($r['greska'] ?? 'Унесите податке.').'</span>');
                                            }
                                            $html = '<div style="font-size:1rem;line-height:1.6;">'
                                                .'<div><strong>Кандидата:</strong> '.$r['n'].'</div>'
                                                .'<div><strong>Просечна старост:</strong> '.$r['avg'].' год.</div>'
                                                .'<div><strong>Млађи од 30:</strong> '.$r['mladji'].'/'.$r['n'].' ('.$r['pct'].'%)</div>';
                                            if (! empty($r['preskoceni'])) {
                                                $ukupno = count($r['preskoceni']);
                                                $lista = array_slice($r['preskoceni'], 0, 10);
                                                $jos = $ukupno - count($lista);
                                                $tekst = e(implode(', ', $lista)).($jos > 0 ? ' … и још '.$jos : '');
                                                $html .= '<div style="margin-top:6px;color:rgb(180,120,0);"><strong>Прескочено ('.$ukupno.'):</strong>'
                                                    .'<div style="max-height:90px;overflow-y:auto;margin-top:2px;word-break:break-word;">'.$tekst.'</div></div>';
                                            }
                                            return new HtmlString($html.'</div>');
                                        }),
                                ])
                                ->modalSubmitActionLabel('Израчунај и упиши')
                                ->modalWidth('lg')
                                ->action(function (array $data, Set $set) {
                                    $r = static::izracunajStarosnuStrukturu($data['referentni_datum'] ?? null, $data['datumi_rodjenja'] ?? null);
                                    if (! ($r['ok'] ?? false)) {
                                        Notification::make()
                                            ->title('Није могуће израчунати')
                                            ->body($r['greska'] ?? '')
                                            ->danger()->send();
                                        return;
                                    }

                                    $set('prosecna_starost_kandidata', $r['avg']);
                                    $set('udeo_kandidata_mladjih_od_30', $r['pct']);

                                    $telo = "Кандидата: {$r['n']} · Просечна старост: {$r['avg']} год. · Млађи од 30: {$r['mladji']}/{$r['n']} ({$r['pct']}%)";
                                    if (! empty($r['preskoceni'])) {
                                        $telo .= ' · Прескочено: '.count($r['preskoceni']);
                                    }
                                    Notification::make()
                                        ->title('Израчунато и уписано')
                                        ->body($telo)
                                        ->success()->send();
                                }),
                        ])->columnSpanFull(),
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
                                    ->color('warning')
                                    ->extraAttributes(['class' => 'kprm-hint-pulse', 'style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;'])
                                    ->modalHeading('Просечна старост кандидата у изборном поступку')
                                    ->modalContent(new HtmlString('<div class="space-y-3 text-sm"><div><p class="font-semibold">Шта представља?</p><p>Просечан број година свих кандидата који су се пријавили за конкретно радно место.</p></div><div><p class="font-semibold">Како се рачуна?</p><ol class="list-decimal list-inside space-y-2 mt-1"><li>Из матичног броја (ЈМБГ) издвојити датум рођења кандидата.</li><li>За сваког кандидата израчунати старост: Старост кандидата = Година оглашавања конкурса − Година рођења кандидата</li><li>Израчунава се просек:<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin:10px 0;flex-wrap:nowrap;"><em>Просечна старост</em> = <span style="display:inline-flex;flex-direction:column;text-align:center;"><span style="border-bottom:1px solid currentColor;padding:2px 12px;font-style:italic;">Збир година свих кандидата</span><span style="padding:2px 12px;font-style:italic;">Укупан број кандидата</span></span></div></li></ol></div></div>'))
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
                                    ->color('warning')
                                    ->extraAttributes(['class' => 'kprm-hint-pulse', 'style' => 'padding:0;background:transparent;box-shadow:none;min-height:unset;'])
                                    ->modalHeading('Удео кандидата млађих од 30 година')
                                    ->modalContent(new HtmlString('<div class="space-y-3 text-sm"><div><p class="font-semibold">Шта представља?</p><p>Проценат кандидата који у тренутку расписивања конкурса (датум оглашавања конкурса) имају мање од 30 година.</p></div><div><p class="font-semibold">Како се рачуна?</p><ol class="list-decimal list-inside space-y-2 mt-1"><li>На основу датума рођења утврдити који кандидати су млађи од 30 година.</li><li>Применити формулу:<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin:10px 0;flex-wrap:nowrap;"><em>Удео млађих од 30</em> = <span style="display:inline-flex;flex-direction:column;text-align:center;"><span style="border-bottom:1px solid currentColor;padding:2px 12px;font-style:italic;">Број кандидата млађих од 30 година</span><span style="padding:2px 12px;font-style:italic;">Укупан број кандидата</span></span><span style="white-space:nowrap;">× 100</span></div></li></ol></div><p>Резултат се исказује у процентима (%).</p></div>'))
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
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_validnih_prijava', ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa']))
                            ->validationMessages([
                                'lte' => 'Број валидних пријава не може бити већи од укупног броја пријава.',
                            ]),
                        TextInput::make('broj_validnih_prijava_iz_organa')
                            ->label('Број валидних пријава кандидата из органа')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_validnih_prijava', ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa']))
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
                            ->label('Број валидних пријава кандидата из другог државног органа')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_validnih_prijava', ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa']))
                            ->hintAction(self::infoHintAction('info_validne_iz_organa', 'Не односи се на органе локалне самоуправе и органе аутономне покрајине'))
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
                            ->label('Број валидних пријава кандидата ван државних органа, укључујући незапослена лица')
                            ->numeric()->minValue(0)
                            ->lte('broj_validnih_prijava')
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_validnih_prijava', ['broj_validnih_prijava_iz_organa', 'broj_validnih_prijava_iz_drugog_organa', 'broj_validnih_prijava_van_drzavnih_organa']))
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
                            ->hidden(fn (Get $get) => $get('tip_konkursa') == 2)
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
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_kandidata_za_koje_se_zakazuju_pfk');
                            })
                            ->helperText('Укључујући и кандидате којима су се оцене признале')
                            ->hintAction(self::infoHintAction('info_broj_kandidata_pfk', 'Укупан број кандидата се може пронаћи у извештају СУК-а'))
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
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $ispunili_ofk = (int) $get('broj_kandidata_koji_su_ispunlii_merila_ofk');
                                    if ($value !== null && $value !== '' && $ispunili_ofk && (int)$value > $ispunili_ofk) {
                                        $fail('Број кандидата за које се заказују ПФК не сме бити већи од броја кандидата који су испунили мерила ОФК.');
                                    }
                                },
                            ]),
                        static::makeDateField('datum_pocetka_provere_pfk', 'Датум почетка провере ПФК', 'datum_ofk_izvestaja', 'ОФК извештаја')
                            ->helperText('Уколико је било више дана провере, унети први датум'),
                        static::makeDateField('datum_pfk_izvestaja', 'Датум ПФК извештаја', 'datum_pocetka_provere_pfk', 'почетка провере ПФК')
                            ->hintAction(self::infoHintAction('info_pfk_izvestaj', 'Иако се ова форма извештаја тренутно не израђује, њено увођење омогућава праћење времена вредновања одговора кандидата и представља важан показатељ ефикасности изборног поступка.')),
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
                        static::makeDateField('datum_pocetka_sprovodjenja_intervjua', 'Датум спровођења завршног интервјуа', 'datum_predaje_dokumentacije', 'предаје документације')
                            ->afterStateUpdated(function ($state, $get, $set) {
                                if ($state && !$get('datum_izvestaja_sa_zavrsnog_intervjua')) {
                                    $set('datum_izvestaja_sa_zavrsnog_intervjua', $state);
                                }
                            }),
                        static::makeDateField('datum_izvestaja_sa_zavrsnog_intervjua', 'Датум извештаја са завршног интервјуа', 'datum_pocetka_sprovodjenja_intervjua', 'спровођења завршног интервјуа')
                            ->hintAction(self::infoHintAction('info_zavrsni_izvestaj', 'Иако се ова форма извештаја тренутно не израђује, њено увођење омогућава праћење времена вредновања одговора кандидата и представља важан показатељ ефикасности изборног поступка.')),
                        TextInput::make('broj_odazvanih_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број одазваних кандидата на завршном разговору')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_neodazvanih_kandidata_zavrsni_razgovor');
                            })
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
                            ->live(onBlur: true)->afterStateUpdated(function ($component, $livewire) {
                                $livewire->validateOnly($component->getStatePath());
                                $livewire->validateOnly('data.broj_odazvanih_kandidata_na_zavrsnom_razgovoru');
                            })
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
                        static::makeDateField('datum_formiranja_liste_kandidata', 'Дан објављивања листе кандидата који су испунили мерила у изборном поступку')
                            ->hintAction(self::infoHintAction('info_lista_kandidata', 'Члан 57, став 7, Закона о државним службеницима каже: На интернет презентацији органа државне управе који је огласио конкурс и Службе за управљање кадровима објављује се листа кандидата под шифром њихове пријаве и име и презиме кандидата који је изабран у конкурсном поступку.'))
                            ->columnSpanFull(),
                        TextInput::make('broj_kandidata_na_listi')
                            ->label('Број кандидата на листи')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_kandidata_na_listi', ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi']))
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
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_kandidata_na_listi', ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi']))
                            ->validationMessages([
                                'lte' => 'Број кандидата из органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора бити једнак укупном броју кандидата на листи.'
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
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_kandidata_na_listi', ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi']))
                            ->validationMessages([
                                'lte' => 'Број кандидата из другог државног органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора бити једнак укупном броју кандидата на листи.'
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
                            ->live(onBlur: true)->afterStateUpdated(self::revalidateSumGroup('broj_kandidata_na_listi', ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi']))
                            ->validationMessages([
                                'lte' => 'Број кандидата ван државних органа не може бити већи од укупног броја кандидата на листи.',
                            ])
                            ->rules([
                                self::sumValidationRule(
                                    'broj_kandidata_na_listi',
                                    ['broj_kandidata_iz_organa_na_listi', 'broj_kandidata_iz_drugog_drzavnog_organa_na_listi', 'broj_kandidata_van_drzavnih_organa_na_listi'],
                                    'Збир кандидата на листи мора бити једнак укупном броју кандидата на листи.'
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
                        TextInput::make('broj_usvojenih_zalbi_na_resenje_o_prijemu_u_radni_odnos')
                            ->label('Број усвојених жалби на решење о пријему у радни однос')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $zalbi = (int) $get('broj_zalbi_na_resenje_o_prijemu_u_radni_odnos');
                                    if ($value !== null && $value !== '' && (int)$value > $zalbi) {
                                        $fail('Број усвојених жалби не може бити већи од броја жалби на решење о пријему у радни однос.');
                                    }
                                },
                            ]),
                        TextInput::make('broj_izvrsilaca_ponovno_oglasavanje')
                            ->label('Број извршилаца за које је оглашавање поновљено након неуспелог поступка')
                            ->numeric()->minValue(0)
                            ->live(onBlur: true)->afterStateUpdated(fn ($component, $livewire) => $livewire->validateOnly($component->getStatePath()))
                            ->hintAction(self::infoHintAction('info_ponovno_oglasavanje', 'Односи се на број извршилаца за радна места која су у току исте календарске године поново оглашена, услед чињенице да претходним огласом није попуњен планирани број извршилаца.')),

                        Section::make('Завршетак уноса')
                            ->schema([
                                Toggle::make('unos_zavrsen')
                                    ->label('Унос завршен')
                                    ->onColor('success')
                                    ->helperText('Означите када сте у потпуности завршили унос и измене на овом радном месту. Запис остаје изменљив — ознаку можете скинути у сваком тренутку.'),
                                TextEntry::make('unos_zavrsen_info')
                                    ->label('Означио')
                                    ->state(fn ($record) => $record?->unos_zavrsen && $record->unos_zavrsen_at
                                        ? ($record->unosZavrsioKorisnik?->name ?? '—') . ', ' . $record->unos_zavrsen_at->copy()->timezone(config('app.display_timezone'))->format('d.m.Y. у H:i')
                                        : '—')
                                    ->visible(fn ($record) => (bool) $record?->unos_zavrsen),
                            ])->columns(2)->columnSpanFull(),
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
                TextColumn::make('naziv_radnog_mesta')
                    ->label('Назив радног места')
                    ->description(fn ($record) => $record->organRelation?->organ)
                    ->sortable()
                    ->wrap()
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('naziv_radnog_mesta', 'like', "%{$search}%")
                        ->orWhereHas('organRelation', fn ($q) => $q->where('organ', 'like', "%{$search}%"))),
                TextColumn::make('zvanjeRelation.zvanje')
                    ->label('Звање')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('mestaRada.grad')
                    ->label('Место рада')
                    ->searchable()
                    ->state(fn ($record) => $record->mestaRada->unique('id')->pluck('grad')->join(', '))
                    ->tooltip(function ($record) {
                        $mesta = $record->mestaRada->unique('id')->pluck('grad');
                        return $mesta->count() > 3 ? $mesta->join(', ') : null;
                    }),
                TextColumn::make('datum_oglasavanja')
                    ->label('Датум оглашавања')
                    ->date('d.m.Y.')
                    ->sortable(),
                TextColumn::make('statusKonkursaRelation.status_konkursa')
                    ->label('Исход конкурса')
                    ->badge()
                    ->wrap()
                    ->extraAttributes(['class' => 'kprm-badge-wrap'])
                    ->placeholder('—')
                    ->sortable(),
                ToggleColumn::make('unos_zavrsen')
                    ->label('Унос завршен')
                    ->sortable()
                    ->onColor('success')
                    ->disabled(fn ($record) => ! auth()->user()?->can('update', $record))
                    ->tooltip(fn ($record) => $record->unos_zavrsen && $record->unos_zavrsen_at
                        ? 'Означио: ' . ($record->unosZavrsioKorisnik?->name ?? '—') . ', ' . $record->unos_zavrsen_at->copy()->timezone(config('app.display_timezone'))->format('d.m.Y. H:i')
                        : null),
            ])
            ->filters([
                SelectFilter::make('organ')
                    ->label('Орган')
                    ->relationship('organRelation', 'organ')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false),
                SelectFilter::make('zvanje')
                    ->label('Звање')
                    ->relationship('zvanjeRelation', 'zvanje', fn ($query) => $query->orderBy('id', 'asc'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('mestaRada')
                    ->label('Место рада')
                    ->relationship('mestaRada', 'grad')
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
                TernaryFilter::make('unos_zavrsen')
                    ->label('Унос завршен')
                    ->placeholder('Сви')
                    ->trueLabel('Завршени')
                    ->falseLabel('Незавршени')
                    ->queries(
                        true: fn (Builder $query) => $query->where('unos_zavrsen', true),
                        false: fn (Builder $query) => $query->where(fn (Builder $q) => $q
                            ->where('unos_zavrsen', false)
                            ->orWhereNull('unos_zavrsen')
                        ),
                        blank: fn (Builder $query) => $query,
                    ),
                TrashedFilter::make()
                    ->label('Обрисани'),
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
                            $mestaRadaIds = $record->mestaRada()->pluck('sifarnik_kodovi_gradova.id')->toArray();
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
                    RestoreAction::make()
                        ->label('Врати'),
                    ForceDeleteAction::make()
                        ->label('Обриши трајно'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Обриши означене'),
                    RestoreBulkAction::make()
                        ->label('Врати означене'),
                    ForceDeleteBulkAction::make()
                        ->label('Трајно обриши означене'),
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
