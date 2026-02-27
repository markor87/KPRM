<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class StepenUspesnostiChart extends ApexChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return 'Степен успешности ' . (now()->year - 1);
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        // status_konkursa_na_dan_1: 1 = Успешно завршен, 2 = Неуспео, 3 = Обустављен
        $uspesno = (clone $baseQuery)->where('status_konkursa_na_dan_1', 1)->count();
        $neuspeo = (clone $baseQuery)->where('status_konkursa_na_dan_1', 2)->count();
        $obustavljeno = (clone $baseQuery)->where('status_konkursa_na_dan_1', 3)->count();

        return [
            'series' => [$uspesno, $neuspeo, $obustavljeno],
            'chart' => [
                'type' => 'donut',
                'height' => 280,
                'background' => 'transparent',
            ],
            'labels' => ['Успешно завршен конкурс', 'Неуспео конкурс', 'Обустављен конкурс'],
            'colors' => ['#3b82f6', '#f59e0b', '#ef4444'],
            'legend' => [
                'show' => true,
                'position' => 'top',
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                        'labels' => ['show' => false],
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontSize' => '14px',
                    'fontWeight' => 'bold',
                    'colors' => ['#ffffff'],
                ],
                'dropShadow' => [
                    'enabled' => true,
                    'top' => 1,
                    'left' => 1,
                    'blur' => 2,
                    'opacity' => 0.3,
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function(val) {
                    return Math.round(val) + '%';
                }
            }
        }
        JS);
    }
}
