<?php

namespace App\Exports;

use App\Services\Uvoz\IzvestajUvoza;
use App\Services\Uvoz\RedUvoza;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Ceo izvestaj probnog uvoza u .xlsx, u dva lista:
 *   „Редови"  — sta se desava sa svakim redom datoteke
 *   „Измене"  — polje po polje, stara vrednost pored nove
 *
 * Na ekranu se prikazuje najvise 200 redova; ovde ide sve.
 *
 * WithStrictNullComparison je obavezan: bez njega Maatwebsite nulu upisuje kao praznu
 * celiju, pa „0 izmenjenih polja" izgleda kao da podatak nedostaje.
 */
class IzvestajUvozaExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly IzvestajUvoza $izvestaj)
    {
    }

    public function sheets(): array
    {
        return [
            new IzvestajUvozaRedoviSheet($this->izvestaj),
            new IzvestajUvozaIzmeneSheet($this->izvestaj),
        ];
    }
}

/** Prvi list: po jedan red za svaki red datoteke. */
class IzvestajUvozaRedoviSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithTitle
{
    public function __construct(private readonly IzvestajUvoza $izvestaj)
    {
    }

    public function title(): string
    {
        return 'Редови';
    }

    public function headings(): array
    {
        return [
            'Ред у датотеци',
            'Исход',
            'ID записа',
            'Измењених поља',
            'Грешке',
            'Упозорења',
            'Поља која ће бити испражњена',
        ];
    }

    public function array(): array
    {
        return array_map(static fn (RedUvoza $red): array => [
            $red->brojReda,
            $red->opisIshoda(),
            $red->idZapisa,
            $red->jeUpotrebljiv() ? count($red->izmene) : '',
            implode(' | ', $red->greske),
            implode(' | ', $red->upozorenja),
            implode(', ', $red->poljaZaPraznjenje()),
        ], $this->izvestaj->redovi);
    }
}

/** Drugi list: jedan red po polju koje se menja - stara vrednost pored nove. */
class IzvestajUvozaIzmeneSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithTitle
{
    public function __construct(private readonly IzvestajUvoza $izvestaj)
    {
    }

    public function title(): string
    {
        return 'Измене';
    }

    public function headings(): array
    {
        return [
            'Ред у датотеци',
            'Врста',
            'Запис',
            'Поље',
            'Стара вредност',
            'Нова вредност',
            'Празни се',
        ];
    }

    public function array(): array
    {
        return array_map(static fn (array $iz): array => [
            $iz['broj_reda'],
            $iz['ishod'],
            $iz['zapis'],
            $iz['polje'],
            $iz['staro'] === '' ? '(празно)' : $iz['staro'],
            $iz['novo'] === '' ? '(празно)' : $iz['novo'],
            $iz['praznjenje'] ? 'ДА' : '',
        ], $this->izvestaj->izmene());
    }
}
