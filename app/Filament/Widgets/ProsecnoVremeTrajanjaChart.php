<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Illuminate\Support\Facades\DB;

class ProsecnoVremeTrajanjaChart extends Widget
{
    protected string $view = 'filament.widgets.prosecno-vreme-trajanja-chart';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 12;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1; // tekuca godina - 1

        // Definicija intervala sa labelama
        $intervals = [
            [
                'label' => 'Од добијања сагласности Владе до доношења решења о покретању поступка',
                'from' => 'datum_dobijanja_saglasnosti_vlade',
                'to' => 'datum_donosenja_resenja_o_pokretanju_postupka'
            ],
            [
                'label' => 'Од доношења решења о покретању до добијања обавештења СУКа',
                'from' => 'datum_donosenja_resenja_o_pokretanju_postupka',
                'to' => 'datum_dobijanja_obavestenja_od_suka'
            ],
            [
                'label' => 'Од добијања обавештења СУКа до одржавања Првог састанка',
                'from' => 'datum_dobijanja_obavestenja_od_suka',
                'to' => 'datum_odrzavanja_prvog_sastanka'
            ],
            [
                'label' => 'Од одржавања Првог састанка до оглашавања',
                'from' => 'datum_odrzavanja_prvog_sastanka',
                'to' => 'datum_oglasavanja'
            ],
            [
                'label' => 'Од оглашавања до прегледа пријава',
                'from' => 'datum_oglasavanja',
                'to' => 'datum_pregleda_prijava'
            ],
            [
                'label' => 'Од прегледа пријава до ОФК',
                'from' => 'datum_pregleda_prijava',
                'to' => 'datum_pocetka_provere_ofk'
            ],
            [
                'label' => 'Од ОФК до ПФК',
                'from' => 'datum_pocetka_provere_ofk',
                'to' => 'datum_pocetka_provere_pfk'
            ],
            [
                'label' => 'Од ПФК до ПК',
                'from' => 'datum_pocetka_provere_pfk',
                'to' => 'datum_pocetka_provere_pk'
            ],
            [
                'label' => 'Од ПК до предаје документације',
                'from' => 'datum_pocetka_provere_pk',
                'to' => 'datum_predaje_dokumentacije'
            ],
            [
                'label' => 'Од предаје документације до завршног разговора',
                'from' => 'datum_predaje_dokumentacije',
                'to' => 'datum_pocetka_sprovodjenja_intervjua'
            ],
            [
                'label' => 'Од завршног разговора до достављања листе руководиоцу',
                'from' => 'datum_pocetka_sprovodjenja_intervjua',
                'to' => 'datum_dostavljanja_liste_rukovodiocu_organa'
            ],
            [
                'label' => 'Од достављања листе руководиоцу до достављања решења о изабраном кандидату',
                'from' => 'datum_dostavljanja_liste_rukovodiocu_organa',
                'to' => 'datum_donosenja_resenja_o_izabranom_kandidatu'
            ],
            [
                'label' => 'Од доношења решења о изабраном кандидату до ступања на рад',
                'from' => 'datum_donosenja_resenja_o_izabranom_kandidatu',
                'to' => 'datum_stupanja_na_rad'
            ],
        ];

        // Kreiraj SELECT izraze za sve proseke u jednom upitu
        $selectExpressions = [];
        foreach ($intervals as $index => $interval) {
            $from = $interval['from'];
            $to = $interval['to'];
            $selectExpressions[] = "ROUND(AVG(CASE WHEN {$from} IS NOT NULL AND {$to} IS NOT NULL THEN DATEDIFF({$to}, {$from}) END)) as avg_{$index}";
        }

        // Bazni upit sa filterima
        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina)
            ->where('tip_konkursa', 1);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        // Izvrši jedan upit sa svim AVG kalkulacijama
        $result = $baseQuery
            ->selectRaw(implode(', ', $selectExpressions))
            ->first();

        // Izvuci rezultate
        $averages = [];
        $labels = [];

        foreach ($intervals as $index => $interval) {
            $averages[] = (int) ($result->{"avg_{$index}"} ?? 0);
            $labels[] = $interval['label'];
        }

        return [
            'labels' => $labels,
            'averages' => $averages,
            'godina' => $godina,
        ];
    }
}
