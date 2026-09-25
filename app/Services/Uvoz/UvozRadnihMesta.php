<?php

namespace App\Services\Uvoz;

use App\Models\PodaciORadnomMestu;
use App\Models\SifarnikKodoviGradova;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Srce uvoza: analiziraj() prolazi ceo fajl bez ijednog upisa, izvrsi() ponavlja isti
 * prolaz u jednoj transakciji.
 *
 * Pravilo upisa pri azuriranju odlucuje MAPIRANOST kolone, ne sadrzaj celije:
 *   kolona mapirana + celija popunjena -> nova vrednost
 *   kolona mapirana + celija prazna    -> null (stara vrednost se brise)
 *   kolona nije mapirana               -> polje se uopste ne dira
 *
 * OGRANICENJE TRANSAKCIJE: `mesto_rada_podaci_o_radnom_mestu` je MyISAM (kao i
 * `activity_log`), pa se upisi u tu pivot tabelu NE VRACAJU ako transakcija pukne -
 * `podaci_o_radnom_mestu` i `oblast_rada_podaci_o_radnom_mestu` su InnoDB i vracaju se.
 * Praktican rizik je mali jer analiziraj() odbija ceo fajl na prvu gresku, pa do upisa
 * dolazi samo kada su svi redovi vec provereni; trajno resenje je prebacivanje te tabele
 * na InnoDB, sto je zaseban zadatak.
 */
class UvozRadnihMesta
{
    public const REZIM_NOVI = 'novi';
    public const REZIM_AZURIRANJE = 'azuriranje';
    public const REZIM_OBOJE = 'oboje';

    /** Terminalni ishodi konkursa - drzi se u koraku sa PodaciORadnomMestu::booted(). */
    private const TERMINALNI_ISHODI = [1, 2, 3, 5];

    /** @var array<string, array<int, true>> kljuc polja => skup postojecih id-ova */
    private array $postojeciIdovi = [];

    /** @var Collection<int, PodaciORadnomMestu> ид записа => запис */
    private Collection $postojeciZapisi;

    public function __construct(
        private readonly CitacTabele $citac = new CitacTabele(),
    ) {
        $this->postojeciZapisi = collect();
    }

    /**
     * Srpska množina uz broj: 1 поље, 2-4 поља, 5+ поља; 11-14 иду по правилу за 5+.
     */
    public static function brojPolja(int $broj): string
    {
        $poslednja = $broj % 10;
        $poslednjeDve = $broj % 100;

        return match (true) {
            $poslednjeDve >= 11 && $poslednjeDve <= 14 => $broj . ' попуњених поља',
            $poslednja === 1 => $broj . ' попуњено поље',
            $poslednja >= 2 && $poslednja <= 4 => $broj . ' попуњена поља',
            default => $broj . ' попуњених поља',
        };
    }

    public static function rezimi(): array
    {
        return [
            self::REZIM_NOVI => 'Само нови записи',
            self::REZIM_AZURIRANJE => 'Само ажурирање постојећих',
            self::REZIM_OBOJE => 'И једно и друго',
        ];
    }

    /**
     * Prolazi ceo fajl i za svaki red odlucuje sta bi se desilo. NISTA ne upisuje.
     *
     * @param  array<string, int>  $mapa  kljuc polja => indeks kolone u fajlu
     */
    public function analiziraj(string $putanja, array $mapa, string $rezim): IzvestajUvoza
    {
        ['redovi' => $redovi] = $this->citac->procitaj($putanja);

        $this->ucitajSifarnike($mapa);
        $this->ucitajPostojeceZapise($redovi, $mapa);

        $polja = SemaUvoza::polja();
        $rezultat = [];

        // Sirovi red se oslobadja cim se obradi. Bez toga cela datoteka i ceo izvestaj
        // stoje u memoriji istovremeno — na 1735 redova to je 76 MB viska, dovoljno da
        // se probije uobicajenih 128 MB u web zahtevu.
        foreach (array_keys($redovi) as $brojReda) {
            $rezultat[] = $this->analizirajRed($brojReda, $redovi[$brojReda], $mapa, $polja, $rezim);

            unset($redovi[$brojReda]);
        }

        return new IzvestajUvoza($rezultat, $rezim);
    }

