<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KonkursiDonutChart extends ApexChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return 'Оглашени конкурси ' . (now()->year - 1);
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $javni = (clone $baseQuery)->where('tip_konkursa', 1)->count();
        $interni = (clone $baseQuery)->where('tip_konkursa', 2)->count();

        return [
            'series' => [$javni, $interni],
            'chart' => [
                'type' => 'donut',
                'height' => 280,
                'background' => 'transparent',
            ],
            'labels' => ["Јавни: {$javni}", "Интерни: {$interni}"],
            'colors' => ['#3b82f6', '#10b981'],
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
