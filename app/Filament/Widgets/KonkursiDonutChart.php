<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\PodaciORadnomMestu;
use App\Services\OrganFilterService;

class KonkursiDonutChart extends Widget
{
    protected static string $view = 'filament.widgets.konkursi-donut-chart';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 3;

    public function getData(): array
    {
        $organFilterService = app(OrganFilterService::class);
        $godina = now()->year - 1; // tekuca godina - 1

        $baseQuery = PodaciORadnomMestu::whereYear('datum_oglasavanja', $godina);
        $baseQuery = $organFilterService->applyOrganFilterForCharts($baseQuery, 'organ');

        $javni = (clone $baseQuery)->where('tip_konkursa', 1)->count();
        $interni = (clone $baseQuery)->where('tip_konkursa', 2)->count();

        return [
            'javni' => $javni,
            'interni' => $interni,
            'godina' => $godina,
        ];
    }
}