    /**
     * Svi zapisi na koje datoteka pokazuje, u JEDNOM upitu, sa unapred ucitanim vezama.
     *
     * Bez ovoga je svaki red trazio zapis pa jos i mesta i oblasti rada zasebno — na
     * datoteci od 1281 reda to je bilo 3855 upita i preko 8 sekundi.
     *
     * @param  array<int, array<int, mixed>>  $redovi
     * @param  array<string, int>  $mapa
     */
    private function ucitajPostojeceZapise(array $redovi, array $mapa): void
    {
        $this->postojeciZapisi = collect();

        if (! isset($mapa['id'])) {
            return;
        }

        $idovi = [];

        foreach ($redovi as $red) {
            $sirovId = $red[$mapa['id']] ?? null;

            if (NormalizatorVrednosti::jePrazno($sirovId)) {
                continue;
            }

            try {
                $id = NormalizatorVrednosti::ceoBroj($sirovId, 1);
            } catch (GreskaVrednosti) {
                // Neispravan ID prijavljuje sam red; ovde ga samo preskacemo.
                continue;
            }

            if ($id !== null) {
                $idovi[$id] = true;
            }
        }

        if ($idovi === []) {
            return;
        }

        $this->postojeciZapisi = PodaciORadnomMestu::query()
            ->with(['mestaRada', 'oblastiRada'])
            ->whereIn('id', array_keys($idovi))
            ->get()
            ->keyBy('id');
    }

    /**
     * Ponavlja isti prolaz i upisuje. Baca izuzetak ako izvestaj ima ijednu gresku -
     * probni uvoz je obavezan korak, ovo je samo pojas.
     *
     * @param  array<string, int>  $mapa
     */
    public function izvrsi(string $putanja, array $mapa, string $rezim, string $imeDatoteke): IzvestajUvoza
    {
        $izvestaj = $this->analiziraj($putanja, $mapa, $rezim);

        if (! $izvestaj->smeSeUvesti()) {
            throw new \RuntimeException(
                'Увоз није покренут: датотека има ' . $izvestaj->brojGresaka() . ' грешака. Исправите их па поновите пробни увоз.'
            );
        }

        $idNovih = [];
        $idAzuriranih = [];

        DB::transaction(function () use ($izvestaj, &$idNovih, &$idAzuriranih): void {
            // Jedan zapis u dnevniku po uvozu, ne po redu: 800 redova ne sme dati
            // 800 stavki u activity_log. withoutLogs() vraca logovanje u finally,
            // pa ne moze ostati ugaseno ni ako upis pukne.
            activity()->withoutLogs(function () use ($izvestaj, &$idNovih, &$idAzuriranih): void {
                foreach ($izvestaj->redovi as $red) {
                    if (! $red->jeUpotrebljiv()) {
                        continue;
                    }

                    $zapis = $red->ishod === RedUvoza::ISHOD_AZURIRANJE
                        // Zapis je vec ucitan u analizi - nema potrebe za novim upitom.
                        ? ($this->postojeciZapisi->get($red->idZapisa)
                            ?? PodaciORadnomMestu::findOrFail($red->idZapisa))
                        : new PodaciORadnomMestu();

                    $this->upisiRed($zapis, $red);

                    if ($red->ishod === RedUvoza::ISHOD_AZURIRANJE) {
                        $idAzuriranih[] = $zapis->id;
                    } else {
                        $idNovih[] = $zapis->id;
                    }
                }
            });
        });

        // Dnevnik se pise TEK POSLE potvrde transakcije. Tabela activity_log je MyISAM,
        // pa upis u nju ne bi bio vracen ni da je unutra - stajala bi stavka o uvozu
        // koji se nije desio. Ovako je stavka dokaz da je upis proslo.
        activity('uvoz')
            ->causedBy(auth()->user())
            ->withProperties([
                'datoteka' => $imeDatoteke,
                'rezim' => self::rezimi()[$izvestaj->rezim] ?? $izvestaj->rezim,
                'novih' => count($idNovih),
                'azuriranih' => count($idAzuriranih),
                'preskocenih' => $izvestaj->brojPreskocenih(),
                'id_novih' => $idNovih,
                'id_azuriranih' => $idAzuriranih,
            ])
            ->log('Увоз радних места из Excel-а');

        return $izvestaj;
    }

