<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Carbon\Carbon;

class ProsecnoVremeTrajanjaChart extends Widget
{
    protected static string $view = 'filament.widgets.prosecno-vreme-trajanja-chart';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 12;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 2; // 2024

        // Samo javni konkursi
        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', 1);
        $baseQuery = $organFilterService->applyOrganFilter($baseQuery, 'organ');

        $konkursi = $baseQuery->get();

        // Računaj proseke za svaki interval
        $intervals = [
            'saglasnost_do_resenja' => [
                'label' => 'Од добијања сагласности Владе до доношења решења о покретању поступка',
                'from' => 'datum_dobijanja_saglasnosti_vlade',
                'to' => 'datum_donosenja_resenja_o_pokretanju_postupka'
            ],
            'resenje_do_obavestenja' => [
                'label' => 'Од доношења решења о покретању до добијања обавештења СУКа',
                'from' => 'datum_donosenja_resenja_o_pokretanju_postupka',
                'to' => 'datum_dobijanja_obavestenja_od_suka'
            ],
            'obavestenje_do_sastanka' => [
                'label' => 'Од добијања обавештења СУКа до одржавања Првог састанка',
                'from' => 'datum_dobijanja_obavestenja_od_suka',
                'to' => 'datum_odrzavanja_prvog_sastanka'
            ],
            'sastanak_do_oglasavanja' => [
                'label' => 'Од одржавања Првог састанка до оглашавања',
                'from' => 'datum_odrzavanja_prvog_sastanka',
                'to' => 'datum_oglasavanja'
            ],
            'oglasavanje_do_pregleda' => [
                'label' => 'Од оглашавања до прегледа пријава',
                'from' => 'datum_oglasavanja',
                'to' => 'datum_pregleda_prijava'
            ],
            'pregled_do_ofk' => [
                'label' => 'Од прегледа пријава до ОФК',
                'from' => 'datum_pregleda_prijava',
                'to' => 'datum_pocetka_provere_ofk'
            ],
            'ofk_do_pfk' => [
                'label' => 'Од ОФК до ПФК',
                'from' => 'datum_pocetka_provere_ofk',
                'to' => 'datum_pocetka_provere_pfk'
            ],
            'pfk_do_pk' => [
                'label' => 'Од ПФК до ПК',
                'from' => 'datum_pocetka_provere_pfk',
                'to' => 'datum_pocetka_provere_pk'
            ],
            'pk_do_dokumentacije' => [
                'label' => 'Од ПК до предаје документације',
                'from' => 'datum_pocetka_provere_pk',
                'to' => 'datum_predaje_dokumentacije'
            ],
            'dokumentacija_do_intervjua' => [
                'label' => 'Од предаје документације до завршног разговора',
                'from' => 'datum_predaje_dokumentacije',
                'to' => 'datum_pocetka_sprovodjenja_intervjua'
            ],
            'intervju_do_liste' => [
                'label' => 'Од завршног разговора до достављања листе руководиоцу',
                'from' => 'datum_pocetka_sprovodjenja_intervjua',
                'to' => 'datum_dostavljanja_liste_rukovodiocu_organa'
            ],
            'lista_do_resenja' => [
                'label' => 'Од достављања листе руководиоцу до достављања решења о изабраном кандидату',
                'from' => 'datum_dostavljanja_liste_rukovodiocu_organa',
                'to' => 'datum_donosenja_resenja_o_izabranom_kandidatu'
            ],
            'resenje_do_stupanja' => [
                'label' => 'Од доношења решења о изабраном кандидату до ступања на рад',
                'from' => 'datum_donosenja_resenja_o_izabranom_kandidatu',
                'to' => 'datum_stupanja_na_rad'
            ],
        ];

        $averages = [];
        $labels = [];

        foreach ($intervals as $key => $interval) {
            $sum = 0;
            $count = 0;

            foreach ($konkursi as $konkurs) {
                if ($konkurs->{$interval['from']} && $konkurs->{$interval['to']}) {
                    $from = Carbon::parse($konkurs->{$interval['from']});
                    $to = Carbon::parse($konkurs->{$interval['to']});
                    $days = $from->diffInDays($to);
                    $sum += $days;
                    $count++;
                }
            }

            $average = $count > 0 ? round($sum / $count) : 0;
            $averages[] = $average;
            $labels[] = $interval['label'];
        }

        return [
            'labels' => $labels,
            'averages' => $averages,
            'godina' => $godina,
        ];
    }
}
