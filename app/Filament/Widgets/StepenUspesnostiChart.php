<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTipKonkursaFilter;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class StepenUspesnostiChart extends ApexChartWidget
{
    use HasTipKonkursaFilter;

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

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $uspesno = (clone $baseQuery)->where('status_konkursa_na_dan_2', 1)->count();
        $neuspeo = (clone $baseQuery)->where('status_konkursa_na_dan_2', 2)->count();
        $obustavljeno = (clone $baseQuery)->where('status_konkursa_na_dan_2', 3)->count();

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
                'show' => false,
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                        'labels' => [
                            'show' => true,
                            'total' => [
                                'show' => true,
                                'label' => 'Укупно',
                                'fontSize' => '13px',
                                'fontWeight' => 600,
                            ],
                            'value' => [
                                'show' => true,
                                'fontSize' => '22px',
                                'fontWeight' => 'bold',
                                'offsetY' => 4,
                            ],
                        ],
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
