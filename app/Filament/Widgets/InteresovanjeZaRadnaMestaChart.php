<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InteresovanjeZaRadnaMestaChart extends ApexChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 6;

    protected function getHeading(): ?string
    {
        return 'Интересовање за радна места (' . (now()->year - 1) . ')';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $podaci = $baseQuery->get();

        $ukupanBrojPrijava = $podaci->sum('ukupan_broj_prijava');
        $brojValidnihPrijava = $podaci->sum('broj_validnih_prijava');
        $brojOdbarenihPrijava = $ukupanBrojPrijava - $brojValidnihPrijava;

        return [
            'series' => [[
                'name' => 'Број пријава',
                'data' => [$ukupanBrojPrijava, $brojValidnihPrijava, $brojOdbarenihPrijava],
            ]],
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'distributed' => true,
                    'dataLabels' => ['position' => 'top'],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'offsetX' => 5,
                'style' => [
                    'fontSize' => '13px',
                    'fontWeight' => 'bold',
                ],
            ],
            'xaxis' => [
                'categories' => [
                    'Број пристиглих пријава',
                    'Број валидних пријава',
                    'Број одбачених пријава',
                ],
            ],
            'colors' => ['#3b5998', '#5a7cbe', '#8ba3d4'],
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
                        return val + ' пријава';
                    }
                }
            }
        }
        JS);
    }
}
