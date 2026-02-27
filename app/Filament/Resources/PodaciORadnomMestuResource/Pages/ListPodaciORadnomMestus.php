<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\PodaciORadnomMestuResource;
use App\Exports\PodaciORadnomMestuExport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPodaciORadnomMestus extends ListRecords
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Извоз у Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();

                    return Excel::download(
                        new PodaciORadnomMestuExport($query),
                        'radna-mesta-' . now()->format('Y-m-d-His') . '.xlsx'
                    );
                }),
            CreateAction::make()
                ->label('Ново радно место'),
        ];
    }
}
