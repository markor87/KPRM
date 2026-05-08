<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTipKonkursaFilter;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KonkursiPoZvanjuChart extends ApexChartWidget
{
    use HasTipKonkursaFilter;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return 'Конкурси по звањима (' . (now()->year - 1) . ')';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa)
            ->whereNotNull('podaci_o_radnom_mestu.zvanje');
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $podaci = (clone $baseQuery)
            ->join('sifarnik_zvanje', 'podaci_o_radnom_mestu.zvanje', '=', 'sifarnik_zvanje.id')
            ->selectRaw('sifarnik_zvanje.zvanje as naziv, COUNT(*) as ukupno')
            ->groupBy('sifarnik_zvanje.id', 'sifarnik_zvanje.zvanje')
            ->orderByDesc('ukupno')
            ->get();

        return [
            'series' => $podaci->pluck('ukupno')->toArray(),
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
                        return val + ' конкурса';
                    }
                }
            }
        }
        JS);
    }
}
