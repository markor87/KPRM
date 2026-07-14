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
        return $authUser->can('View:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PodaciORadnomMestu');
    }

    public function update(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Update:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
    }

    public function delete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Delete:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
    }

    public function restore(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Restore:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
    }

    public function forceDelete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('ForceDelete:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
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
        return $authUser->can('Replicate:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PodaciORadnomMestu');
    }

    /**
     * Drugi sloj izolacije po organu (pored getEloquentQuery na resursu).
     * Korisnik sa ViewAny dozvolom vidi/menja sve organe; ostali samo svoj.
     * Prati istu logiku kao OrganFilterService.
     */
    private function pripadaOrganuKorisnika(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        if ($authUser->can('ViewAny:PodaciORadnomMestu')) {
            return true;
        }

        return $authUser->organ_id !== null
            && (int) $podaciORadnomMestu->organ === (int) $authUser->organ_id;
    }

}