<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;
use Carbon\Carbon;

class TrajanjePostupakaChart extends Widget
{
    protected static string $view = 'filament.widgets.trajanje-postupaka-chart';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 12;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1; // tekuca godina - 1

        // Bazni query sa filtrom po organu
        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilter($baseQuery, 'organ');

        // JAVNI KONKURSI - Vreme trajanja konkursnog postupka
        $javniQuery = (clone $baseQuery)->where('tip_konkursa', 1);
        $javniKonkursi = $javniQuery->get();

        $javniDurations = [];
        foreach ($javniKonkursi as $konkurs) {
            if ($konkurs->datum_donosenja_resenja_o_pokretanju_postupka && $konkurs->datum_stupanja_na_rad) {
                $start = Carbon::parse($konkurs->datum_donosenja_resenja_o_pokretanju_postupka);
                $end = Carbon::parse($konkurs->datum_stupanja_na_rad);
                $days = $start->diffInDays($end);
                $javniDurations[] = $days;
            }
        }

        $javniStats = [
            'min' => !empty($javniDurations) ? min($javniDurations) : 0,
            'avg' => !empty($javniDurations) ? round(array_sum($javniDurations) / count($javniDurations), 2) : 0,
            'max' => !empty($javniDurations) ? max($javniDurations) : 0,
        ];

        // JAVNI KONKURSI - Vreme trajanja izbornog postupka
        $izbornaQuery = (clone $baseQuery)->where('tip_konkursa', 1);
        $izbornaKonkursi = $izbornaQuery->get();

        $izborniDurations = [];
        foreach ($izbornaKonkursi as $konkurs) {
            if ($konkurs->datum_pregleda_prijava && $konkurs->datum_dostavljanja_liste_rukovodiocu_organa) {
                $start = Carbon::parse($konkurs->datum_pregleda_prijava);
                $end = Carbon::parse($konkurs->datum_dostavljanja_liste_rukovodiocu_organa);
                $days = $start->diffInDays($end);
                $izborniDurations[] = $days;
            }
        }

        $izbornaStats = [
            'min' => !empty($izborniDurations) ? min($izborniDurations) : 0,
            'avg' => !empty($izborniDurations) ? round(array_sum($izborniDurations) / count($izborniDurations), 2) : 0,
            'max' => !empty($izborniDurations) ? max($izborniDurations) : 0,
        ];

        return [
            'javni' => $javniStats,
            'izborna' => $izbornaStats,
            'godina' => $godina,
        ];
    }
}
