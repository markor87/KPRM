<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RazloziNeuspehaChart extends ApexChartWidget
{
    use HasDashboardFilters;

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 12;

    protected static ?int $contentHeight = 550;

    protected function getHeading(): ?string
    {
        $tipLabel = $this->tipKonkursa === 1 ? 'јавних' : 'интерних';
        return "Разлози неуспеха {$tipLabel} конкурсних поступака";
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = $this->getGodina();

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa)
            ->where('ishod_konkursa', 2)
            ->whereNotNull('razlog_neuspelog_konkursa')
            ->join('sifarnik_razlog_neuspelih_konkursa', 'podaci_o_radnom_mestu.razlog_neuspelog_konkursa', '=', 'sifarnik_razlog_neuspelih_konkursa.id');
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'podaci_o_radnom_mestu.organ');

        $rows = $baseQuery
            ->selectRaw('sifarnik_razlog_neuspelih_konkursa.razlog as naziv, COUNT(*) as ukupno')
            ->groupBy('sifarnik_razlog_neuspelih_konkursa.id', 'sifarnik_razlog_neuspelih_konkursa.razlog')
            ->orderByDesc('ukupno')
            ->get();

        $labels = $rows->pluck('naziv')->toArray();
        $values = $rows->pluck('ukupno')->map(fn($v) => (int) $v)->toArray();

        return [
            'series' => [[
                'name' => 'Број конкурса',
                'data' => $values,
            ]],
            'chart' => [
                'type' => 'bar',
                'height' => 500,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'dataLabels' => ['position' => 'top'],
                    'barHeight' => '60%',
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
                'categories' => $labels,
                'title' => [
                    'text' => 'Број конкурса',
                    'style' => ['fontSize' => '12px'],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'maxWidth' => 500,
                    'style' => ['fontSize' => '11px'],
                ],
            ],
            'colors' => ['#f59e0b'],
            'grid' => [
                'strokeDashArray' => 3,
                'padding' => ['left' => 20, 'right' => 60],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            chart: {
                events: {
                    updated: function(ctx) {
                        var isDark = document.documentElement.classList.contains('dark');
                        var c = isDark ? '#e5e7eb' : '#111827';
                        if (ctx.w.config.dataLabels.style.colors && ctx.w.config.dataLabels.style.colors[0] === c) return;
                        ctx.updateOptions({ dataLabels: { style: { colors: Array(20).fill(c) } } }, false, false, false);
                    }
                }
            },
            dataLabels: {
                style: {
                    colors: Array(20).fill(document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827')
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
