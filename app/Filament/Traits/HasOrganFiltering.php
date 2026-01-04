<?php

namespace App\Filament\Traits;

use App\Services\OrganFilterService;
use Illuminate\Database\Eloquent\Builder;

trait HasOrganFiltering
{
    /**
     * Get the organ filter service instance
     *
     * @return OrganFilterService
     */
    protected function getOrganFilterService(): OrganFilterService
    {
        return app(OrganFilterService::class);
    }

    /**
     * Apply organ-based filtering to a query
     *
     * @param Builder $query
     * @param string $organColumn The column name for organ (default: 'organ')
     * @return Builder
     */
    protected function applyOrganFilter(Builder $query, string $organColumn = 'organ'): Builder
    {
        return $this->getOrganFilterService()->applyOrganFilter($query, $organColumn);
    }

    /**
     * Get filtered count for a model
     *
     * @param string $model The model class name
     * @param string $organColumn The column name for organ (default: 'organ')
     * @return int
     */
    protected function getFilteredCount(string $model, string $organColumn = 'organ'): int
    {
        $query = $model::query();
        return $this->applyOrganFilter($query, $organColumn)->count();
    }

    /**
     * Check if current user can view all organ data
     *
     * @return bool
     */
    protected function canViewAllOrganData(): bool
    {
        return $this->getOrganFilterService()->canViewAllData();
    }

    /**
     * Get current user's organ ID
     *
     * @return int|null
     */
    protected function getUserOrganId(): ?int
    {
        return $this->getOrganFilterService()->getUserOrganId();
    }
}
