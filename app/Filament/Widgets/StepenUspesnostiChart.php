<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;

class StepenUspesnostiChart extends Widget
{
    protected static string $view = 'filament.widgets.stepen-uspesnosti-chart';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 3;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1; // tekuca godina - 1

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilter($baseQuery, 'organ');

        // status_konkursa_na_dan_1: 1 = Успешно завршен, 3 = Обустављен
        $uspesno = (clone $baseQuery)->where('status_konkursa_na_dan_1', 1)->count();
        $obustavljeno = (clone $baseQuery)->where('status_konkursa_na_dan_1', 3)->count();

        $ukupno = $uspesno + $obustavljeno;
        $uspesnoProcenat = $ukupno > 0 ? round(($uspesno / $ukupno) * 100) : 0;
        $obustavljenoProcenat = $ukupno > 0 ? round(($obustavljeno / $ukupno) * 100) : 0;

        return [
            'uspesno' => $uspesno,
            'obustavljeno' => $obustavljeno,
            'uspesnoProcenat' => $uspesnoProcenat,
            'obustavljenoProcenat' => $obustavljenoProcenat,
            'godina' => $godina,
        ];
    }
}
