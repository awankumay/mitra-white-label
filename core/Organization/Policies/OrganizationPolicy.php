<?php

declare(strict_types=1);

namespace Core\Organization\Policies;

use Core\Organization\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:organization');
    }

    public function view(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('view:organization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:organization');
    }

    public function update(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('update:organization');
    }

    public function delete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('delete:organization');
    }

    public function restore(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('restore:organization');
    }

    public function forceDelete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('force_delete:organization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any:organization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any:organization');
    }

    public function replicate(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('replicate:organization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:organization');
    }
}
