<?php

namespace App\Filament\Resources\PodaciORadnomMestuResource\Pages;

use App\Filament\Resources\PodaciORadnomMestuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPodaciORadnomMestu extends EditRecord
{
    protected static string $resource = PodaciORadnomMestuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
