<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrganFilterService
{
    /**
     * Apply organ-based filtering to a query
     *
     * @param Builder $query
     * @param string $organColumn The column name for organ (default: 'organ')
     * @return Builder
     */
    public function applyOrganFilter(Builder $query, string $organColumn = 'organ'): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admins vide sve
        if ($user->is_super_admin || $user->hasRole('Super Admin')) {
            return $query;
        }

        // Korisnici sa view_any_podaci::o::radnom::mestu dozvolom vide sve
        if ($user->can('view_any_podaci::o::radnom::mestu')) {
            return $query;
        }

        // Filtriraj po organ_id korisnika
        if ($user->organ_id) {
            return $query->where($organColumn, $user->organ_id);
        }

        // Korisnici bez organa ne vide ništa
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if current user can view all data (bypasses organ filtering)
     *
     * @return bool
     */
    public function canViewAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->is_super_admin
            || $user->hasRole('Super Admin')
            || $user->can('view_any_podaci::o::radnom::mestu');
    }

    /**
     * Get the current user's organ ID
     *
     * @return int|null
     */
    public function getUserOrganId(): ?int
    {
        return Auth::user()?->organ_id;
    }

    /**
     * Check if user should see empty results
     *
     * @return bool
     */
    public function shouldShowEmptyResults(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return true;
        }

        return !$this->canViewAllData() && !$user->organ_id;
    }
}
