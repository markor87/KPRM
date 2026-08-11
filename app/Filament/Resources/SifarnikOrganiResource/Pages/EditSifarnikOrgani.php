<?php

namespace App\Filament\Resources\SifarnikOrganiResource\Pages;

use App\Filament\Resources\SifarnikOrganiResource;
use Filament\Resources\Pages\EditRecord;

class EditSifarnikOrgani extends EditRecord
{
    protected static string $resource = SifarnikOrganiResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
