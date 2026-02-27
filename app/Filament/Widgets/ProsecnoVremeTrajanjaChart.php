<?php

namespace App\Filament\Widgets;

use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ProsecnoVremeTrajanjaChart extends ApexChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 12;

    protected static ?int $contentHeight = 650;

    protected function getHeading(): ?string
    {
        return 'Просечно време трајања фаза јавних конкурсних поступака изражено у данима (' . (now()->year - 1) . ')';
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $intervals = [
            [
                'label' => 'Од добијања сагласности Владе до доношења решења о покретању поступка',
                'from' => 'datum_dobijanja_saglasnosti_vlade',
                'to' => 'datum_donosenja_resenja_o_pokretanju_postupka',
            ],
            [
                'label' => 'Од доношења решења о покретању до добијања обавештења СУКа',
                'from' => 'datum_donosenja_resenja_o_pokretanju_postupka',
                'to' => 'datum_dobijanja_obavestenja_od_suka',
            ],
            [
                'label' => 'Од добијања обавештења СУКа до одржавања Првог састанка',
                'from' => 'datum_dobijanja_obavestenja_od_suka',
                'to' => 'datum_odrzavanja_prvog_sastanka',
            ],
            [
                'label' => 'Од одржавања Првог састанка до оглашавања',
                'from' => 'datum_odrzavanja_prvog_sastanka',
                'to' => 'datum_oglasavanja',
            ],
            [
                'label' => 'Од оглашавања до прегледа пријава',
                'from' => 'datum_oglasavanja',
                'to' => 'datum_pregleda_prijava',
            ],
            [
                'label' => 'Од прегледа пријава до ОФК',
                'from' => 'datum_pregleda_prijava',
                'to' => 'datum_pocetka_provere_ofk',
            ],
            [
                'label' => 'Од ОФК до ПФК',
                'from' => 'datum_pocetka_provere_ofk',
                'to' => 'datum_pocetka_provere_pfk',
            ],
            [
                'label' => 'Од ПФК до ПК',
                'from' => 'datum_pocetka_provere_pfk',
                'to' => 'datum_pocetka_provere_pk',
            ],
            [
                'label' => 'Од ПК до предаје документације',
                'from' => 'datum_pocetka_provere_pk',
                'to' => 'datum_predaje_dokumentacije',
            ],
            [
                'label' => 'Од предаје документације до завршног разговора',
                'from' => 'datum_predaje_dokumentacije',
                'to' => 'datum_pocetka_sprovodjenja_intervjua',
            ],
            [
                'label' => 'Од завршног разговора до достављања листе руководиоцу',
                'from' => 'datum_pocetka_sprovodjenja_intervjua',
                'to' => 'datum_dostavljanja_liste_rukovodiocu_organa',
            ],
            [
                'label' => 'Од достављања листе руководиоцу до достављања решења о изабраном кандидату',
                'from' => 'datum_dostavljanja_liste_rukovodiocu_organa',
                'to' => 'datum_donosenja_resenja_o_izabranom_kandidatu',
            ],
            [
                'label' => 'Од доношења решења о изабраном кандидату до ступања на рад',
                'from' => 'datum_donosenja_resenja_o_izabranom_kandidatu',
                'to' => 'datum_stupanja_na_rad',
            ],
        ];

        $selectExpressions = [];
        foreach ($intervals as $index => $interval) {
            $from = $interval['from'];
            $to = $interval['to'];
            $selectExpressions[] = "ROUND(AVG(CASE WHEN {$from} IS NOT NULL AND {$to} IS NOT NULL THEN DATEDIFF({$to}, {$from}) END)) as avg_{$index}";
        }

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', 1);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $result = $baseQuery
            ->selectRaw(implode(', ', $selectExpressions))
            ->first();

        $averages = [];
        $labels = [];

        foreach ($intervals as $index => $interval) {
            $averages[] = (int) ($result->{"avg_{$index}"} ?? 0);
            $labels[] = $interval['label'];
        }

        return [
            'series' => [[
                'name' => 'Просечан број дана',
                'data' => $averages,
            ]],
            'chart' => [
                'type' => 'bar',
                'height' => 600,
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
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
                'categories' => $labels,
                'title' => [
                    'text' => 'Број дана',
                    'style' => ['fontSize' => '12px'],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'maxWidth' => 450,
                    'style' => ['fontSize' => '10px'],
                ],
            ],
            'colors' => ['#dc2626'],
            'grid' => [
                'strokeDashArray' => 3,
                'padding' => ['left' => 20, 'right' => 40],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
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
