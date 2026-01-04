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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PodaciORadnomMestuResource extends Resource
{
    protected static ?string $model = PodaciORadnomMestu::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Radna Mesta';

    protected static ?string $modelLabel = 'Radno Mesto';

    protected static ?string $pluralModelLabel = 'Radna Mesta';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Osnovni podaci o konkursu')
                    ->schema([
                        Forms\Components\TextInput::make('naziv_radnog_mesta')
                            ->label('Naziv radnog mesta')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('vrsta_organa')
                            ->label('Vrsta organa')
                            ->relationship('vrstaOrganaRelation', 'vrsta_organa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable()
                            ->live(),
                        Forms\Components\Select::make('organ')
                            ->label('Organ')
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
                            ->label('Tip konkursa')
                            ->relationship('tipKonkursaRelation', 'tip_konkursa')
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('broj_izvrsilaca')
                            ->label('Broj izvršilaca')
                            ->numeric(),
                        Forms\Components\Select::make('zvanje')
                            ->label('Zvanje')
                            ->relationship('zvanjeRelation', 'zvanje', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('mesto_rada')
                            ->label('Mesto rada')
                            ->relationship('mestoRadaRelation', 'mesto')
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('status_konkursa_na_dan_1')
                            ->label('Status konkursa na dan 1')
                            ->relationship('statusKonkursaNaDan1Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('status_konkursa_na_dan_2')
                            ->label('Status konkursa na dan 2')
                            ->relationship('statusKonkursaNaDan2Relation', 'status_konkursa', fn($query) => $query->orderBy('id', 'asc'))
                            ->preload()
                            ->searchable(),
                    ])->columns(3),

                Forms\Components\Section::make('Datumi postupka')
                    ->schema([
                        Forms\Components\DatePicker::make('datum_dobijanja_saglasnosti_vlade')
                            ->label('Datum dobijanja saglasnosti Vlade')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_donosenja_resenja_o_pokretanju_postupka')
                            ->label('Datum donošenja rešenja o pokretanju postupka')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_dobijanja_obavestenja_od_suka')
                            ->label('Datum dobijanja obaveštenja od SUKa')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_odrzavanja_prvog_sastanka')
                            ->label('Datum održavanja prvog sastanka')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_oglasavanja')
                            ->label('Datum oglašavanja')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_pregleda_prijava')
                            ->label('Datum pregleda prijava')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_ofk_izvestaja')
                            ->label('Datum OFK izveštaja')
                            ->helperText('Datum kreiranja izveštaja SUKa')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_pocetka_provere_pfk')
                            ->label('Datum početka provere PFK')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_pk_izvestaja')
                            ->label('Datum PK izveštaja')
                            ->helperText('Datum kreiranja izveštaja SUKa')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_predaje_dokumentacije')
                            ->label('Datum predaje dokumentacije')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_pocetka_sprovodjenja_intervjua')
                            ->label('Datum početka sprovođenja intervjua')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_dostavljanja_liste_rukovodiocu_organa')
                            ->label('Datum dostavljanja liste rukovodiocu organa')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_donosenja_resenja_o_izabranom_kandidatu')
                            ->label('Datum donošenja rešenja o izabranom kandidatu')
                            ->native(true),
                        Forms\Components\DatePicker::make('datum_stupanja_na_rad')
                            ->label('Datum stupanja na rad')
                            ->helperText('Datum stupanja na rad prvog izvršioca')
                            ->native(true),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Status i žalbe')
                    ->schema([
                        Forms\Components\TextInput::make('broj_primljenih_izvrsilaca')
                            ->label('Broj primljenih izvršilaca')
                            ->numeric(),
                        Forms\Components\TextInput::make('ocena_sa_vrednovanja')
                            ->label('Ocena sa vrednovanja')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Broj žalbi na rešenje o odbacivanju prijave')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_zalbi_na_resenje_o_prijemu_u_radni_odnos')
                            ->label('Broj žalbi na rešenje o prijemu u radni odnos')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave')
                            ->label('Broj usvojenih žalbi na rešenje o odbacivanju prijave')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_izvrsilaca_ponovno_oglasavanje')
                            ->label('Broj izvršilaca - ponovno oglašavanje')
                            ->numeric(),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Podaci o prijavama')
                    ->schema([
                        Forms\Components\TextInput::make('ukupan_broj_prijava')
                            ->label('Ukupan broj prijava')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_iz_organa')
                            ->label('Broj prijava iz organa')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_iz_drugih_organa')
                            ->label('Broj prijava iz drugih organa')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_prijava_van_drzavnih_organa')
                            ->label('Broj prijava van državnih organa')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Validne prijave')
                    ->schema([
                        Forms\Components\TextInput::make('broj_validnih_prijava')
                            ->label('Broj validnih prijava')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_iz_organa')
                            ->label('Broj validnih prijava iz organa')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_iz_drugog_organa')
                            ->label('Broj validnih prijava iz drugog organa')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_validnih_prijava_van_drzavnih_organa')
                            ->label('Broj validnih prijava van državnih organa')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Kandidati koji su ispunili merila')
                    ->schema([
                        Forms\Components\TextInput::make('broj_kandidata_koji_su_ispunlii_merila_ofk')
                            ->label('Broj kandidata koji su ispunili merila OFK')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_koji_su_ispunlii_merila_pfk')
                            ->label('Broj kandidata koji su ispunili merila PFK')
                            ->numeric(),
                        Forms\Components\TextInput::make('provera_pfk')
                            ->label('Provera PFK')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_ispunili_merila_pk')
                            ->label('Broj kandidata koji su ispunili merila PK')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_odazvanih_kandidata_na_zavrsnom_razgovoru')
                            ->label('Broj odazvanih kandidata na završnom razgovoru')
                            ->numeric(),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Lista kandidata')
                    ->schema([
                        Forms\Components\TextInput::make('broj_kandidata_na_listi')
                            ->label('Broj kandidata na listi')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_iz_organa_na_listi')
                            ->label('Broj kandidata iz organa na listi')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_iz_drugog_drzavnog_organa_na_listi')
                            ->label('Broj kandidata iz drugog državnog organa na listi')
                            ->numeric(),
                        Forms\Components\TextInput::make('broj_kandidata_van_drzavnih_organa_na_listi')
                            ->label('Broj kandidata van državnih organa na listi')
                            ->numeric(),
                    ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Izabrani kandidat')
                    ->schema([
                        Forms\Components\Select::make('izabrani_kandidat')
                            ->label('Izabrani kandidat')
                            ->relationship('izabraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_ofk')
                            ->label('Broj bodova na OFK')
                            ->options([
                                7 => '7',
                                8 => '8',
                                9 => '9',
                            ]),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_pfk')
                            ->label('Broj bodova na PFK')
                            ->options(array_merge(
                                [0 => '0', 3 => '3', 5 => '5', 8 => '8'],
                                array_combine(range(10, 20), range(10, 20))
                            )),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_pk')
                            ->label('Broj bodova na PK')
                            ->options(array_combine(range(10, 30), range(10, 30))),
                        Forms\Components\Select::make('broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Broj bodova na završnom razgovoru')
                            ->options([
                                2 => '2',
                                4 => '4',
                                6 => '6',
                            ]),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Drugoplasirani kandidat')
                    ->schema([
                        Forms\Components\Select::make('drugoplasirani_kandidat')
                            ->label('Drugoplasirani kandidat')
                            ->relationship('drugoplasiraniKandidatRelation', 'izabrani_kandidat')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_ofk')
                            ->label('Broj bodova na OFK')
                            ->options([
                                7 => '7',
                                8 => '8',
                                9 => '9',
                            ]),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_pfk')
                            ->label('Broj bodova na PFK')
                            ->options(array_merge(
                                [0 => '0', 3 => '3', 5 => '5', 8 => '8'],
                                array_combine(range(10, 20), range(10, 20))
                            )),
                        Forms\Components\Select::make('broj_bodova_drugplasiranog_kandidata_na_pk')
                            ->label('Broj bodova na PK')
                            ->options(array_combine(range(10, 30), range(10, 30))),
                        Forms\Components\Select::make('broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru')
                            ->label('Broj bodova na završnom razgovoru')
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
                    ->label('Vrsta organa')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('organRelation.organ')
                    ->label('Organ')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('naziv_radnog_mesta')
                    ->label('Naziv radnog mesta')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('zvanjeRelation.zvanje')
                    ->label('Zvanje')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mestoRadaRelation.mesto')
                    ->label('Mesto rada')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
