<?php

declare(strict_types=1);

namespace Core\Organization\Policies;

use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrganizationalUnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:organizational_unit');
    }

    public function view(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('view:organizational_unit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:organizational_unit');
    }

    public function update(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('update:organizational_unit');
    }

    public function delete(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('delete:organizational_unit');
    }

    public function restore(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('restore:organizational_unit');
    }

    public function forceDelete(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('force_delete:organizational_unit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any:organizational_unit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any:organizational_unit');
    }

    public function replicate(AuthUser $authUser, OrganizationalUnit $organizationalUnit): bool
    {
        return $authUser->can('replicate:organizational_unit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:organizational_unit');
    }
}
