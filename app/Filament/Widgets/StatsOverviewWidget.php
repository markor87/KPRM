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
                $isFiltered ? 'Корисници у Вашем Органу' : 'Укупно Корисника',
                $this->getFilteredCount(User::class, 'organ_id')
            )
                ->description($isFiltered
                    ? "Корисници у {$userOrgan}"
                    : 'Сви регистровани корисници у систему'
                )
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, 28]),

            // Radna Mesta - filtrirano po organu
            Stat::make(
                $isFiltered ? 'Радна Места у Вашем Органу' : 'Укупно Радних Места',
                $this->getFilteredCount(PodaciORadnomMestu::class, 'organ')
            )
                ->description($isFiltered
                    ? "Радна места у {$userOrgan}"
                    : 'Сва радна места у систему'
                )
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            // Roles - globalno (ne filtrira se jer su system-wide)
            Stat::make('Укупно Улога', Role::count())
                ->description('Системске улоге')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }
}
