<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdminPanel extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Admin Panel';

    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
