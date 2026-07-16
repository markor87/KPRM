<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InteresovanjeZaRadnaMestaChart extends ApexChartWidget
{
    use HasDashboardFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 6;

    protected function getHeading(): ?string
    {
        return 'Интересовање за радна места';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = $this->godina;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
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
                        return val + ' пријава';
                    }
                }
            }
        }
        JS);
    }
}
