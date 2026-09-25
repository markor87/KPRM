<?php

namespace App\Services\Uvoz;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Pretvara sirovu vrednost iz celije u kanonski oblik koji baza ocekuje.
 *
 * Svaka metoda vraca ili vrednost ili baca GreskaVrednosti sa porukom na srpskom -
 * ta poruka ide pravo u izvestaj probnog uvoza, uz broj reda i naziv kolone.
 */
class NormalizatorVrednosti
{
    /** Datumski formati koje prihvatamo, redom kojim se probaju. */
    private const FORMATI_DATUMA = ['Y-m-d', 'd.m.Y', 'd.m.Y.', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'];

    /**
     * Prazno? Prazan string, sam razmak (i tvrdi razmak) i null se broje kao prazno.
     * Nula i "0" NISU prazno.
     */
    public static function jePrazno(mixed $vrednost): bool
    {
        if ($vrednost === null) {
            return true;
        }

        if (is_string($vrednost)) {
            return self::ocisti($vrednost) === '';
        }

        return false;
    }

    /**
     * Skida razmake sa oba kraja i sazima unutrasnje.
     *
     * Za ZAGLAVLJA i za poredjenje — tamo je sazimanje pozeljno, jer se
     * „Датум  оглашавања" i „Датум оглашавања" moraju poklopiti.
     */
    public static function ocisti(string $tekst): string
    {
        return trim(preg_replace('/\s+/u', ' ', self::obicniRazmaci($tekst)) ?? '');
    }

    /**
     * Blaza obrada, za SADRZAJ tekstualnih celija: tvrdi razmaci postaju obicni i
     * krajevi se skracuju, ali se unutrasnji razmaci NE diraju.
     *
     * Bez ove razlike uvoz bi tiho prepravljao nazive radnih mesta koji u bazi imaju
     * dvostruki razmak — datoteka izvezena pa vracena nepromenjena prijavila bi
     * stotine „izmena" i zaista ih upisala.
     */
    public static function ocistiSadrzaj(string $tekst): string
    {
        return trim(self::obicniRazmaci($tekst));
    }

    private static function obicniRazmaci(string $tekst): string
    {
        return str_replace(["\xC2\xA0", "\xE2\x80\xAF"], ' ', $tekst);
    }

    /**
     * @throws GreskaVrednosti
     */
    public static function tekst(mixed $vrednost, ?int $maxDuzina = null): ?string
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        $tekst = is_string($vrednost) ? self::ocistiSadrzaj($vrednost) : (string) $vrednost;

        if ($maxDuzina !== null && mb_strlen($tekst) > $maxDuzina) {
            throw new GreskaVrednosti("Текст је дужи од {$maxDuzina} знакова (има " . mb_strlen($tekst) . ').');
        }

        return $tekst;
    }