    /**
     * @param  array<int, mixed>  $red
     * @param  array<string, int>  $mapa
     * @param  array<string, array>  $polja
     */
    private function analizirajRed(int $brojReda, array $red, array $mapa, array $polja, string $rezim): RedUvoza
    {
        $greske = [];
        $upozorenja = [];
        $vrednosti = [];

        // 1. Kljuc reda - za sada samo oblik vrednosti, ne i postojanje zapisa.
        $idZapisa = null;
        $imaId = false;

        if (isset($mapa['id'])) {
            $sirovId = $red[$mapa['id']] ?? null;

            if (! NormalizatorVrednosti::jePrazno($sirovId)) {
                $imaId = true;

                try {
                    $idZapisa = NormalizatorVrednosti::ceoBroj($sirovId, 1);
                } catch (GreskaVrednosti $e) {
                    $greske[] = 'Колона „ID": ' . $e->getMessage();
                }
            }
        }

        // 2. Rezim odlucuje PRE nego sto se proveri postojanje zapisa: red koji izabrani
        // rezim ionako ne dira ne sme da obori ceo uvoz zato sto mu je ID pogresan.
        $preskoci = match ($rezim) {
            self::REZIM_NOVI => $imaId
                ? 'режим прима само нове записе, а ред има попуњен ID'
                : null,
            self::REZIM_AZURIRANJE => ! $imaId
                ? 'режим ажурира само постојеће записе, а ред нема ID'
                : null,
            default => null,
        };

        if ($preskoci !== null) {
            return new RedUvoza(
                brojReda: $brojReda,
                ishod: RedUvoza::ISHOD_PRESKOCEN,
                idZapisa: $idZapisa,
                razlogPreskakanja: $preskoci,
            );
        }

        // 3. Tek sada postojanje zapisa - iz unapred ucitane mape, ne novim upitom.
        $postojeci = $idZapisa === null ? null : $this->postojeciZapisi->get($idZapisa);

        if ($idZapisa !== null && $postojeci === null) {
            $greske[] = 'Колона „ID": запис #' . $idZapisa . ' не постоји (или је обрисан).';
        }

        $jeAzuriranje = $postojeci !== null;

        // 4. Svaka mapirana kolona: procitaj, proveri tip, proveri sifarnik, uporedi sa bazom.
        $izmene = [];

        foreach ($mapa as $kljuc => $indeks) {
            if ($kljuc === 'id' || ! isset($polja[$kljuc])) {
                continue;
            }

            $def = $polja[$kljuc];
            $sirovo = $red[$indeks] ?? null;

            try {
                $vrednost = $this->normalizuj($sirovo, $def);
            } catch (GreskaVrednosti $e) {
                $greske[] = 'Колона „' . $def['labela'] . '": ' . $e->getMessage();

                continue;
            }

            $greskaSifre = $this->proveriSifarnik($kljuc, $def, $vrednost);

            if ($greskaSifre !== null) {
                $greske[] = 'Колона „' . $def['labela'] . '": ' . $greskaSifre;

                continue;
            }

            $vrednosti[$kljuc] = $vrednost;

            $izmena = $this->izmenaPolja($postojeci, $kljuc, $def, $vrednost);

            if ($izmena !== null) {
                $izmene[] = $izmena;
            }
        }

        if ($greske !== []) {
            return new RedUvoza(
                brojReda: $brojReda,
                ishod: RedUvoza::ISHOD_GRESKA,
                idZapisa: $idZapisa,
                greske: $greske,
            );
        }

        // 5. Upozorenja - ne blokiraju upis, ali korisnik mora da ih vidi pre nego pritisne dugme.
        $poljaZaPraznjenje = array_values(array_map(
            static fn (array $iz): string => $iz['polje'],
            array_filter($izmene, static fn (array $iz): bool => $iz['praznjenje']),
        ));

        if ($poljaZaPraznjenje !== []) {
            $upozorenja[] = 'Биће испражњено ' . self::brojPolja(count($poljaZaPraznjenje)) . ': '
                . implode(', ', $poljaZaPraznjenje) . '.';
        }

        $upozorenja = array_merge(
            $upozorenja,
            $this->upozorenjaZaVeze($vrednosti, $mapa, $postojeci),
            $this->upozorenjaZaIzvedenaPolja($vrednosti, $postojeci),
        );

        return new RedUvoza(
            brojReda: $brojReda,
            ishod: $jeAzuriranje ? RedUvoza::ISHOD_AZURIRANJE : RedUvoza::ISHOD_NOV,
            idZapisa: $idZapisa,
            vrednosti: $vrednosti,
            upozorenja: $upozorenja,
            izmene: $izmene,
        );
    }

