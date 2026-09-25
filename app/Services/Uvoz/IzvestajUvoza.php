<?php

namespace App\Services\Uvoz;

/**
 * Rezultat probnog uvoza: sta bi se desilo da se pritisne "Покрени увоз".
 */
class IzvestajUvoza
{
    /** Koliko redova sa greskom ili upozorenjem prikazujemo na ekranu. */
    public const MAX_NA_EKRANU = 200;

    /** @param array<int, RedUvoza> $redovi */
    public function __construct(
        public readonly array $redovi,
        public readonly string $rezim,
    ) {
    }

    public function brojNovih(): int
    {
        return $this->prebroj(RedUvoza::ISHOD_NOV);
    }

    public function brojAzuriranih(): int
    {
        return $this->prebroj(RedUvoza::ISHOD_AZURIRANJE);
    }

    public function brojPreskocenih(): int
    {
        return $this->prebroj(RedUvoza::ISHOD_PRESKOCEN);
    }

    public function brojGresaka(): int
    {
        return $this->prebroj(RedUvoza::ISHOD_GRESKA);
    }

    /** Koliko ce popunjenih polja biti obrisano jer je мапирана ћелија празна. */
    public function brojPraznjenja(): int
    {
        $ukupno = 0;

        foreach ($this->redovi as $red) {
            $ukupno += count($red->poljaZaPraznjenje());
        }

        return $ukupno;
    }

    /** Koliko se polja stvarno menja na postojecim zapisima. */
    public function brojIzmenjenihPolja(): int
    {
        $ukupno = 0;

        foreach ($this->redovi as $red) {
            if ($red->ishod === RedUvoza::ISHOD_AZURIRANJE) {
                $ukupno += count($red->izmene);
            }
        }

        return $ukupno;
    }

    /**
     * Ravan spisak „sta se menja" - jedan red po polju.
     *
     * @param  bool  $samoAzuriranja  na ekranu prikazujemo samo izmene postojecih zapisa;
     *         nov zapis bi dao 80 redova i udavio ono sto se stvarno menja. U .xlsx-u ide sve.
     * @return array<int, array{broj_reda: int, ishod: string, zapis: string, polje: string, staro: string, novo: string, praznjenje: bool}>
     */
    public function izmene(bool $samoAzuriranja = false): array
    {
        $izlaz = [];

        foreach ($this->redovi as $red) {
            if (! $red->jeUpotrebljiv()) {
                continue;
            }

            if ($samoAzuriranja && $red->ishod !== RedUvoza::ISHOD_AZURIRANJE) {
                continue;
            }

            foreach ($red->izmene as $iz) {
                $izlaz[] = [
                    'broj_reda' => $red->brojReda,
                    'ishod' => $red->ishod === RedUvoza::ISHOD_AZURIRANJE ? 'Ажурирање' : 'Нов запис',
                    'zapis' => $red->idZapisa === null ? '' : '#' . $red->idZapisa,
                    'polje' => $iz['polje'],
                    'staro' => $iz['staro'],
                    'novo' => $iz['novo'],
                    'praznjenje' => $iz['praznjenje'],
                ];
            }
        }

        return $izlaz;
    }

    /** Koliko polja se popunjava na svakom novom zapisu - za sazetak na ekranu. */
    public function noviZapisiSaBrojemPolja(): array
    {
        $izlaz = [];

        foreach ($this->redovi as $red) {
            if ($red->ishod === RedUvoza::ISHOD_NOV) {
                $izlaz[] = ['broj_reda' => $red->brojReda, 'polja' => count($red->izmene)];
            }
        }

        return $izlaz;
    }

    public function imaUpotrebljivih(): bool
    {
        foreach ($this->redovi as $red) {
            if ($red->jeUpotrebljiv()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Uvoz se pusta samo kada nema nijedne greske - delimican uvoz bi ostavio
     * korisnika da rucno trazi sta je proslo a sta nije.
     */
    public function smeSeUvesti(): bool
    {
        return $this->brojGresaka() === 0 && $this->imaUpotrebljivih();
    }

    /** @return array<int, RedUvoza> redovi koje vredi prikazati - greske pa upozorenja */
    public function redoviZaPrikaz(): array
    {
        $zanimljivi = array_filter(
            $this->redovi,
            static fn (RedUvoza $red): bool => $red->greske !== [] || $red->upozorenja !== [],
        );

        usort($zanimljivi, static function (RedUvoza $a, RedUvoza $b): int {
            $prioritet = static fn (RedUvoza $red): int => $red->greske !== [] ? 0 : 1;

            return [$prioritet($a), $a->brojReda] <=> [$prioritet($b), $b->brojReda];
        });

        return array_slice($zanimljivi, 0, self::MAX_NA_EKRANU);
    }

    public function brojZanimljivih(): int
    {
        return count(array_filter(
            $this->redovi,
            static fn (RedUvoza $red): bool => $red->greske !== [] || $red->upozorenja !== [],
        ));
    }

    private function prebroj(string $ishod): int
    {
        return count(array_filter(
            $this->redovi,
            static fn (RedUvoza $red): bool => $red->ishod === $ishod,
        ));
    }
}
