<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KandidatiFunnelChart extends ApexChartWidget
{
    use HasDashboardFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 6;

    protected function getHeading(): ?string
    {
        return 'Селекција кандидата';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = $this->getGodina();

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $result = (clone $baseQuery)->selectRaw('
            SUM(broj_validnih_prijava) as validne,
            SUM(broj_neodazvanih_kandidata_ofk) as neodazvani_ofk,
            SUM(broj_kandidata_koji_su_ispunlii_merila_ofk) as ofk,
            SUM(broj_kandidata_koji_su_ispunlii_merila_pfk) as pfk,
            SUM(broj_kandidata_ispunili_merila_pk) as pk,
            SUM(broj_odazvanih_kandidata_na_zavrsnom_razgovoru) as intervju,
            SUM(broj_kandidata_na_listi) as lista
        ')->first();

        return [
            'series' => [[
                'name' => 'Број кандидата',
                'data' => [
                    (int) ($result->validne ?? 0),
                    (int) ($result->neodazvani_ofk ?? 0),
                    (int) ($result->ofk ?? 0),
                    (int) ($result->pfk ?? 0),
                    (int) ($result->pk ?? 0),
                    (int) ($result->intervju ?? 0),
                    (int) ($result->lista ?? 0),
                ],
            ]],
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '60%',
                    'borderRadius' => 4,
                    'distributed' => true,
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
                    'Валидне пријаве',
                    'Неодазвани кандидати',
                    'ОФК',
                    'ПФК',
                    'ПК',
                    'Интервју',
                    'Листа',
                ],
            ],
            'colors' => ['#3b82f6', '#dc2626', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6'],
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
                        return val + ' кандидата';
                    }
                }
            }
        }
        JS);
    }
}
