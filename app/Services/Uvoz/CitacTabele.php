<?php

namespace App\Services\Uvoz;

use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Cita .xlsx i .csv u isti oblik: zaglavlje + redovi.
 *
 * WithHeadingRow se NE SME koristiti - vendor default za heading_row.formatter je 'slug',
 * a Str::slug() kroz Str::ascii() potpuno brise cirilicu, pa bi svih 78 zaglavlja postalo
 * prazan string i sudarilo se. Zato citamo sirove redove i mapiramo zaglavlje rucno.
 */
class CitacTabele
{
    /** Gornja granica - Excel::toArray() ucitava ceo fajl u memoriju, bez komadanja. */
    public const MAX_REDOVA = 2000;

    /**
     * @return array{zaglavlje: array<int, string>, redovi: array<int, array<int, mixed>>}
     */
    public function procitaj(string $putanja): array
    {
        if (! is_file($putanja)) {
            throw new RuntimeException('Датотека није пронађена: ' . $putanja);
        }

        $nastavak = mb_strtolower(pathinfo($putanja, PATHINFO_EXTENSION));

        $redovi = $nastavak === 'csv'
            ? $this->procitajCsv($putanja)
            : $this->procitajExcel($putanja);

        // Uklanjamo potpuno prazne redove sa kraja - Excel ih rado dopise.
        while ($redovi !== [] && $this->jePrazanRed(end($redovi))) {
            array_pop($redovi);
        }

        if ($redovi === []) {
            throw new RuntimeException('Датотека је празна.');
        }

        $zaglavlje = array_map(
            static fn (mixed $celija): string => NormalizatorVrednosti::ocisti((string) ($celija ?? '')),
            array_shift($redovi),
        );

        if (implode('', $zaglavlje) === '') {
            throw new RuntimeException('Први ред датотеке мора садржати називе колона.');
        }

        // Preskacemo prazne redove u sredini, ali pamtimo stvarni broj reda u fajlu
        // (+2: jedan za zaglavlje, jedan jer Excel broji od 1) radi poruka o greskama.
        $ociscceni = [];

        foreach ($redovi as $indeks => $red) {
            if ($this->jePrazanRed($red)) {
                continue;
            }

            $ociscceni[$indeks + 2] = $red;
        }

        if (count($ociscceni) > self::MAX_REDOVA) {
            throw new RuntimeException(
                'Датотека има ' . count($ociscceni) . ' редова, а највише је дозвољено ' . self::MAX_REDOVA
                . '. Поделите је на више мањих датотека.'
            );
        }

        if ($ociscceni === []) {
            throw new RuntimeException('Датотека нема ниједан ред са подацима.');
        }

        return ['zaglavlje' => $zaglavlje, 'redovi' => $ociscceni];
    }

    /** @return array<int, array<int, mixed>> */
    private function procitajExcel(string $putanja): array
    {
        $listovi = Excel::toArray(new class {}, $putanja);

        return $listovi[0] ?? [];
    }

    /**
     * CSV citamo sami, jer moramo nanjusiti kodiranje i razdelnik.
     * Srpski Excel izvozi CSV sa `;`, ne `,` - to je najcesci uzrok neuspeha u praksi.
     *
     * @return array<int, array<int, mixed>>
     */
    private function procitajCsv(string $putanja): array
    {
        $sadrzaj = file_get_contents($putanja);

        if ($sadrzaj === false) {
            throw new RuntimeException('Датотека се не може прочитати.');
        }

        // BOM
        if (str_starts_with($sadrzaj, "\xEF\xBB\xBF")) {
            $sadrzaj = substr($sadrzaj, 3);
        }

        if (! mb_check_encoding($sadrzaj, 'UTF-8')) {
            $sadrzaj = mb_convert_encoding($sadrzaj, 'UTF-8', 'Windows-1251');
        }

        $razdelnik = $this->pogodiRazdelnik(strtok($sadrzaj, "\n") ?: '');

        $redovi = [];
        $tok = fopen('php://memory', 'r+');
        fwrite($tok, $sadrzaj);
        rewind($tok);

        while (($red = fgetcsv($tok, 0, $razdelnik, '"', '\\')) !== false) {
            $redovi[] = $red;
        }

        fclose($tok);

        return $redovi;
    }

    private function pogodiRazdelnik(string $redZaglavlja): string
    {
        $kandidati = [';' => 0, ',' => 0, "\t" => 0];

        foreach (array_keys($kandidati) as $znak) {
            $kandidati[$znak] = substr_count($redZaglavlja, $znak);
        }

        arsort($kandidati);

        $najcesci = array_key_first($kandidati);

        return $kandidati[$najcesci] > 0 ? $najcesci : ';';
    }

    /** @param array<int, mixed> $red */
    private function jePrazanRed(array $red): bool
    {
        foreach ($red as $celija) {
            if (! NormalizatorVrednosti::jePrazno($celija)) {
                return false;
            }
        }

        return true;
    }
}
