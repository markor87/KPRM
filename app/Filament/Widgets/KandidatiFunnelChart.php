<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KandidatiFunnelChart extends ApexChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 6;

    protected function getHeading(): ?string
    {
        return 'Селекција кандидата (' . (now()->year - 1) . ')';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $podaci = $baseQuery->get();

        return [
            'series' => [[
                'name' => 'Број кандидата',
                'data' => [
                    $podaci->sum('broj_validnih_prijava'),
                    $podaci->sum('broj_kandidata_koji_su_ispunlii_merila_ofk'),
                    $podaci->sum('broj_kandidata_koji_su_ispunlii_merila_pfk'),
                    $podaci->sum('broj_kandidata_ispunili_merila_pk'),
                    $podaci->sum('broj_odazvanih_kandidata_na_zavrsnom_razgovoru'),
                    $podaci->sum('broj_kandidata_na_listi'),
                ],
            ]],
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '60%',
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
                'categories' => [
                    'Валидне пријаве',
                    'ОФК',
                    'ПФК',
                    'ПК',
                    'Интервју',
                    'Листа',
                ],
            ],
            'colors' => ['#3b82f6'],
            'grid' => [
                'strokeDashArray' => 3,
            ],
            'legend' => ['show' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' кандидата';
                    }
                }
            }
        }
        JS);
    }
}
