<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTipKonkursaFilter;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KonkursiDonutChart extends ApexChartWidget
{
    use HasTipKonkursaFilter;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return 'Оглашени конкурси';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $tipCount = (clone $baseQuery)->where('tip_konkursa', $this->tipKonkursa)->count();
        $total = (clone $baseQuery)->count();
        $tipLabel = $this->tipKonkursa === 1 ? 'Јавни' : 'Интерни';
        $tipColor = $this->tipKonkursa === 1 ? '#3b82f6' : '#10b981';
        $percent = $total > 0 ? round($tipCount / $total * 100) : 0;

        return [
            'series' => [$percent],
            'chart' => [
                'type' => 'radialBar',
                'height' => 280,
                'background' => 'transparent',
            ],
            'colors' => [$tipColor],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => ['size' => '55%'],
                    'dataLabels' => [
                        'name' => [
                            'show' => true,
                            'fontSize' => '14px',
                            'offsetY' => -8,
                        ],
                        'value' => [
                            'show' => true,
                            'fontSize' => '26px',
                            'fontWeight' => 'bold',
                            'offsetY' => 6,
                        ],
                    ],
                ],
            ],
            'labels' => ["{$tipLabel}: {$tipCount}"],
            'legend' => ['show' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            plotOptions: {
                radialBar: {
                    dataLabels: {
                        value: {
                            formatter: function(val) {
                                return val + '%';
                            }
                        }
                    }
                }
            }
        }
        JS);
    }
}
