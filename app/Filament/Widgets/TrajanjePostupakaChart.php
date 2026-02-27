<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TrajanjePostupakaChart extends ApexChartWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 12;

    protected function getHeading(): ?string
    {
        return 'Трајање јавних конкурсних и изборних поступака (' . (now()->year - 1) . ')';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', 1);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $podaci = $baseQuery->get();

        $javniDurations = [];
        $izborniDurations = [];

        foreach ($podaci as $konkurs) {
            if ($konkurs->datum_donosenja_resenja_o_pokretanju_postupka && $konkurs->datum_stupanja_na_rad) {
                $javniDurations[] = Carbon::parse($konkurs->datum_donosenja_resenja_o_pokretanju_postupka)
                    ->diffInDays(Carbon::parse($konkurs->datum_stupanja_na_rad));
            }

            if ($konkurs->datum_pregleda_prijava && $konkurs->datum_dostavljanja_liste_rukovodiocu_organa) {
                $izborniDurations[] = Carbon::parse($konkurs->datum_pregleda_prijava)
                    ->diffInDays(Carbon::parse($konkurs->datum_dostavljanja_liste_rukovodiocu_organa));
            }
        }

        $javniMin = !empty($javniDurations) ? min($javniDurations) : 0;
        $javniAvg = !empty($javniDurations) ? round(array_sum($javniDurations) / count($javniDurations)) : 0;
        $javniMax = !empty($javniDurations) ? max($javniDurations) : 0;

        $izbornaMin = !empty($izborniDurations) ? min($izborniDurations) : 0;
        $izbornaAvg = !empty($izborniDurations) ? round(array_sum($izborniDurations) / count($izborniDurations)) : 0;
        $izbornaMax = !empty($izborniDurations) ? max($izborniDurations) : 0;

        return [
            'series' => [
                [
                    'name' => 'Конкурсни поступак',
                    'data' => [$javniMin, $javniAvg, $javniMax],
                ],
                [
                    'name' => 'Изборни поступак',
                    'data' => [$izbornaMin, $izbornaAvg, $izbornaMax],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '50%',
                    'borderRadius' => 4,
                    'dataLabels' => ['position' => 'top'],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'offsetY' => -20,
                'style' => [
                    'fontSize' => '13px',
                    'fontWeight' => 'bold',
                ],
            ],
            'xaxis' => [
                'categories' => ['Најкраћи', 'Просек', 'Најдужи'],
            ],
            'colors' => ['#dc2626', '#1d4ed8'],
            'grid' => [
                'strokeDashArray' => 3,
            ],
            'legend' => [
                'show' => true,
                'position' => 'top',
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' дана';
                    }
                }
            }
        }
        JS);
    }
}
