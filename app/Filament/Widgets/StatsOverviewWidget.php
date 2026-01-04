<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\PodaciORadnomMestu;
use App\Filament\Traits\HasOrganFiltering;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StatsOverviewWidget extends BaseWidget
{
    use HasOrganFiltering;

    protected function getStats(): array
    {
        // Proveri da li je filtrirano
        $isFiltered = !$this->canViewAllOrganData();
        $userOrgan = $isFiltered ? auth()->user()?->organ?->organ : null;

        return [
            // Korisnici - filtrirano po organu
            Stat::make(
                $isFiltered ? 'Users in Your Organ' : 'Total Users',
                $this->getFilteredCount(User::class, 'organ_id')
            )
                ->description($isFiltered
                    ? "Users in {$userOrgan}"
                    : 'All registered users in the system'
                )
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, 28]),

            // Radna Mesta - filtrirano po organu
            Stat::make(
                $isFiltered ? 'Radna Mesta in Your Organ' : 'Total Radna Mesta',
                $this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
            )
                ->description($isFiltered
                    ? "Job positions in {$userOrgan}"
                    : 'All job positions in the system'
                )
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            // Roles - globalno (ne filtrira se jer su system-wide)
            Stat::make('Total Roles', Role::count())
                ->description('System roles')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }
}
