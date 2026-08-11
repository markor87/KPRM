<?php

namespace App\Filament\Resources\SifarnikOrganiResource\Pages;

use App\Filament\Resources\SifarnikOrganiResource;
use Filament\Resources\Pages\ListRecords;

class ListSifarnikOrgani extends ListRecords
{
    protected static string $resource = SifarnikOrganiResource::class;

    /**
     * Органи се не додају кроз апликацију — шифарник је задат.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