    /**
     * @param  array<string, mixed>  $def
     *
     * @throws GreskaVrednosti
     */
    private function normalizuj(mixed $sirovo, array $def): mixed
    {
        return match ($def['tip']) {
            SemaUvoza::TIP_TEKST => NormalizatorVrednosti::tekst($sirovo, $def['max_duzina'] ?? null),
            SemaUvoza::TIP_CEO_BROJ => NormalizatorVrednosti::ceoBroj($sirovo, $def['min'] ?? null, $def['max'] ?? null),
            SemaUvoza::TIP_DECIMAL => NormalizatorVrednosti::decimal($sirovo, $def['min'] ?? null, $def['max'] ?? null),
            SemaUvoza::TIP_DATUM => NormalizatorVrednosti::datum($sirovo),
            SemaUvoza::TIP_LOGICKA => NormalizatorVrednosti::logicka($sirovo),
            SemaUvoza::TIP_SIFARNIK => NormalizatorVrednosti::sifarnik($sirovo),
            SemaUvoza::TIP_MESTA_RADA => NormalizatorVrednosti::mestaRada($sirovo),
            SemaUvoza::TIP_OBLASTI_RADA => NormalizatorVrednosti::oblastiRada($sirovo),
            default => NormalizatorVrednosti::tekst($sirovo),
        };
    }

    /**
     * Postojanje id-a u sifarniku. Baza to nece uraditi: pivot tabela mesta rada je MyISAM,
     * pa su njeni "strani kljucevi" samo indeksi, a sifarnicke kolone same tabele nemaju FK.
     *
     * @param  array<string, mixed>  $def
     */
    private function proveriSifarnik(string $kljuc, array $def, mixed $vrednost): ?string
    {
        if ($vrednost === null || ! isset($this->postojeciIdovi[$kljuc])) {
            return null;
        }

        $idovi = $this->postojeciIdovi[$kljuc];

        $zaProveru = match ($def['tip']) {
            SemaUvoza::TIP_MESTA_RADA => array_keys($vrednost),
            SemaUvoza::TIP_OBLASTI_RADA => $vrednost,
            default => [$vrednost],
        };

        $nepostojeci = array_values(array_filter(
            $zaProveru,
            static fn (int $id): bool => ! isset($idovi[$id]),
        ));

        if ($nepostojeci === []) {
            return null;
        }

        return count($nepostojeci) === 1
            ? 'ид ' . $nepostojeci[0] . ' не постоји у шифарнику.'
            : 'ид-ови ' . implode(', ', $nepostojeci) . ' не постоје у шифарнику.';
    }

