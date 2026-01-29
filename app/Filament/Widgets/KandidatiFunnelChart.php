<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;

class KandidatiFunnelChart extends Widget
{
    protected static string $view = 'filament.widgets.kandidati-funnel-chart';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 6;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1;

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilter($baseQuery, 'organ');

        $podaci = $baseQuery->get();

        // Saberi ukupne brojeve za svaku fazu selekcije
        return [
            'brojValidnihPrijava' => $podaci->sum('broj_validnih_prijava') ?? 0,
            'brojKandidataOfk' => $podaci->sum('broj_kandidata_koji_su_ispunlii_merila_ofk') ?? 0,
            'brojKandidataPfk' => $podaci->sum('broj_kandidata_koji_su_ispunlii_merila_pfk') ?? 0,
            'brojKandidataPk' => $podaci->sum('broj_kandidata_ispunili_merila_pk') ?? 0,
            'brojOdazvanihIntervju' => $podaci->sum('broj_odazvanih_kandidata_na_zavrsnom_razgovoru') ?? 0,
            'brojKandidataNaListi' => $podaci->sum('broj_kandidata_na_listi') ?? 0,
            'godina' => $godina,
        ];
    }
}
