<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('role:view_any');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:view');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('role:create');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:update');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:delete');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:restore');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:force_delete');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('role:force_delete_any');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('role:restore_any');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('role:replicate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('role:reorder');
    }
}
