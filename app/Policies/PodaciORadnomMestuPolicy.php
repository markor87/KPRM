<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PodaciORadnomMestu;
use App\Models\User;
use App\Services\OrganFilterService;
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
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu)
            && $this->smeUOrganu($authUser, $podaciORadnomMestu, 'izmena');
    }

    public function delete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Delete:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu)
            && $this->smeUOrganu($authUser, $podaciORadnomMestu, 'brisanje');
    }

    public function restore(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $authUser->can('Restore:PodaciORadnomMestu')
            && $this->pripadaOrganuKorisnika($authUser, $podaciORadnomMestu)
            && $this->smeUOrganu($authUser, $podaciORadnomMestu, 'brisanje');
    }

    public function forceDelete(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        return $this->jeSuperAdmin($authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->jeSuperAdmin($authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PodaciORadnomMestu');
    }

    /**
     * Дуплирање зависи само од дозволе из улоге и од тога да ли корисник уопште види запис —
     * прекидачи по органима га свесно не дирају.
     */
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
     * Трајно брисање („Обриши трајно") намерно је резервисано САМО за Super Admina —
     * дозволе ForceDelete/ForceDeleteAny из улога се овде свесно не узимају у обзир,
     * јер тај запис после нема одакле да се врати.
     */
    private function jeSuperAdmin(AuthUser $authUser): bool
    {
        return $authUser instanceof User && $authUser->isSuperAdmin();
    }

    /**
     * Drugi sloj izolacije po organu (pored getEloquentQuery na resursu).
     * Korisnik sa ViewAny dozvolom vidi/menja sve organe; ostali svoj organ i one podređene
     * organe koji su njihovom organu izričito dodeljeni.
     * Prati istu logiku kao OrganFilterService.
     */
    private function pripadaOrganuKorisnika(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu): bool
    {
        if ($authUser->can('ViewAny:PodaciORadnomMestu')) {
            return true;
        }

        return in_array(
            (int) $podaciORadnomMestu->organ,
            app(OrganFilterService::class)->dostupniOrganiIds($authUser),
            true,
        );
    }

    /**
     * Дозвола из улоге каже ШТА корисник сме, ова провера ГДЕ. У сопственом органу увек
     * важи; у подређеном органу само ако је одговарајући прекидач изричито укључен.
     *
     * @param 'izmena'|'brisanje' $vrsta
     */
    private function smeUOrganu(AuthUser $authUser, PodaciORadnomMestu $podaciORadnomMestu, string $vrsta): bool
    {
        if ($authUser->can('ViewAny:PodaciORadnomMestu')) {
            return true;
        }

        $servis = app(OrganFilterService::class);
        $organ = (int) $podaciORadnomMestu->organ;

        return $vrsta === 'brisanje'
            ? $servis->mozeBrisatiUOrganu($organ, $authUser)
            : $servis->mozeMenjatiUOrganu($organ, $authUser);
    }
}