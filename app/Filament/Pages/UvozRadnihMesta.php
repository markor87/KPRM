<?php

namespace App\Filament\Pages;

use App\Exports\IzvestajUvozaExport;
use App\Exports\SablonUvozaExport;
use App\Services\Uvoz\CitacTabele;
use App\Services\Uvoz\IzvestajUvoza;
use App\Services\Uvoz\MapiranjeKolona;
use App\Services\Uvoz\RedUvoza;
use App\Services\Uvoz\SemaUvoza;
use App\Services\Uvoz\UvozRadnihMesta as ServisUvoza;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class UvozRadnihMesta extends Page
{
    /** Dozvola se drzi izricito, jer filament-shield ima super_admin.define_via_gate => false. */
    public const PERMISSION = 'View:UvozRadnihMesta';

    /** Koliko redova tabele „шта се мења" ide na ekran; ceo spisak je u .xlsx-u. */
    public const MAX_IZMENA_NA_EKRANU = 300;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.pages.uvoz-radnih-mesta';

    protected static string | \UnitEnum | null $navigationGroup = 'Admin Panel';

    protected static ?string $navigationLabel = 'Увоз радних места';

    protected static ?string $title = 'Увоз радних места из Excel-а';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    /** Putanja do trajne kopije fajla (Livewire privremeni prostor se brise). */
    public ?string $putanjaFajla = null;

    public ?string $imeDatoteke = null;

    /** @var array<int, string> */
    public array $zaglavlje = [];

    /** @var array<int, string> kljucevi polja koje je automatika sama pogodila */
    public array $automatskiMapirani = [];

    /** Sazetak probnog uvoza - Livewire cuva samo nizove, ne DTO. */
    public ?array $izvestaj = null;

    public bool $samoNemapirane = false;

    /**
     * Impersonacija se odbija ovde, a ne tek pri upisu: bez toga bi korisnik prosao ceo
     * carobnjak pa dobio go abort(403) iz PodaciORadnomMestu::booted() na prvom redu.
     */
    public static function canAccess(): bool
    {
        $korisnik = auth()->user();

        if ($korisnik === null || ! $korisnik->can(self::PERMISSION)) {
            return false;
        }

        return ! app('impersonate')->isImpersonating();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->ocistiStareDatoteke();

        $this->form->fill(['rezim' => ServisUvoza::REZIM_OBOJE]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    $this->korakDatoteka(),
                    $this->korakMapiranje(),
                    $this->korakProvera(),
                ])
                    ->columnSpanFull()
                    // Carobnjak nema svoj submit - upis pokrece dugme „Покрени увоз" u 3. koraku.
                    ->submitAction(null),
            ])
            ->statePath('data');
    }

    // ------------------------------------------------------------------
    // Korak 1 - datoteka
    // ------------------------------------------------------------------

    private function korakDatoteka(): Step
    {
        return Step::make('Датотека')
            ->description('Изаберите .xlsx или .csv')
            ->schema([
                Text::make(
                    'Датотека мора имати ред са називима колона на врху. Шифарничке колоне '
                    . '(орган, звање, статус…) садрже бројчане ид-ове, не називе. Највише '
                    . CitacTabele::MAX_REDOVA . ' редова по датотеци.'
                ),

                Actions::make([
                    Action::make('sablon')
                        ->label('Преузми шаблон (.xlsx)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(fn () => Excel::download(new SablonUvozaExport(), 'sablon-uvoza-radnih-mesta.xlsx')),
                ]),

                FileUpload::make('datoteka')
                    ->label('Датотека за увоз')
                    // Filament ne cuva fajl - mi ga sami prepisujemo u storage/app/private/uvoz
                    // cim stigne, jer nam treba i pre nego sto se obrazac posalje.
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->maxSize(20 * 1024)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->ucitajDatoteku($state)),

                View::make('filament.pages.uvoz.sazetak-datoteke')
                    ->visible(fn (): bool => $this->zaglavlje !== []),
            ]);
    }

    private function ucitajDatoteku(mixed $state): void
    {
        // I samo citanje zaglavlja prolazi kroz celu datoteku, pa i ovde treba prostora.
        $this->pripremiResurse();

        $this->resetujAnalizu();
        $this->zaglavlje = [];
        $this->putanjaFajla = null;
        $this->imeDatoteke = null;

        $datoteka = is_array($state) ? (reset($state) ?: null) : $state;

        if ($datoteka === null || $datoteka === '') {
            return;
        }

        // U afterStateUpdated stanje je jos uvek TemporaryUploadedFile - Filament fajl
        // snima tek u saveUploadedFiles(), pri slanju obrasca (BaseFileUpload.php:816).
        // Ranije je ovde stajala provera is_string() koja je tiho izlazila i ostavljala
        // drugi korak prazan.
        if ($datoteka instanceof TemporaryUploadedFile) {
            $izvor = $datoteka->getRealPath();
            $imeDatoteke = $datoteka->getClientOriginalName();
        } elseif (is_string($datoteka)) {
            $izvor = Storage::disk('local')->path($datoteka);
            $imeDatoteke = basename($datoteka);
        } else {
            $this->greska('Датотека није препозната', 'Покушајте поново да је изаберете.');

            return;
        }

        if (! is_file($izvor)) {
            $this->greska('Датотека није пронађена', 'Отпремање можда није довршено — покушајте поново.');

            return;
        }

        // Kopiramo van Livewire privremenog prostora - razmak izmedju probnog i stvarnog
        // uvoza mora da prezivi, a privremeni fajlovi se brisu.
        $odrediste = storage_path('app/private/uvoz/' . Str::uuid());

        if (! is_dir($odrediste)) {
            mkdir($odrediste, 0775, true);
        }

        // Nastavak mora ostati, jer po njemu CitacTabele bira xlsx ili csv put.
        $nastavak = mb_strtolower(pathinfo($imeDatoteke, PATHINFO_EXTENSION));

        if (! in_array($nastavak, ['xlsx', 'xls', 'csv'], true)) {
            $this->greska('Неподржан тип датотеке', 'Прихватају се .xlsx, .xls и .csv.');

            return;
        }

        $trajnaPutanja = $odrediste . DIRECTORY_SEPARATOR . $imeDatoteke;

        copy($izvor, $trajnaPutanja);

        try {
            ['zaglavlje' => $zaglavlje] = app(CitacTabele::class)->procitaj($trajnaPutanja);
        } catch (Throwable $e) {
            $this->greska('Датотека се не може прочитати', $e->getMessage());

            return;
        }

        $this->putanjaFajla = $trajnaPutanja;
        $this->imeDatoteke = $imeDatoteke;
        $this->zaglavlje = $zaglavlje;

        $mapiranje = app(MapiranjeKolona::class);
        ['mapa' => $mapa, 'automatski' => $automatski] = $mapiranje->automatski($zaglavlje);
        $mapa = $mapiranje->dopuniZapamcenim($mapa, $zaglavlje);

        $this->automatskiMapirani = $automatski;
        $this->data['mapiranje'] = $mapa;

        Notification::make()
            ->title('Датотека учитана')
            ->body('Аутоматски мапирано ' . count($mapa) . ' од ' . count(SemaUvoza::polja()) . ' поља.')
            ->success()
            ->send();
    }

    // ------------------------------------------------------------------
    // Korak 2 - mapiranje kolona
    // ------------------------------------------------------------------

    private function korakMapiranje(): Step
    {
        return Step::make('Мапирање колона')
            ->description('Проверите шта је аутоматика погодила')
            ->schema($this->komponenteMapiranja());
    }

    /**
     * Стабло компоненти је УВЕК исто - од стања зависе само опције и видљивост, а њих
     * Filament рачуна при сваком исцртавању.
     *
     * Раније се овде враћало различито стабло у зависности од тога да ли је датотека
     * учитана, кроз `schema(closure)`. Filament дечја стабла кешира, па је други корак
     * заувек остајао на поруци „Прво учитајте датотеку".
     *
     * @return array<int, mixed>
     */
    private function komponenteMapiranja(): array
    {
        $bezDatoteke = fn (): bool => $this->zaglavlje === [];
        $saDatotekom = fn (): bool => $this->zaglavlje !== [];

        $komponente = [
            Text::make('Прво учитајте датотеку у првом кораку.')
                ->visible($bezDatoteke),

            Toggle::make('samo_nemapirane')
                ->label('Прикажи само немапирана поља')
                ->live()
                ->visible($saDatotekom)
                ->afterStateUpdated(function ($state): void {
                    $this->samoNemapirane = (bool) $state;
                }),

            View::make('filament.pages.uvoz.sazetak-mapiranja')
                ->visible($saDatotekom),
        ];

        foreach (SemaUvoza::poTabovima() as $tab => $polja) {
            $komponente[] = Section::make($tab)
                ->collapsible()
                ->visible($saDatotekom)
                ->schema(array_map(
                    fn (string $kljuc, array $def) => $this->selectMapiranja($kljuc, $def),
                    array_keys($polja),
                    $polja,
                ))
                ->columns(2);
        }

        $izvedena = [];

        foreach (SemaUvoza::izvedenaPolja() as $labela => $objasnjenje) {
            $izvedena[] = Select::make('izvedeno_' . Str::slug($labela, '_'))
                ->label($labela)
                ->options([])
                ->placeholder('не увози се')
                ->helperText($objasnjenje)
                ->disabled()
                ->dehydrated(false);
        }

        $komponente[] = Section::make('Изведена поља')
            ->description('Ова поља модел сам израчунава при упису, па се из датотеке занемарују.')
            ->collapsed()
            ->visible($saDatotekom)
            ->schema($izvedena)
            ->columns(2);

        return $komponente;
    }

    private function selectMapiranja(string $kljuc, array $def): Select
    {
        return Select::make('mapiranje.' . $kljuc)
            ->label($def['labela'])
            // Opcije se racunaju pri svakom iscrtavanju, pa prate ucitanu datoteku.
            ->options(fn (): array => $this->opcijeKolona())
            ->placeholder('— није мапирано —')
            ->searchable()
            ->helperText(function () use ($kljuc, $def): ?string {
                $znacka = match (true) {
                    ! isset($this->data['mapiranje'][$kljuc]) => 'није мапирано',
                    in_array($kljuc, $this->automatskiMapirani, true) => 'аутоматски',
                    default => 'ручно',
                };

                return trim($znacka . ' · ' . ($def['pomoc'] ?? ''), ' ·');
            })
            ->live()
            ->afterStateUpdated(fn () => $this->resetujAnalizu())
            ->visible(fn (): bool => ! $this->samoNemapirane || ! isset($this->data['mapiranje'][$kljuc]));
    }

    /** @return array<int, string> indeks kolone => naziv u datoteci */
    private function opcijeKolona(): array
    {
        $opcije = [];

        foreach ($this->zaglavlje as $indeks => $naziv) {
            $opcije[$indeks] = $naziv === '' ? 'Колона ' . ($indeks + 1) . ' (без назива)' : $naziv;
        }

        return $opcije;
    }

    // ------------------------------------------------------------------
    // Korak 3 - provera i uvoz
    // ------------------------------------------------------------------

    private function korakProvera(): Step
    {
        return Step::make('Провера и увоз')
            ->description('Прво пробни увоз, тек онда упис')
            ->schema([
                Radio::make('rezim')
                    ->label('Шта се ради са редовима из датотеке')
                    ->options(ServisUvoza::rezimi())
                    ->default(ServisUvoza::REZIM_OBOJE)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetujAnalizu()),

                Actions::make([
                    Action::make('testiraj')
                        ->label('Тестирај увоз')
                        ->icon('heroicon-o-beaker')
                        ->color('warning')
                        ->action(fn () => $this->testirajUvoz()),

                    Action::make('preuzmi_izvestaj')
                        ->label('Преузми цео извештај (.xlsx)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->visible(fn (): bool => $this->izvestaj !== null)
                        ->action(fn () => $this->preuzmiIzvestaj()),

                    Action::make('pokreni')
                        ->label('Покрени увоз')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => (bool) ($this->izvestaj['sme_se_uvesti'] ?? false))
                        ->requiresConfirmation()
                        ->modalHeading('Потврда увоза')
                        ->modalDescription(fn (): string => $this->tekstPotvrde())
                        ->modalSubmitActionLabel('Да, упиши')
                        ->action(fn () => $this->pokreniUvoz()),
                ]),

                View::make('filament.pages.uvoz.rezultat')
                    ->visible(fn (): bool => $this->izvestaj !== null),
            ]);
    }

    public function testirajUvoz(): void
    {
        if (! $this->spremanZaAnalizu()) {
            return;
        }

        try {
            $izvestaj = app(ServisUvoza::class)->analiziraj(
                $this->putanjaFajla,
                $this->trenutnaMapa(),
                $this->data['rezim'] ?? ServisUvoza::REZIM_OBOJE,
            );
        } catch (Throwable $e) {
            $this->izvestaj = null;

            Notification::make()
                ->title('Пробни увоз није успео')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->izvestaj = $this->uNiz($izvestaj);

        Notification::make()
            ->title('Пробни увоз завршен — ништа није уписано')
            ->body($this->sazetakBrojeva($izvestaj))
            ->{$izvestaj->brojGresaka() > 0 ? 'warning' : 'success'}()
            ->send();
    }

    public function pokreniUvoz(): void
    {
        if (! $this->spremanZaAnalizu() || ! ($this->izvestaj['sme_se_uvesti'] ?? false)) {
            return;
        }

        try {
            $izvestaj = app(ServisUvoza::class)->izvrsi(
                $this->putanjaFajla,
                $this->trenutnaMapa(),
                $this->data['rezim'] ?? ServisUvoza::REZIM_OBOJE,
                $this->imeDatoteke ?? 'непозната датотека',
            );
        } catch (Throwable $e) {
            Notification::make()
                ->title('Увоз није успео — ништа није уписано')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        app(MapiranjeKolona::class)->zapamti($this->trenutnaMapa(), $this->zaglavlje);

        $this->izvestaj = $this->uNiz($izvestaj);
        $this->izvestaj['sme_se_uvesti'] = false;
        $this->izvestaj['zavrsen'] = true;

        Notification::make()
            ->title('Увоз завршен')
            ->body($this->sazetakBrojeva($izvestaj))
            ->success()
            ->persistent()
            ->send();
    }

    public function preuzmiIzvestaj()
    {
        if (! $this->spremanZaAnalizu()) {
            return null;
        }

        $izvestaj = app(ServisUvoza::class)->analiziraj(
            $this->putanjaFajla,
            $this->trenutnaMapa(),
            $this->data['rezim'] ?? ServisUvoza::REZIM_OBOJE,
        );

        return Excel::download(
            new IzvestajUvozaExport($izvestaj),
            'izvestaj-uvoza-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }

    // ------------------------------------------------------------------
    // Pomocne
    // ------------------------------------------------------------------

    /** Vidljiva poruka o gresci - nijedan neuspeh ne sme proci tiho. */
    private function greska(string $naslov, string $telo): void
    {
        Notification::make()
            ->title($naslov)
            ->body($telo)
            ->danger()
            ->persistent()
            ->send();
    }

    /** Rezultat probnog uvoza vazi samo za mapiranje i rezim koji su tada bili izabrani. */
    private function resetujAnalizu(): void
    {
        $this->izvestaj = null;
    }

    /**
     * Uvoz je jedan tezak zahtev, ne prosecna strana.
     *
     * PhpSpreadsheet drzi celu datoteku u memoriji — datoteka od 1735 redova i 82 kolone
     * (142.000 celija) trazi oko 110 MB, a uobicajeno podesavanje je 128 MB, pa upis puca
     * na „Allowed memory size exhausted". Podizemo granicu samo za ovaj zahtev.
     *
     * Ako je ini_set na serveru zabranjen, ostaje zatecena vrednost — zato i dalje vazi
     * CitacTabele::MAX_REDOVA kao gornja granica.
     */
    private function pripremiResurse(): void
    {
        $trenutna = trim((string) ini_get('memory_limit'));

        // -1 znaci „bez ogranicenja"; tada nema sta da se dize.
        if ($trenutna !== '-1') {
            @ini_set('memory_limit', '512M');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
    }

    private function spremanZaAnalizu(): bool
    {
        $this->pripremiResurse();

        if ($this->putanjaFajla === null || ! is_file($this->putanjaFajla)) {
            Notification::make()
                ->title('Датотека није учитана')
                ->body('Вратите се на први корак и изаберите датотеку.')
                ->danger()
                ->send();

            return false;
        }

        if ($this->trenutnaMapa() === []) {
            Notification::make()
                ->title('Ниједна колона није мапирана')
                ->body('Вратите се на други корак и повежите бар једну колону.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    /** @return array<string, int> */
    private function trenutnaMapa(): array
    {
        $sirovo = $this->data['mapiranje'] ?? [];

        $mapa = [];

        foreach ($sirovo as $kljuc => $indeks) {
            if ($indeks === null || $indeks === '') {
                continue;
            }

            $mapa[$kljuc] = (int) $indeks;
        }

        return $mapa;
    }

    private function sazetakBrojeva(IzvestajUvoza $izvestaj): string
    {
        return 'Нових: ' . $izvestaj->brojNovih()
            . ' · Ажурираних: ' . $izvestaj->brojAzuriranih()
            . ' · Прескочених: ' . $izvestaj->brojPreskocenih()
            . ' · Грешака: ' . $izvestaj->brojGresaka();
    }

    private function tekstPotvrde(): string
    {
        $praznjenja = $this->izvestaj['broj_praznjenja'] ?? 0;

        $tekst = 'Биће унето ' . ($this->izvestaj['broj_novih'] ?? 0) . ' нових и ажурирано '
            . ($this->izvestaj['broj_azuriranih'] ?? 0) . ' постојећих записа.';

        if ($praznjenja > 0) {
            $tekst .= ' Уз то, ' . ServisUvoza::brojPolja($praznjenja) . ' биће ИСПРАЖЊЕНО, јер су '
                . 'одговарајуће ћелије у датотеци празне.';
        }

        return $tekst . ' Ова радња се не може опозвати.';
    }

    /** DTO -> niz, jer Livewire cuva samo serijalizabilno stanje. */
    private function uNiz(IzvestajUvoza $izvestaj): array
    {
        return [
            'broj_novih' => $izvestaj->brojNovih(),
            'broj_azuriranih' => $izvestaj->brojAzuriranih(),
            'broj_preskocenih' => $izvestaj->brojPreskocenih(),
            'broj_gresaka' => $izvestaj->brojGresaka(),
            'broj_praznjenja' => $izvestaj->brojPraznjenja(),
            'broj_izmenjenih_polja' => $izvestaj->brojIzmenjenihPolja(),
            'sme_se_uvesti' => $izvestaj->smeSeUvesti(),
            'zavrsen' => false,
            'broj_zanimljivih' => $izvestaj->brojZanimljivih(),
            'prikazano' => count($izvestaj->redoviZaPrikaz()),
            'redovi' => array_map(static fn (RedUvoza $red): array => [
                'broj_reda' => $red->brojReda,
                'ishod' => $red->ishod,
                'opis' => $red->opisIshoda(),
                'greske' => $red->greske,
                'upozorenja' => $red->upozorenja,
            ], $izvestaj->redoviZaPrikaz()),
            // Na ekranu samo izmene postojecih zapisa - nov zapis bi dao 80 redova
            // i udavio ono sto se stvarno menja. Ceo spisak je u .xlsx izvestaju.
            'izmene' => array_slice($izvestaj->izmene(samoAzuriranja: true), 0, self::MAX_IZMENA_NA_EKRANU),
            'ukupno_izmena' => count($izvestaj->izmene(samoAzuriranja: true)),
            'novi_zapisi' => $izvestaj->noviZapisiSaBrojemPolja(),
        ];
    }

    /** Ostaci starijih od 24 sata - fajl je vec upisan ili je korisnik odustao. */
    private function ocistiStareDatoteke(): void
    {
        $koren = storage_path('app/private/uvoz');

        if (! is_dir($koren)) {
            return;
        }

        foreach (glob($koren . DIRECTORY_SEPARATOR . '*') ?: [] as $direktorijum) {
            if (! is_dir($direktorijum) || filemtime($direktorijum) > now()->subDay()->getTimestamp()) {
                continue;
            }

            foreach (glob($direktorijum . DIRECTORY_SEPARATOR . '*') ?: [] as $datoteka) {
                @unlink($datoteka);
            }

            @rmdir($direktorijum);
        }
    }
}
