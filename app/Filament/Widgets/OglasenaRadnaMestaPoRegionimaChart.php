<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTipKonkursaFilter;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OglasenaRadnaMestaPoRegionimaChart extends ApexChartWidget
{
    use HasTipKonkursaFilter;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return 'Радна места по регионима ' . (now()->year - 1);
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa)
            ->join('mesto_rada_podaci_o_radnom_mestu', 'podaci_o_radnom_mestu.id', '=', 'mesto_rada_podaci_o_radnom_mestu.podaci_o_radnom_mestu_id')
            ->whereNotNull('mesto_rada_podaci_o_radnom_mestu.region')
            ->where('mesto_rada_podaci_o_radnom_mestu.region', '!=', '');
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $podaci = (clone $baseQuery)
            ->selectRaw('mesto_rada_podaci_o_radnom_mestu.region as naziv, SUM(mesto_rada_podaci_o_radnom_mestu.broj_izvrsilaca) as ukupno')
            ->groupBy('mesto_rada_podaci_o_radnom_mestu.region')
            ->orderByDesc('ukupno')
            ->get();

        return [
            'series' => $podaci->pluck('ukupno')->map(fn($v) => (int) $v)->toArray(),
            'chart' => [
                'type' => 'donut',
                'height' => 280,
                'background' => 'transparent',
            ],
            'labels' => $podaci->pluck('naziv')->toArray(),
            'colors' => [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#6366f1',
            ],
            'legend' => ['show' => false],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '60%',
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
                    'fontSize' => '11px',
                    'fontWeight' => 'bold',
                    'colors' => ['#ffffff'],
                ],
                'dropShadow' => ['enabled' => false],
            ],
        ];
    }

    protected function extraJsOptions(): ?\Filament\Support\RawJs
    {
        return \Filament\Support\RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function(val, opts) {
                    return opts.w.globals.series[opts.seriesIndex];
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' места';
                    }
                }
            }
        }
        JS);
    }
}
