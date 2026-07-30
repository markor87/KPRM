<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ZalbeChart extends ApexChartWidget
{
    use HasDashboardFilters;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 6;

    protected function getHeading(): ?string
    {
        $tipLabel = $this->tipKonkursa === 1 ? 'јавном' : 'интерном';
        return "Жалбе у {$tipLabel} конкурсном поступку";
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = $this->getGodina();

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $result = (clone $baseQuery)->selectRaw('
            SUM(broj_zalbi_na_resenje_o_odbacaju_prijave) as zalbe_odbacaj,
            SUM(broj_zalbi_na_resenje_o_prijemu_u_radni_odnos) as zalbe_prijem,
            SUM(broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave) as usvojene_odbacaj,
            SUM(broj_usvojenih_zalbi_na_resenje_o_prijemu_u_radni_odnos) as usvojene_prijem
        ')->first();

        $zalbeOdbacaj    = (int) ($result->zalbe_odbacaj    ?? 0);
        $zalbePrijem     = (int) ($result->zalbe_prijem     ?? 0);
        $usvojeneOdbacaj = (int) ($result->usvojene_odbacaj ?? 0);
        $usvojenePrijem  = (int) ($result->usvojene_prijem  ?? 0);

        return [
            'series' => [
                [
                    'name' => 'Укупно жалби',
                    'data' => [$zalbeOdbacaj, $zalbePrijem],
                ],
                [
                    'name' => 'Усвојене жалбе',
                    'data' => [$usvojeneOdbacaj, $usvojenePrijem],
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
                    'columnWidth' => '55%',
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
                'categories' => [
                    'На одбацивање пријаве',
                    'На пријем у радни однос',
                ],
                'labels' => ['style' => ['fontSize' => '12px']],
            ],
            'yaxis' => [
                'title' => [
                    'text' => 'Број жалби',
                    'style' => ['fontSize' => '11px'],
                ],
            ],
            'colors' => ['#3b82f6', '#ef4444'],
            'legend' => [
                'show' => true,
                'position' => 'top',
            ],
            'grid' => ['strokeDashArray' => 3],
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
                    return val > 0 ? val : '';
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' жалби';
                    }
                }
            }
        }
        JS);
    }
}
