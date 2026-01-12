<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PodaciORadnomMestuResource\Pages;
use App\Filament\Resources\PodaciORadnomMestuResource\RelationManagers;
use App\Models\PodaciORadnomMestu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PodaciORadnomMestuResource extends Resource
{
    protected static ?string $model = PodaciORadnomMestu::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Радна Места';

    protected static ?string $modelLabel = 'Радно Место';

    protected static ?string $pluralModelLabel = 'Радна Места';

    protected static ?string $slug = 'podaci-o-radnom-mestu';

    protected static ?int $navigationSort = 10;

    /**
     * Apply organ-based filtering globally to all queries
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return app(\App\Services\OrganFilterService::class)->applyOrganFilter($query, 'organ');
    }

    /**
     * Allow access to list page with either 'view' or 'view_any' permission
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_podaci::o::radnom::mestu')
            || auth()->user()?->can('view_any_podaci::o::radnom::mestu')
            || false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основни подаци о конкурсу')
                    ->schema([
                        Forms\Components\TextInput::make('naziv_radnog_mesta')
                            ->label('Назив радног места')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('vrsta_organa')
                            ->label('Врста органа')
                            ->relationship('vrstaOrganaRelation', 'vrsta_organa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->live(),
                        Forms\Components\Select::make('organ')
                            ->label('Орган')
                            ->options(function (callable $get) {
                                $vrstaOrganaId = $get('vrsta_organa');
                                if (!$vrstaOrganaId) {
                                    return \App\Models\SifarnikOrgani::pluck('organ', 'id');
                                }
                                return \App\Models\SifarnikOrgani::where('vrsta_organ_id', $vrstaOrganaId)
                                    ->pluck('organ', 'id');
                            })
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tip_konkursa')
                            ->label('Тип конкурса')
                            ->relationship('tipKonkursaRelation', 'tip_konkursa')
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('broj_izvrsilaca')
                            ->label('Број извршилаца')
                            ->numeric(),
                        Forms\Components\Select::make('zvanje')
                            ->label('Звање')
                            ->relationship('zvanjeRelation', 'zvanje', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('mesto_rada')
                            ->label('Место рада')
                            ->relationship('mestoRadaRelation', 'mesto')
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('status_konkursa_na_dan_1')
                            ->label('Статус конкурса на дан 1')
                            ->relationship('statusKonkursaNaDan1Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('status_konkursa_na_dan_2')
                            ->label('Статус конкурса на дан 2')
                            ->relationship('statusKonkursaNaDan2Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                    ])->columns(3),

                Forms\Components\Section::make('Датуми поступка')
                    ->schema([
                        Forms\Components\DatePicker::make('datum_dobijanja_saglasnosti_vlade')
                            ->label('Датум добијања сагласности Владе'),
                        Forms\Components\DatePicker::make('datum_donosenja_resenja_o_pokretanju_postupka')
                            ->label('Датум доношења решења о покретању поступка'),
                        Forms\Components\DatePicker::make('datum_dobijanja_obavestenja_od_suka')
                            ->label('Датум добијања обавештења од СУКа'),
                        Forms\Components\DatePicker::make('datum_odrzavanja_prvog_sastanka')
                            ->label('Датум одржавања првог састанка'),
                        Forms\Components\DatePicker::make('datum_oglasavanja')
                            ->label('Датум оглашавања'),
                        Forms\Components\DatePicker::make('datum_pregleda_prijava')
                            ->label('Датум прегледа пријава'),
                        Forms\Components\DatePicker::make('datum_pocetka_provere_ofk')
                            ->label('Датум почетка провере ОФК'),
                        Forms\Components\DatePicker::make('datum_ofk_izvestaja')
                            ->label('Датум ОФК извештаја')
                            ->helperText('Датум креирања извештаја СУКа'),
                        Forms\Components\DatePicker::make('datum_pocetka_provere_pfk')
                            ->label('Датум почетка провере ПФК'),
                        Forms\Components\DatePicker::make('datum_pocetka_provere_pk')
                            ->label('Датум почетка провере ПК'),
                        Forms\Components\DatePicker::make('datum_pk_izvestaja')
                            ->label('Датум ПК извештаја')
                            ->helperText('Датум креирања извештаја СУКа'),
                        Forms\Components\DatePicker::make('datum_predaje_dokumentacije')
                            ->label('Датум предаје документације'),
                        Forms\Components\DatePicker::make('datum_pocetka_sprovodjenja_intervjua')
                            ->label('Датум почетка спровођења интервјуа'),
                        Forms\Components\DatePicker::make('datum_dostavljanja_liste_rukovodiocu_organa')
                            ->label('Датум достављања листе руководиоцу органа'),
                        Forms\Components\DatePicker::make('datum_donosenja_resenja_o_izabranom_kandidatu')
                            ->label('Датум доношења решења о изабраном кандидату'),
                        Forms\Components\DatePicker::make('datum_stupanja_na_rad')
                            ->label('Датум ступања на рад')
                            ->helperText('Датум ступања на рад првог извршиоца'),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Статус и жалбе')
                    ->schema([
                        Forms\Components\TextInput::make('broj_primljenih_izvrsilaca')
                            ->label('Број примљених извршилаца')
                            ->numeric(),
                        Forms\Components\TextInput::make('ocena_sa_vrednovanja')
                            ->label('Оцена са вредновања')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Број жалби на решење о одбацивању пријаве')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_zalbi_na_resenje_o_prijemu_u_radni_odnos')
                            ->label('Број жалби на решење о пријему у радни однос')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Број усвојених жалби на решење о одбацивању пријаве')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_izvrsilaca_ponovno_oglasavanje')
                            ->label('Број извршилаца - поновно оглашавање')
                            ->numeric(),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Подаци о пријавама')
                    ->schema([
                        Forms\Components\TextInput::make('ukupan_broj_prijava')
                            ->label('Укупан број пријава')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_iz_organa')
                            ->label('Број пријава из органа')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_iz_drugih_organa')
                            ->label('Број пријава из других органа')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_van_drzavnih_organa')
                            ->label('Број пријава ван државних органа')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Валидне пријаве')
                    ->schema([
                        Forms\Components\TextInput::make('broj_validnih_prijava')
                            ->label('Број валидних пријава')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_iz_organa')
                            ->label('Број валидних пријава из органа')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_iz_drugog_organa')
                            ->label('Број валидних пријава из другог органа')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_van_drzavnih_organa')
                            ->label('Број валидних пријава ван државних органа')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Кандидати који су испунили мерила')
                    ->schema([
                        Forms\Components\TextInput::make('broj_kandidata_koji_su_ispunlii_merila_ofk')
                            ->label('Број кандидата који су испунили мерила ОФК')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_koji_su_ispunlii_merila_pfk')
                            ->label('Број кандидата који су испунили мерила ПФК')
                            ->numeric(),
                        Forms\Components\Select::make('provera_pfk')
                            ->label('Провера ПФК')
                            ->relationship('proveraPfkRelation', 'provera_pfk')
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('broj_kandidata_ispunili_merila_pk')
                            ->label('Број кандидата који су испунили мерила ПК')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_odazvanih_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број одазваних кандидата на завршном разговору')
                            ->numeric(),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Листа кандидата')
                    ->schema([
                        Forms\Components\TextInput::make('broj_kandidata_na_listi')
                            ->label('Број кандидата на листи')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_iz_organa_na_listi')
                            ->label('Број кандидата из органа на листи')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_iz_drugog_drzavnog_organa_na_listi')
                            ->label('Број кандидата из другог државног органа на листи')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_van_drzavnih_organa_na_listi')
                            ->label('Број кандидата ван државних органа на листи')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Изабрани кандидат')
                    ->schema([
                        Forms\Components\Select::make('izabrani_kandidat')
                            ->label('Изабрани кандидат')
                            ->relationship('izabraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_ofk')
                            ->label('Број бодова на ОФК')
                            ->options([
                                7 => '7',
                                8 => '8',
                                9 => '9',
                            ]),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_pfk')
                            ->label('Број бодова на ПФК')
                            ->options(array_merge(
                                [0 => '0', 3 => '3', 5 => '5', 8 => '8'],
                                array_combine(range(10, 20), range(10, 20))
                            )),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_pk')
                            ->label('Број бодова на ПК')
                            ->options(array_combine(range(10, 30), range(10, 30))),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број бодова на завршном разговору')
                            ->options([
                                2 => '2',
                                4 => '4',
                                6 => '6',
                            ]),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Другопласирани кандидат')
                    ->schema([
                        Forms\Components\Select::make('drugoplasirani_kandidat')
                            ->label('Другопласирани кандидат')
                            ->relationship('drugoplasiraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_ofk')
                            ->label('Број бодова на ОФК')
                            ->options([
                                7 => '7',
                                8 => '8',
                                9 => '9',
                            ]),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_pfk')
                            ->label('Број бодова на ПФК')
                            ->options(array_merge(
                                [0 => '0', 3 => '3', 5 => '5', 8 => '8'],
                                array_combine(range(10, 20), range(10, 20))
                            )),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_pk')
                            ->label('Број бодова на ПК')
                            ->options(array_combine(range(10, 30), range(10, 30))),
                        Forms\Components\Select::make('broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Број бодова на завршном разговору')
                            ->options([
                                2 => '2',
                                4 => '4',
                                6 => '6',
                            ]),
                    ])->columns(3)->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vrstaOrganaRelation.vrsta_organa')
                    ->label('Врста органа')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('organRelation.organ')
                    ->label('Орган')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('naziv_radnog_mesta')
                    ->label('Назив радног места')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('zvanjeRelation.zvanje')
                    ->label('Звање')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mestoRadaRelation.mesto')
                    ->label('Место рада')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('statistika')
                        ->label('Статистика')
                        ->icon('heroicon-o-chart-bar')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Статистика конкурса')
                        ->modalSubheading(fn ($record) => 'Преглед статистичких података за овај конкурс')
                        ->modalWidth('7xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Затвори')
                        ->infolist(fn ($record) => [
                            // SEKCIJA 1: Временски периоди
                            Infolists\Components\Section::make('Временски периоди')
                                ->description('Периоди трајања конкурсних поступака')
                                ->icon('heroicon-o-clock')
                                ->schema([
                                    Infolists\Components\TextEntry::make('vreme_trajanja')
                                        ->label('Време трајања конкурсног поступка')
                                        ->state(function ($record) {
                                            if ($record->datum_donosenja_resenja_o_pokretanju_postupka
                                                && $record->datum_stupanja_na_rad) {

                                                $start = Carbon::parse($record->datum_donosenja_resenja_o_pokretanju_postupka);
                                                $end = Carbon::parse($record->datum_stupanja_na_rad);
                                                $days = $start->diffInDays($end);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између доношења решења и ступања на рад'),

                                    Infolists\Components\TextEntry::make('vreme_trajanja_izbornog_postupka')
                                        ->label('Време трајања изборног поступка')
                                        ->state(function ($record) {
                                            if ($record->datum_pregleda_prijava
                                                && $record->datum_dostavljanja_liste_rukovodiocu_organa) {

                                                $start = Carbon::parse($record->datum_pregleda_prijava);
                                                $end = Carbon::parse($record->datum_dostavljanja_liste_rukovodiocu_organa);
                                                $days = $start->diffInDays($end);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и достављања листе'),

                                    Infolists\Components\TextEntry::make('vreme_od_saglasnosti_do_resenja')
                                        ->label('Време од сагласности Владе до решења')
                                        ->state(function ($record) {
                                            if ($record->datum_dobijanja_saglasnosti_vlade
                                                && $record->datum_donosenja_resenja_o_pokretanju_postupka) {

                                                $start = Carbon::parse($record->datum_dobijanja_saglasnosti_vlade);
                                                $end = Carbon::parse($record->datum_donosenja_resenja_o_pokretanju_postupka);
                                                $days = $start->diffInDays($end);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између добијања сагласности и решења'),

                                    Infolists\Components\TextEntry::make('vreme_od_obavestenja_suka_do_resenja')
                                        ->label('Време од обавештења СУК-а до решења')
                                        ->state(function ($record) {
                                            if ($record->datum_dobijanja_obavestenja_od_suka
                                                && $record->datum_donosenja_resenja_o_pokretanju_postupka) {

                                                $obavestenje = Carbon::parse($record->datum_dobijanja_obavestenja_od_suka);
                                                $resenje = Carbon::parse($record->datum_donosenja_resenja_o_pokretanju_postupka);
                                                $days = $resenje->diffInDays($obavestenje);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између обавештења СУК-а и решења'),

                                    Infolists\Components\TextEntry::make('vreme_od_obavestenja_suka_do_prvog_sastanka')
                                        ->label('Време од обавештења СУК-а до првог састанка')
                                        ->state(function ($record) {
                                            if ($record->datum_dobijanja_obavestenja_od_suka
                                                && $record->datum_odrzavanja_prvog_sastanka) {

                                                $obavestenje = Carbon::parse($record->datum_dobijanja_obavestenja_od_suka);
                                                $sastanak = Carbon::parse($record->datum_odrzavanja_prvog_sastanka);
                                                $days = $obavestenje->diffInDays($sastanak);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између обавештења и првог састанка'),

                                    Infolists\Components\TextEntry::make('vreme_od_prvog_sastanka_do_oglasavanja')
                                        ->label('Време од првог састанка до оглашавања')
                                        ->state(function ($record) {
                                            if ($record->datum_odrzavanja_prvog_sastanka
                                                && $record->datum_oglasavanja) {

                                                $sastanak = Carbon::parse($record->datum_odrzavanja_prvog_sastanka);
                                                $oglasavanje = Carbon::parse($record->datum_oglasavanja);
                                                $days = $sastanak->diffInDays($oglasavanje);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између првог састанка и оглашавања конкурса'),

                                    Infolists\Components\TextEntry::make('vreme_od_oglasavanja_do_pregleda_prijava')
                                        ->label('Време од оглашавања до прегледа пријава')
                                        ->state(function ($record) {
                                            if ($record->datum_oglasavanja
                                                && $record->datum_pregleda_prijava) {

                                                $oglasavanje = Carbon::parse($record->datum_oglasavanja);
                                                $pregled = Carbon::parse($record->datum_pregleda_prijava);
                                                $days = $oglasavanje->diffInDays($pregled);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између оглашавања и прегледа пријава'),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(false),

                            // SEKCIJA 2: Интервали између датума
                            Infolists\Components\Section::make('Интервали између датума')
                                ->description('Временски размаци између кључних догађаја')
                                ->icon('heroicon-o-calendar-days')
                                ->schema([
                                    Infolists\Components\TextEntry::make('vreme_od_pregleda_prijava_do_pocetka_provere_ofk')
                                        ->label('Време од прегледа пријава до почетка провере ОФК')
                                        ->state(function ($record) {
                                            if ($record->datum_pregleda_prijava
                                                && $record->datum_pocetka_provere_ofk) {

                                                $pregled = Carbon::parse($record->datum_pregleda_prijava);
                                                $proveraOfk = Carbon::parse($record->datum_pocetka_provere_ofk);
                                                $days = $pregled->diffInDays($proveraOfk);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и почетка провере ОФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 2),

                                    Infolists\Components\TextEntry::make('vreme_od_pocetka_provere_ofk_do_pocetka_provere_pfk')
                                        ->label('Време од почетка провере ОФК до почетка провере ПФК')
                                        ->state(function ($record) {
                                            if ($record->datum_pocetka_provere_ofk
                                                && $record->datum_pocetka_provere_pfk) {

                                                $proveraOfk = Carbon::parse($record->datum_pocetka_provere_ofk);
                                                $proveraPfk = Carbon::parse($record->datum_pocetka_provere_pfk);
                                                $days = $proveraOfk->diffInDays($proveraPfk);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ОФК и почетка провере ПФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 2),

                                    Infolists\Components\TextEntry::make('vreme_od_pregleda_prijava_do_pocetka_provere_pfk')
                                        ->label('Време трајања од прегледа пријава до ПФК')
                                        ->state(function ($record) {
                                            if ($record->tip_konkursa == 2
                                                && $record->datum_pregleda_prijava
                                                && $record->datum_pocetka_provere_pfk) {

                                                $pregled = Carbon::parse($record->datum_pregleda_prijava);
                                                $proveraPfk = Carbon::parse($record->datum_pocetka_provere_pfk);
                                                $days = $pregled->diffInDays($proveraPfk);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између прегледа пријава и почетка провере ПФК')
                                        ->hidden(fn ($record) => $record->tip_konkursa == 1),

                                    Infolists\Components\TextEntry::make('vreme_od_pocetka_provere_pfk_do_pocetka_provere_pk')
                                        ->label('Време од почетка провере ПФК до почетка провере ПК')
                                        ->state(function ($record) {
                                            if ($record->datum_pocetka_provere_pfk
                                                && $record->datum_pocetka_provere_pk) {

                                                $proveraPfk = Carbon::parse($record->datum_pocetka_provere_pfk);
                                                $proveraPk = Carbon::parse($record->datum_pocetka_provere_pk);
                                                $days = $proveraPfk->diffInDays($proveraPk);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ПФК и почетка провере ПК'),

                                    Infolists\Components\TextEntry::make('vreme_od_pocetka_provere_pk_do_predaje_dokumentacije')
                                        ->label('Време од почетка провере ПК до предаје документације')
                                        ->state(function ($record) {
                                            if ($record->datum_pocetka_provere_pk
                                                && $record->datum_predaje_dokumentacije) {

                                                $proveraPk = Carbon::parse($record->datum_pocetka_provere_pk);
                                                $predajaDok = Carbon::parse($record->datum_predaje_dokumentacije);
                                                $days = $proveraPk->diffInDays($predajaDok);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка провере ПК и предаје документације'),

                                    Infolists\Components\TextEntry::make('vreme_od_predaje_dokumentacije_do_intervjua')
                                        ->label('Време од предаје документације до спровођења интервјуа')
                                        ->state(function ($record) {
                                            if ($record->datum_predaje_dokumentacije
                                                && $record->datum_pocetka_sprovodjenja_intervjua) {

                                                $predajaDok = Carbon::parse($record->datum_predaje_dokumentacije);
                                                $intervju = Carbon::parse($record->datum_pocetka_sprovodjenja_intervjua);
                                                $days = $predajaDok->diffInDays($intervju);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између предаје документације и почетка спровођења интервјуа'),

                                    Infolists\Components\TextEntry::make('vreme_od_intervjua_do_dostavljanja_liste')
                                        ->label('Време од спровођења интервјуа до достављања листе')
                                        ->state(function ($record) {
                                            if ($record->datum_pocetka_sprovodjenja_intervjua
                                                && $record->datum_dostavljanja_liste_rukovodiocu_organa) {

                                                $intervju = Carbon::parse($record->datum_pocetka_sprovodjenja_intervjua);
                                                $lista = Carbon::parse($record->datum_dostavljanja_liste_rukovodiocu_organa);
                                                $days = $intervju->diffInDays($lista);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између почетка спровођења интервјуа и достављања листе руководиоцу'),

                                    Infolists\Components\TextEntry::make('vreme_od_dostavljanja_liste_do_resenja')
                                        ->label('Време од достављања листе до доношења решења')
                                        ->state(function ($record) {
                                            if ($record->datum_dostavljanja_liste_rukovodiocu_organa
                                                && $record->datum_donosenja_resenja_o_izabranom_kandidatu) {

                                                $lista = Carbon::parse($record->datum_dostavljanja_liste_rukovodiocu_organa);
                                                $resenje = Carbon::parse($record->datum_donosenja_resenja_o_izabranom_kandidatu);
                                                $days = $lista->diffInDays($resenje);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између достављања листе и доношења решења о изабраном кандидату'),

                                    Infolists\Components\TextEntry::make('vreme_od_resenja_do_stupanja_na_rad')
                                        ->label('Време од доношења решења до ступања на рад')
                                        ->state(function ($record) {
                                            if ($record->datum_donosenja_resenja_o_izabranom_kandidatu
                                                && $record->datum_stupanja_na_rad) {

                                                $resenje = Carbon::parse($record->datum_donosenja_resenja_o_izabranom_kandidatu);
                                                $stupanje = Carbon::parse($record->datum_stupanja_na_rad);
                                                $days = $resenje->diffInDays($stupanje);

                                                return $days . ' дана';
                                            }
                                            return 'Н/Д';
                                        })
                                        ->placeholder('Нема података')
                                        ->helperText('Број дана између доношења решења и ступања на рад'),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(false),

                            // SEKCIJA 3: Додатне статистике (за будуће формуле)
                            Infolists\Components\Section::make('Додатне статистике')
                                ->description('Додатне анализе конкурсног поступка')
                                ->icon('heroicon-o-chart-bar')
                                ->schema([
                                    // Ovde će se dodavati nove statistike
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(true)
                                ->hidden(fn () => true), // Sakrij dok je prazna

                            // SEKCIJA 4: Напредна анализа (за више статистика)
                            Infolists\Components\Section::make('Напредна анализа')
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
                    Tables\Actions\ViewAction::make()
                        ->label('Преглед'),
                    Tables\Actions\EditAction::make()
                        ->label('Измени'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Обриши'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Обриши означене'),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListPodaciORadnomMestus::route('/'),
            'create' => Pages\CreatePodaciORadnomMestu::route('/create'),
            'edit' => Pages\EditPodaciORadnomMestu::route('/{record}/edit'),
        ];
    }
}
