<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PodaciORadnomMestu;
use Illuminate\Auth\Access\HandlesAuthorization;

class PodaciORadnomMestuPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PodaciORadnomMestu');
    }

    public function view(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('View:PodaciORadnomMestu');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PodaciORadnomMestu');
    }

    public function update(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Update:PodaciORadnomMestu');
    }

    public function delete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Delete:PodaciORadnomMestu');
    }

    public function restore(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Restore:PodaciORadnomMestu');
    }

    public function forceDelete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('ForceDelete:PodaciORadnomMestu');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PodaciORadnomMestu');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PodaciORadnomMestu');
    }

    public function replicate(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Replicate:PodaciORadnomMestu');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PodaciORadnomMestu');
    }

}