    /**
     * Ucitava po jedan pluck('id') po sifarniku za CEO uvoz, ne po redu.
     *
     * @param  array<string, int>  $mapa
     */
    private function ucitajSifarnike(array $mapa): void
    {
        $this->postojeciIdovi = [];

        foreach (SemaUvoza::sifarnickaPolja() as $kljuc => $model) {
            if (! isset($mapa[$kljuc])) {
                continue;
            }

            $this->postojeciIdovi[$kljuc] = array_fill_keys(
                $model::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
                true,
            );
        }
    }

    /**
     * Da li zapis u bazi ima vrednost koju bi prazna mapirana celija obrisala.
     *
     * @param  array<string, mixed>  $def
     */
    /**
     * Jedna stavka izvestaja „sta se menja", ili null ako se polje ne menja.
     *
     * Za nov zapis nema sa cim da se poredi, pa se belezi svako popunjeno polje.
     *
     * @param  array<string, mixed>  $def
     * @return array{polje: string, staro: string, novo: string, praznjenje: bool}|null
     */
    private function izmenaPolja(?PodaciORadnomMestu $zapis, string $kljuc, array $def, mixed $novaVrednost): ?array
    {
        $novoZaPoredjenje = $this->uporedivo($novaVrednost, $def);

        if ($zapis === null) {
            return $novoZaPoredjenje === ''
                ? null
                : [
                    'polje' => $def['labela'],
                    'staro' => '',
                    'novo' => $this->zaPrikaz($novaVrednost, $def),
                    'praznjenje' => false,
                ];
        }

        $staraVrednost = $this->trenutnaVrednost($zapis, $kljuc, $def);
        $staroZaPoredjenje = $this->uporedivo($staraVrednost, $def);

        if ($staroZaPoredjenje === $novoZaPoredjenje) {
            return null;
        }

        return [
            'polje' => $def['labela'],
            'staro' => $this->zaPrikaz($staraVrednost, $def),
            'novo' => $this->zaPrikaz($novaVrednost, $def),
            // Praznjenje je samo brisanje vrednosti. NOT NULL logicke kolone ne mogu
            // da se isprazne - one padnu na „Не", sto se u izvestaju vidi kao obicna izmena.
            'praznjenje' => $novoZaPoredjenje === '' && $staroZaPoredjenje !== '',
        ];
    }

    /** Trenutno stanje polja u bazi, u istom obliku u kom stize iz datoteke. */
    private function trenutnaVrednost(PodaciORadnomMestu $zapis, string $kljuc, array $def): mixed
    {
        return match ($def['tip']) {
            SemaUvoza::TIP_MESTA_RADA => $zapis->mestaRada
                ->mapWithKeys(fn ($grad): array => [(int) $grad->id => (int) ($grad->pivot->broj_izvrsilaca ?: 1)])
                ->all(),
            SemaUvoza::TIP_OBLASTI_RADA => $zapis->oblastiRada->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            default => $zapis->{$kljuc},
        };
    }

    /**
     * Kanonski oblik za POREDJENJE. Bez ovoga bi izvestaj prijavljivao izmenu na svakom
     * polju: baza vraca datum kao „2025-01-16" a datoteka „16.01.2025", decimalu kao
     * „34.50" naspram 34.5, logicku kao 1 naspram true.
     *
     * @param  array<string, mixed>  $def
     */
    private function uporedivo(mixed $vrednost, array $def): string
    {
        if ($vrednost === null || $vrednost === '' || $vrednost === []) {
            return '';
        }

        return match ($def['tip']) {
            SemaUvoza::TIP_DATUM => $this->uDatum($vrednost) ?? '',
            SemaUvoza::TIP_LOGICKA => $vrednost ? '1' : '0',
            SemaUvoza::TIP_DECIMAL => number_format((float) $vrednost, 2, '.', ''),
            SemaUvoza::TIP_CEO_BROJ, SemaUvoza::TIP_SIFARNIK => (string) (int) $vrednost,
            SemaUvoza::TIP_MESTA_RADA => $this->mestaUNisku((array) $vrednost),
            SemaUvoza::TIP_OBLASTI_RADA => $this->oblastiUNisku((array) $vrednost),
            default => trim((string) $vrednost),
        };
    }

