<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, 28]),

            Stat::make('Total Roles', Role::count())
                ->description('System roles')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make('Total Permissions', Permission::count())
                ->description('System permissions')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),
        ];
    }
}
