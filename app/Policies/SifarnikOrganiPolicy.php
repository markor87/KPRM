<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Шифарник органа и мапирање приступа подређеним органима.
 *
 * Намерно кроз обичне Shield дозволе, а не тврдо везано за Super Admina — тако се право
 * мапирања касније додељује било којој улози кроз „Улоге → Ресурси", без измене кода.
 * Миграција дозволе иницијално даје само улози Super Admin.
 */
class SifarnikOrganiPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SifarnikOrgani');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:SifarnikOrgani');
    }

    /**
     * Органи се не додају ни бришу кроз апликацију — шифарник је задат, а брисање органа
     * би оставило записе о радним местима без органа.
     */
    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:SifarnikOrgani');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:SifarnikOrgani');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SifarnikOrgani');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SifarnikOrgani');
    }
}
