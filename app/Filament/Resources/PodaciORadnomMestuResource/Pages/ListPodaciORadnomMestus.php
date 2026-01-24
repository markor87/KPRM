<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use App\Filament\Resources\PodaciORadnomMestuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPodaciORadnomMestus extends ListRecords
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ново радно место'),
        ];
    }
}