    /**
     * Ceo broj. Prihvata "1.234", "1 234", "1234,00" (ako je decimalni deo nula).
     *
     * @throws GreskaVrednosti
     */
    public static function ceoBroj(mixed $vrednost, ?float $min = null, ?float $max = null): ?int
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        if (is_int($vrednost)) {
            $broj = $vrednost;
        } elseif (is_float($vrednost)) {
            if (fmod($vrednost, 1.0) !== 0.0) {
                throw new GreskaVrednosti('Очекује се цео број, а вредност има децимале: ' . $vrednost);
            }
            $broj = (int) $vrednost;
        } else {
            $tekst = self::ocisti((string) $vrednost);
            // Grupisanje hiljada: "1 234" ili "1.234" -> "1234". Zarez tretiramo kao decimalni.
            $tekst = str_replace(' ', '', $tekst);
            $tekst = str_replace(',', '.', $tekst);

            // "1.234" je hiljada, "1.5" je decimala - razlikujemo po broju cifara posle tacke.
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $tekst) === 1) {
                $tekst = str_replace('.', '', $tekst);
            }

            if (! is_numeric($tekst)) {
                throw new GreskaVrednosti('Није број: „' . self::ocisti((string) $vrednost) . '".');
            }

            $broj = (float) $tekst;

            if (fmod($broj, 1.0) !== 0.0) {
                throw new GreskaVrednosti('Очекује се цео број, а вредност има децимале: ' . self::ocisti((string) $vrednost));
            }

            $broj = (int) $broj;
        }

        self::proveriOpseg($broj, $min, $max);

        return $broj;
    }

    /**
     * Decimalni broj, zaokruzen na dve decimale (kolone su decimal(5,2)).
     *
     * @throws GreskaVrednosti
     */
    public static function decimal(mixed $vrednost, ?float $min = null, ?float $max = null): ?float
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        if (is_int($vrednost) || is_float($vrednost)) {
            $broj = (float) $vrednost;
        } else {
            $tekst = self::ocisti((string) $vrednost);
            $tekst = str_replace([' ', '%'], '', $tekst);
            $tekst = str_replace(',', '.', $tekst);

            if (! is_numeric($tekst)) {
                throw new GreskaVrednosti('Није број: „' . self::ocisti((string) $vrednost) . '".');
            }

            $broj = (float) $tekst;
        }

        $broj = round($broj, 2);

        self::proveriOpseg($broj, $min, $max);

        return $broj;
    }

    /**
     * Logicka vrednost. Prazno vraca null - pozivalac odlucuje da li to znaci false
     * (NOT NULL kolone, vidi SemaUvoza::NOT_NULL_LOGICKE) ili brisanje vrednosti.
     *
     * @throws GreskaVrednosti
     */
    public static function logicka(mixed $vrednost): ?bool
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        if (is_bool($vrednost)) {
            return $vrednost;
        }

        if (is_int($vrednost) || is_float($vrednost)) {
            return (bool) $vrednost;
        }

        $tekst = mb_strtolower(self::ocisti((string) $vrednost));

        $tacno = ['1', 'da', 'да', 'true', 'tacno', 'тачно', 'yes', 'x', 'ч'];
        $netacno = ['0', 'ne', 'не', 'false', 'netacno', 'нетачно', 'no'];

        if (in_array($tekst, $tacno, true)) {
            return true;
        }

        if (in_array($tekst, $netacno, true)) {
            return false;
        }

        throw new GreskaVrednosti('Није логичка вредност: „' . self::ocisti((string) $vrednost) . '". Дозвољено: 1/0, да/не.');
    }

    /**
     * Datum u obliku Y-m-d.
     *
     * Excel serijski broj se tumaci SAMO ovde - zato je tip kolone iz seme obavezan
     * pre poziva, da broj kandidata 45000 ne postane datum.
     *
     * @throws GreskaVrednosti
     */
    public static function datum(mixed $vrednost): ?string
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        if ($vrednost instanceof DateTimeInterface) {
            return CarbonImmutable::instance($vrednost)->format('Y-m-d');
        }

        // Excel cuva datum kao broj dana od 1900-01-01. Ogranicavamo na razuman opseg
        // (1970-2100) da se slucajna petocifrena vrednost ne protumaci kao datum.
        if (is_int($vrednost) || is_float($vrednost)) {
            if ($vrednost < 25569 || $vrednost > 73415) {
                throw new GreskaVrednosti('Број ' . $vrednost . ' није употребљив датум.');
            }

            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($vrednost))->format('Y-m-d');
        }

        $tekst = self::ocisti((string) $vrednost);
        $tekst = preg_replace('/\s*\.\s*/u', '.', $tekst) ?? $tekst;

        foreach (self::FORMATI_DATUMA as $format) {
            try {
                $datum = CarbonImmutable::createFromFormat($format, $tekst);
            } catch (InvalidFormatException) {
                // Ulaz ne odgovara ovom obliku - probamo sledeci.
                continue;
            }

            // createFromFormat prima i nepostojece datume (31.02.2026 -> 03.03.2026),
            // zato uporedjujemo natrag sa ulazom.
            if ($datum !== false && $datum->format($format) === $tekst) {
                return $datum->format('Y-m-d');
            }
        }

        throw new GreskaVrednosti('Није датум: „' . $tekst . '". Прихваћени облици: 31.12.2026 или 2026-12-31.');
    }

    /**
     * Mesta rada: "12:3, 45:1" -> [12 => 3, 45 => 1] (ид града => број извршилаца).
     * Prihvata i "12-3" i "12 x 3" kao razdvajac.
     *
     * @return array<int, int>|null
     *
     * @throws GreskaVrednosti
     */
    public static function mestaRada(mixed $vrednost): ?array
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        $izlaz = [];

        foreach (self::naDelove((string) $vrednost) as $deo) {
            if (preg_match('/^(\d+)\s*[:\-x×]\s*(\d+)$/ui', $deo, $poklapanja) !== 1) {
                throw new GreskaVrednosti(
                    'Неисправан облик „' . $deo . '". Очекује се „ид града:број извршилаца", нпр. „12:3, 45:1".'
                );
            }

            $idGrada = (int) $poklapanja[1];
            $brojIzvrsilaca = (int) $poklapanja[2];

            if (isset($izlaz[$idGrada])) {
                throw new GreskaVrednosti('Град са ид-ом ' . $idGrada . ' наведен је више пута.');
            }

            if ($brojIzvrsilaca < 1) {
                throw new GreskaVrednosti('Број извршилаца за град ' . $idGrada . ' мора бити бар 1.');
            }

            $izlaz[$idGrada] = $brojIzvrsilaca;
        }

        return $izlaz === [] ? null : $izlaz;
    }

    /**
     * Oblasti rada: "3, 7" -> [3, 7].
     *
     * @return array<int>|null
     *
     * @throws GreskaVrednosti
     */
    public static function oblastiRada(mixed $vrednost): ?array
    {
        if (self::jePrazno($vrednost)) {
            return null;
        }

        $izlaz = [];

        foreach (self::naDelove((string) $vrednost) as $deo) {
            if (preg_match('/^\d+$/', $deo) !== 1) {
                throw new GreskaVrednosti('Неисправан ид области „' . $deo . '". Очекују се бројеви, нпр. „3, 7".');
            }

            $izlaz[] = (int) $deo;
        }

        return $izlaz === [] ? null : array_values(array_unique($izlaz));
    }

    /**
     * Sifarnicki id - ceo pozitivan broj. Postojanje se proverava drugde,
     * prema unapred ucitanoj listi id-ova.
     *
     * @throws GreskaVrednosti
     */
    public static function sifarnik(mixed $vrednost): ?int
    {
        $id = self::ceoBroj($vrednost);

        if ($id !== null && $id < 1) {
            throw new GreskaVrednosti('Ид шифарника мора бити позитиван број, а добијено је ' . $id . '.');
        }

        return $id;
    }

    /** @return array<string> */
    private static function naDelove(string $vrednost): array
    {
        $delovi = preg_split('/[,;\n\r]+/u', self::ocisti($vrednost)) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $deo): string => self::ocisti($deo), $delovi),
            static fn (string $deo): bool => $deo !== '',
        ));
    }

    /**
     * @throws GreskaVrednosti
     */
    private static function proveriOpseg(int|float $broj, ?float $min, ?float $max): void
    {
        if ($min !== null && $broj < $min) {
            throw new GreskaVrednosti('Вредност ' . $broj . ' је мања од дозвољеног минимума ' . $min . '.');
        }

        if ($max !== null && $broj > $max) {
            throw new GreskaVrednosti('Вредност ' . $broj . ' је већа од дозвољеног максимума ' . $max . '.');
        }
    }
}