    /** Citljiv oblik za izvestaj - ono sto korisnik vidi u tabeli i u .xlsx-u. */
    private function zaPrikaz(mixed $vrednost, array $def): string
    {
        if ($vrednost === null || $vrednost === '' || $vrednost === []) {
            return '';
        }

        return match ($def['tip']) {
            SemaUvoza::TIP_DATUM => ($d = $this->uDatum($vrednost)) === null
                ? (string) $vrednost
                : CarbonImmutable::parse($d)->format('d.m.Y'),
            SemaUvoza::TIP_LOGICKA => $vrednost ? 'Да' : 'Не',
            SemaUvoza::TIP_DECIMAL => str_replace('.', ',', number_format((float) $vrednost, 2, '.', '')),
            SemaUvoza::TIP_MESTA_RADA => $this->mestaUNisku((array) $vrednost),
            SemaUvoza::TIP_OBLASTI_RADA => $this->oblastiUNisku((array) $vrednost),
            default => trim((string) $vrednost),
        };
    }

    private function uDatum(mixed $vrednost): ?string
    {
        if ($vrednost instanceof DateTimeInterface) {
            return CarbonImmutable::instance($vrednost)->format('Y-m-d');
        }

        try {
            return CarbonImmutable::parse((string) $vrednost)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<int, int> $mesta ид града => број извршилаца */
    private function mestaUNisku(array $mesta): string
    {
        ksort($mesta);

        $delovi = [];

        foreach ($mesta as $idGrada => $broj) {
            $delovi[] = $idGrada . ':' . $broj;
        }

        return implode(', ', $delovi);
    }

    /** @param array<int, int> $oblasti */
    private function oblastiUNisku(array $oblasti): string
    {
        $oblasti = array_map(static fn ($id): int => (int) $id, array_values($oblasti));
        sort($oblasti);

        return implode(', ', $oblasti);
    }

    /**
     * @param  array<string, mixed>  $vrednosti
     * @param  array<string, int>  $mapa
     * @return array<int, string>
     */
    private function upozorenjaZaVeze(array $vrednosti, array $mapa, ?PodaciORadnomMestu $postojeci): array
    {
        $upozorenja = [];

        // Zbir izvrsilaca po gradovima naspram kolone "Број извршилаца". Forma to zahteva
        // (PodaciORadnomMestuResource.php:668-685), uvoz samo upozorava - bez poslovnih provera.
        $mesta = $vrednosti['mesta_rada'] ?? null;

        if (is_array($mesta) && $mesta !== []) {
            $zbir = array_sum($mesta);
            $ukupno = $vrednosti['broj_izvrsilaca'] ?? $postojeci?->broj_izvrsilaca;

            if ($ukupno !== null && (int) $ukupno !== $zbir) {
                $upozorenja[] = 'Збир извршилаца по градовима је ' . $zbir . ', а „Број извршилаца" је '
                    . (int) $ukupno . '. Запис ће се сачувати, али се неће моћи сачувати из форме док се не усагласи.';
            }
        }

        // Nov zapis bez mesta rada se ne moze sacuvati iz forme - repeater je required.
        if ($postojeci === null && ! isset($mapa['mesta_rada'])) {
            $upozorenja[] = 'Колона „Место рада" није мапирана, па нов запис остаје без места рада '
                . 'и неће се моћи сачувати из форме док се не допуни ручно.';
        }

        return $upozorenja;
    }

    /**
     * Model tiho prepisuje ishod_konkursa pri upisu (PodaciORadnomMestu::booted():194-204).
     * Ako to ne najavimo, korisnik prijavljuje gresku.
     *
     * @param  array<string, mixed>  $vrednosti
     * @return array<int, string>
     */
    private function upozorenjaZaIzvedenaPolja(array $vrednosti, ?PodaciORadnomMestu $postojeci): array
    {
        $imaResenje = ! empty($vrednosti['datum_donosenja_resenja_o_pokretanju_postupka']
            ?? $postojeci?->datum_donosenja_resenja_o_pokretanju_postupka);
        $imaDatumIshoda = ! empty($vrednosti['datum_ishoda_konkursa']
            ?? $postojeci?->datum_ishoda_konkursa);

        $trenutni = $postojeci?->ishod_konkursa === null ? null : (int) $postojeci->ishod_konkursa;

        if (in_array($trenutni, self::TERMINALNI_ISHODI, true) && $imaDatumIshoda) {
            return [];
        }

        $izvedeni = $imaResenje ? 4 : null;

        if ($izvedeni === $trenutni) {
            return [];
        }

        return [$izvedeni === 4
            ? 'Исход конкурса ће при упису бити постављен на „У току" (изводи се из датума решења о покретању поступка).'
            : 'Исход конкурса ће при упису бити испражњен (нема датума решења о покретању поступка ни датираног терминалног исхода).',
        ];
    }

    /** Jedan red -> jedan zapis. Poziva se samo unutar transakcije. */
    private function upisiRed(PodaciORadnomMestu $zapis, RedUvoza $red): void
    {
        $zaUpis = [];

        foreach ($red->vrednosti as $kljuc => $vrednost) {
            $def = SemaUvoza::definicija($kljuc);

            if ($def === null || in_array($def['tip'], [SemaUvoza::TIP_MESTA_RADA, SemaUvoza::TIP_OBLASTI_RADA], true)) {
                continue;
            }

            // NOT NULL boolean kolone: prazna celija je false, ne null - MySQL u strogom
            // rezimu bi na null bacio izuzetak i srusio celu transakciju.
            if ($vrednost === null && in_array($kljuc, SemaUvoza::NOT_NULL_LOGICKE, true)) {
                $vrednost = false;
            }

            $zaUpis[$kljuc] = $vrednost;
        }

        $zapis->fill($zaUpis)->save();

        // sync() se zove samo ako je kolona mapirana; prazna celija daje sync([]) -
        // sve veze se brisu, dosledno pravilu za obicna polja.
        if (array_key_exists('mesta_rada', $red->vrednosti)) {
            $zapis->mestaRada()->sync($this->pivotMestaRada($red->vrednosti['mesta_rada'] ?? []));
        }

        if (array_key_exists('oblasti_rada', $red->vrednosti)) {
            $zapis->oblastiRada()->sync($red->vrednosti['oblasti_rada'] ?? []);
        }
    }

    /**
     * Pivot nosi i region/oblast/kod_grada - popunjavaju se iz sifarnika gradova,
     * ne iz fajla, isto kao sto to radi repeater u formi.
     *
     * @param  array<int, int>|null  $mesta
     * @return array<int, array<string, mixed>>
     */
    private function pivotMestaRada(?array $mesta): array
    {
        if ($mesta === null || $mesta === []) {
            return [];
        }

        $gradovi = SifarnikKodoviGradova::query()
            ->whereIn('id', array_keys($mesta))
            ->get()
            ->keyBy('id');

        $pivot = [];

        foreach ($mesta as $idGrada => $brojIzvrsilaca) {
            $grad = $gradovi->get($idGrada);

            $pivot[$idGrada] = [
                'broj_izvrsilaca' => $brojIzvrsilaca,
                'region' => $grad?->region,
                'oblast' => $grad?->oblast,
                'kod_grada' => $grad?->kod_grada,
            ];
        }

        return $pivot;
    }
}
