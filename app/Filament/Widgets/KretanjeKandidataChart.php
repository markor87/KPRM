<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTipKonkursaFilter;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class KretanjeKandidataChart extends ApexChartWidget
{
    use HasTipKonkursaFilter;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 12;

    protected static ?int $contentHeight = 450;

    protected function getHeading(): ?string
    {
        $tipLabel = $this->tipKonkursa === 1 ? 'јавном' : 'интерном';
        return "Кретање кандидата кроз процес селекције у {$tipLabel} конкурсу";
    }

    protected function getOptions(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', $this->tipKonkursa);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $result = (clone $baseQuery)->selectRaw('
            SUM(broj_prijava_iz_organa) as prijave_organ,
            SUM(broj_prijava_iz_drugih_organa) as prijave_drugi,
            SUM(broj_prijava_van_drzavnih_organa) as prijave_van,
            SUM(broj_validnih_prijava_iz_organa) as validne_organ,
            SUM(broj_validnih_prijava_iz_drugog_organa) as validne_drugi,
            SUM(broj_validnih_prijava_van_drzavnih_organa) as validne_van,
            SUM(broj_neodazvanih_kandidata_ofk) as neodazvani_ofk,
            SUM(broj_kandidata_iz_organa_na_listi) as lista_organ,
            SUM(broj_kandidata_iz_drugog_drzavnog_organa_na_listi) as lista_drugi,
            SUM(broj_kandidata_van_drzavnih_organa_na_listi) as lista_van,
            SUM(CASE WHEN izabrani_kandidat = 1 THEN 1 ELSE 0 END) as izabrani_organ,
            SUM(CASE WHEN izabrani_kandidat = 2 THEN 1 ELSE 0 END) as izabrani_drugi,
            SUM(CASE WHEN izabrani_kandidat = 3 THEN 1 ELSE 0 END) as izabrani_van,
            SUM(CASE WHEN drugoplasirani_kandidat = 1 THEN 1 ELSE 0 END) as drugoplasirani_organ,
            SUM(CASE WHEN drugoplasirani_kandidat = 2 THEN 1 ELSE 0 END) as drugoplasirani_drugi,
            SUM(CASE WHEN drugoplasirani_kandidat = 3 THEN 1 ELSE 0 END) as drugoplasirani_van
        ')->first();

        return [
            'series' => [
                [
                    'name' => 'Кандидати ван државних органа',
                    'data' => [
                        (int) ($result->prijave_van ?? 0),
                        (int) ($result->validne_van ?? 0),
                        0,
                        (int) ($result->lista_van ?? 0),
                        (int) ($result->izabrani_van ?? 0),
                        (int) ($result->drugoplasirani_van ?? 0),
                    ],
                ],
                [
                    'name' => 'Кандидати из другог државног органа',
                    'data' => [
                        (int) ($result->prijave_drugi ?? 0),
                        (int) ($result->validne_drugi ?? 0),
                        0,
                        (int) ($result->lista_drugi ?? 0),
                        (int) ($result->izabrani_drugi ?? 0),
                        (int) ($result->drugoplasirani_drugi ?? 0),
                    ],
                ],
                [
                    'name' => 'Кандидати из органа',
                    'data' => [
                        (int) ($result->prijave_organ ?? 0),
                        (int) ($result->validne_organ ?? 0),
                        0,
                        (int) ($result->lista_organ ?? 0),
                        (int) ($result->izabrani_organ ?? 0),
                        (int) ($result->drugoplasirani_organ ?? 0),
                    ],
                ],
                [
                    'name' => 'Неодазвани кандидати',
                    'data' => [
                        0,
                        0,
                        (int) ($result->neodazvani_ofk ?? 0),
                        0,
                        0,
                        0,
                    ],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'height' => 400,
                'stacked' => true,
                'stackType' => '100%',
                'toolbar' => ['show' => false],
                'background' => 'transparent',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'borderRadius' => 2,
                    'dataLabels' => ['position' => 'center'],
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
            'xaxis' => [
                'categories' => [
                    'Пристигле пријаве',
                    'Валидне пријаве',
                    'Неодазвани кандидати',
                    'Изборна листа',
                    'Изабрани кандидат',
                    'Другопласирани кандидат',
                ],
                'labels' => ['style' => ['fontSize' => '11px']],
            ],
            'yaxis' => [
                'title' => [
                    'text' => 'Број кандидата',
                    'style' => ['fontSize' => '11px'],
                ],
            ],
            'colors' => ['#3b82f6', '#0d9488', '#1e3a5f', '#dc2626'],
            'legend' => [
                'show' => true,
                'position' => 'bottom',
            ],
            'grid' => ['strokeDashArray' => 3],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function(val, opts) {
                    var actual = opts.w.globals.series[opts.seriesIndex][opts.dataPointIndex];
                    return actual > 0 ? actual : '';
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return Math.round(val) + '%';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val, opts) {
                        return opts.w.globals.series[opts.seriesIndex][opts.dataPointIndex];
                    }
                }
            }
        }
        JS);
    }
}
