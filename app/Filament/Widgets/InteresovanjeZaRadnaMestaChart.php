<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;

class InteresovanjeZaRadnaMestaChart extends Widget
{
    protected static string $view = 'filament.widgets.interesovanje-za-radna-mesta-chart';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 6;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 2; // 2024

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilter($baseQuery, 'organ');

        $podaci = $baseQuery->get();

        // Saberi ukupne brojeve
        $ukupanBrojPrijava = $podaci->sum('ukupan_broj_prijava') ?? 0;
        $brojValidnihPrijava = $podaci->sum('broj_validnih_prijava') ?? 0;
        $brojOdbarenihPrijava = $ukupanBrojPrijava - $brojValidnihPrijava;

        return [
            'ukupanBrojPrijava' => $ukupanBrojPrijava,
            'brojValidnihPrijava' => $brojValidnihPrijava,
            'brojOdbarenihPrijava' => $brojOdbarenihPrijava,
            'godina' => $godina,
        ];
    }
}
