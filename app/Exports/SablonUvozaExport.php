<?php

namespace App\Exports;

use App\Services\Uvoz\SemaUvoza;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Prazan sablon sa tacnim zaglavljima koje uvoz prepoznaje bez ijednog rucnog mapiranja,
 * plus jedan red primera da se vidi ocekivani oblik celija za mesta i oblasti rada.
 */
class SablonUvozaExport implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Шаблон за увоз';
    }

    public function headings(): array
    {
        return array_values(SemaUvoza::labele());
    }

    public function array(): array
    {
        $primer = [];

        foreach (SemaUvoza::polja() as $kljuc => $def) {
            $primer[] = match ($def['tip']) {
                SemaUvoza::TIP_ID => '',
                SemaUvoza::TIP_DATUM => '31.12.2026',
                SemaUvoza::TIP_LOGICKA => 'не',
                SemaUvoza::TIP_SIFARNIK => '1',
                SemaUvoza::TIP_MESTA_RADA => '12:3, 45:1',
                SemaUvoza::TIP_OBLASTI_RADA => '3, 7',
                SemaUvoza::TIP_DECIMAL => '35,5',
                SemaUvoza::TIP_TEKST => 'пример назива радног места',
                default => '0',
            };
        }

        return [$primer];
    }
}
