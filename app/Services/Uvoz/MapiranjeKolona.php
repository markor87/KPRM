<?php

namespace App\Services\Uvoz;

use App\Models\Setting;

/**
 * Povezuje zaglavlja iz fajla sa poljima iz SemaUvoza.
 *
 * Mapa je uvek oblika [kljuc_polja => indeks kolone u fajlu]. Polje kojeg nema u mapi
 * je nemapirano i uvoz ga uopste ne dira.
 */
class MapiranjeKolona
{
    public const KLJUC_PODESAVANJA = 'uvoz_mapiranje_kolona';

    /**
     * Latinicno-cirilicni homoglifi. Isti oblik slova, razlicit kod - endemski su u
     * srpskim kancelarijskim dokumentima, pa bez ovoga "Орган" iz fajla ne pogadja
     * "Орган" iz seme iako izgledaju identicno.
     *
     * Oba slucaja svakog slova moraju biti u mapi i voditi na isto cirilicno slovo:
     * mapiranje se radi PRE spustanja u mala slova, pa bi "H" -> "Н" bez "h" -> "н"
     * znacilo da se "HOFK" i "hofk" razidju.
     */
    private const HOMOGLIFI = [
        'A' => 'А', 'a' => 'а',
        'B' => 'В', 'b' => 'в',
        'C' => 'С', 'c' => 'с',
        'E' => 'Е', 'e' => 'е',
        'H' => 'Н', 'h' => 'н',
        'J' => 'Ј', 'j' => 'ј',
        'K' => 'К', 'k' => 'к',
        'M' => 'М', 'm' => 'м',
        'O' => 'О', 'o' => 'о',
        'P' => 'Р', 'p' => 'р',
        'S' => 'Ѕ', 's' => 'ѕ',
        'T' => 'Т', 't' => 'т',
        'X' => 'Х', 'x' => 'х',
        'Y' => 'У', 'y' => 'у',
    ];

    /**
     * Automatski mapira sto vise moze. Za svako zaglavlje iz fajla trazi polje ciji se
     * labela, sinonim ili ime kolone u bazi poklapaju posle normalizacije.
     *
     * @param  array<int, string>  $zaglavlje
     * @return array{mapa: array<string, int>, automatski: array<string>}
     */
    public function automatski(array $zaglavlje): array
    {
        $recnik = $this->recnikPolja();
        $mapa = [];

        foreach ($zaglavlje as $indeks => $naziv) {
            $normalizovano = $this->normalizuj($naziv);

            if ($normalizovano === '' || ! isset($recnik[$normalizovano])) {
                continue;
            }

            $kljuc = $recnik[$normalizovano];

            // Jedno polje sme biti mapirano samo jednom - prvi pogodak pobedjuje.
            if (isset($mapa[$kljuc])) {
                continue;
            }

            $mapa[$kljuc] = $indeks;
        }

        return ['mapa' => $mapa, 'automatski' => array_keys($mapa)];
    }

    /**
     * Dopunjuje automatsku mapu zapamcenim izborom iz proslog uvoza, ali samo za polja
     * koja automatika nije pogodila i cija kolona jos postoji u fajlu.
     *
     * @param  array<string, int>  $mapa
     * @param  array<int, string>  $zaglavlje
     * @return array<string, int>
     */
    public function dopuniZapamcenim(array $mapa, array $zaglavlje): array
    {
        $zapamceno = $this->zapamcenoMapiranje();

        if ($zapamceno === []) {
            return $mapa;
        }

        $polja = SemaUvoza::polja();
        $zauzeti = array_flip($mapa);

        foreach ($zapamceno as $kljuc => $nazivKolone) {
            if (isset($mapa[$kljuc]) || ! isset($polja[$kljuc])) {
                continue;
            }

            $indeks = array_search($nazivKolone, $zaglavlje, true);

            if ($indeks === false || isset($zauzeti[$indeks])) {
                continue;
            }

            $mapa[$kljuc] = $indeks;
            $zauzeti[$indeks] = $kljuc;
        }

        return $mapa;
    }

    /**
     * Pamti mapiranje po NAZIVU kolone, ne po indeksu - sledeci fajl moze imati kolone
     * u drugom redosledu, a nazivi ostaju isti.
     *
     * @param  array<string, int>  $mapa
     * @param  array<int, string>  $zaglavlje
     */
    public function zapamti(array $mapa, array $zaglavlje): void
    {
        $poNazivu = [];

        foreach ($mapa as $kljuc => $indeks) {
            if (isset($zaglavlje[$indeks])) {
                $poNazivu[$kljuc] = $zaglavlje[$indeks];
            }
        }

        Setting::set(self::KLJUC_PODESAVANJA, json_encode($poNazivu, JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, string> kljuc polja => naziv kolone u fajlu */
    public function zapamcenoMapiranje(): array
    {
        $sirovo = Setting::get(self::KLJUC_PODESAVANJA);

        if (! is_string($sirovo) || $sirovo === '') {
            return [];
        }

        $dekodirano = json_decode($sirovo, true);

        return is_array($dekodirano) ? $dekodirano : [];
    }

    /**
     * Recnik svih prihvacenih zaglavlja -> kljuc polja.
     * Labela se uvek prihvata, uz nju sinonimi i samo ime kolone u bazi.
     *
     * @return array<string, string>
     */
    private function recnikPolja(): array
    {
        $recnik = [];

        foreach (SemaUvoza::polja() as $kljuc => $def) {
            $varijante = array_merge(
                [$def['labela'], $kljuc],
                $def['sinonimi'] ?? [],
            );

            foreach ($varijante as $varijanta) {
                $normalizovano = $this->normalizuj($varijanta);

                // Prva definicija pobedjuje - sema je uredjena, pa je to ona ocekivana.
                if ($normalizovano !== '' && ! isset($recnik[$normalizovano])) {
                    $recnik[$normalizovano] = $kljuc;
                }
            }
        }

        return $recnik;
    }

    /**
     * Obe strane poredjenja prolaze kroz isto: mala slova, sazeti razmaci, bez zavrsne
     * tacke i dvotacke, latinicni homoglifi prevedeni u cirilicne.
     */
    public function normalizuj(string $tekst): string
    {
        $tekst = NormalizatorVrednosti::ocisti($tekst);
        $tekst = rtrim($tekst, " .:\u{00A0}");
        $tekst = strtr($tekst, self::HOMOGLIFI);

        return mb_strtolower($tekst);
    }
}
