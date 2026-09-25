<?php

namespace App\Services\Uvoz;

/**
 * Sta ce se desiti sa jednim redom iz fajla. Nastaje u analiziraj(), bez ijednog upisa.
 */
class RedUvoza
{
    public const ISHOD_NOV = 'nov';
    public const ISHOD_AZURIRANJE = 'azuriranje';
    public const ISHOD_PRESKOCEN = 'preskocen';
    public const ISHOD_GRESKA = 'greska';

    /**
     * @param  int  $brojReda  stvarni broj reda u datoteci (racunajuci zaglavlje)
     * @param  array<string, mixed>  $vrednosti  kljuc polja => normalizovana vrednost
     * @param  array<int, string>  $greske
     * @param  array<int, string>  $upozorenja
     * @param  array<int, array{polje: string, staro: string, novo: string, praznjenje: bool}>  $izmene
     *         polje po polje, sta je bilo i sta ce biti - racuna se poredjenjem sa bazom
     */
    public function __construct(
        public readonly int $brojReda,
        public readonly string $ishod,
        public readonly ?int $idZapisa,
        public readonly array $vrednosti = [],
        public readonly array $greske = [],
        public readonly array $upozorenja = [],
        public readonly array $izmene = [],
        public readonly ?string $razlogPreskakanja = null,
    ) {
    }

    public function jeUpotrebljiv(): bool
    {
        return $this->ishod === self::ISHOD_NOV || $this->ishod === self::ISHOD_AZURIRANJE;
    }

    /** Polja koja ce ostati prazna iako sada imaju vrednost. */
    public function poljaZaPraznjenje(): array
    {
        return array_values(array_map(
            static fn (array $iz): string => $iz['polje'],
            array_filter($this->izmene, static fn (array $iz): bool => $iz['praznjenje']),
        ));
    }

    public function opisIshoda(): string
    {
        return match ($this->ishod) {
            self::ISHOD_NOV => 'Нов запис',
            self::ISHOD_AZURIRANJE => 'Ажурирање записа #' . $this->idZapisa,
            self::ISHOD_PRESKOCEN => 'Прескочен — ' . ($this->razlogPreskakanja ?? 'не одговара изабраном режиму'),
            default => 'Грешка',
        };
    }
}
