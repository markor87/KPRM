<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TrajanjePostupakaChart extends ApexChartWidget
{
    use HasDashboardFilters;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 12;

    protected function getHeading(): ?string
    {
        $tipLabel = $this->tipKonkursa === 1 ? 'јавних' : 'интерних';
        return "Трајање {$tipLabel} конкурсних и изборних поступака";
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = $this->getGodina();

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ', $this->getOrganId());

        $podaci = $baseQuery->get();

        $javniDurations = [];
        $izborniDurations = [];

        foreach ($podaci as $konkurs) {
            if ($konkurs->datum_donosenja_resenja_o_pokretanju_postupka && $konkurs->datum_stupanja_na_rad) {
                $javniDurations[] = Carbon::parse($konkurs->datum_donosenja_resenja_o_pokretanju_postupka)
                    ->diffInDays(Carbon::parse($konkurs->datum_stupanja_na_rad));
            }

            if ($konkurs->datum_pregleda_prijava && $konkurs->datum_dostavljanja_liste_rukovodiocu_organa) {
                $izborniDurations[] = Carbon::parse($konkurs->datum_pregleda_prijava)
                    ->diffInDays(Carbon::parse($konkurs->datum_dostavljanja_liste_rukovodiocu_organa));
            }
        }

        $javniMin = !empty($javniDurations) ? min($javniDurations) : 0;
        $javniAvg = !empty($javniDurations) ? round(array_sum($javniDurations) / count($javniDurations)) : 0;
        $javniMax = !empty($javniDurations) ? max($javniDurations) : 0;

        $izbornaMin = !empty($izborniDurations) ? min($izborniDurations) : 0;
        $izbornaAvg = !empty($izborniDurations) ? round(array_sum($izborniDurations) / count($izborniDurations)) : 0;
        $izbornaMax = !empty($izborniDurations) ? max($izborniDurations) : 0;

        return [
            'series' => [
                [
                    'name' => 'Конкурсни поступак',
                    'data' => [$javniMin, $javniAvg, $javniMax],
                ],
                [
                    'name' => 'Изборни поступак',
                    'data' => [$izbornaMin, $izbornaAvg, $izbornaMax],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '50%',
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
                'categories' => ['Најкраћи', 'Просек', 'Најдужи'],
            ],
            'colors' => ['#dc2626', '#1d4ed8'],
            'grid' => [
                'strokeDashArray' => 3,
            ],
            'legend' => [
                'show' => true,
                'position' => 'top',
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
                },
                formatter: function(val) {
                    return val + ' дана';
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' дана';
                    }
                }
            }
        }
        JS);
    }
}
