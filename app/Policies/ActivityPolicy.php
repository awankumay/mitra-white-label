<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('activity:view_any');
    }

    public function view(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:view');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('activity:create');
    }

    public function update(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:update');
    }

    public function delete(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:delete');
    }

    public function restore(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:restore');
    }

    public function forceDelete(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:force_delete');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('activity:force_delete_any');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('activity:restore_any');
    }

    public function replicate(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('activity:replicate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('activity:reorder');
    }
}
