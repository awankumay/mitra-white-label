<?php

namespace App\Policies;

use App\Models\User;
use Core\Support\Scope;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:user');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view:user');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:user');
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('update:user')
            && $this->sharesScope($authUser, $user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('delete:user')
            && $this->sharesScope($authUser, $user);
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('restore:user')
            && $this->sharesScope($authUser, $user);
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('force_delete:user')
            && $this->sharesScope($authUser, $user);
    }

    private function sharesScope(AuthUser $authUser, User $target): bool
    {
        if (Scope::isSuperAdmin($authUser)) {
            return true;
        }

        $authUnitIds = $authUser->units()->pluck('organizational_units.id');
        $targetUnitIds = $target->units()->pluck('organizational_units.id');

        if ($authUnitIds->intersect($targetUnitIds)->isNotEmpty()) {
            return true;
        }

        $authOrgIds = $authUser->organizations()->pluck('organizations.id');
        $targetOrgIds = $target->organizations()->pluck('organizations.id');

        return $authOrgIds->intersect($targetOrgIds)->isNotEmpty();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any:user');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any:user');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('replicate:user');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:user');
    }
}
