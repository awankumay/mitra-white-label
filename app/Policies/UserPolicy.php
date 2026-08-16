<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('user:view_any');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('user:view');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('user:create');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('user:update');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('user:delete');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('user:restore');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('user:force_delete');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('user:force_delete_any');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('user:restore_any');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('user:replicate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('user:reorder');
    }
}
