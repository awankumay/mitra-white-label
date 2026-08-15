<?php

declare(strict_types=1);

namespace App\Policies;

use FilamentWhiteLabel\Models\WhiteLabelSettings;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class WhiteLabelSettingsPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:white_label_settings');
    }

    public function view(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('view:white_label_settings');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:white_label_settings');
    }

    public function update(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('update:white_label_settings');
    }

    public function delete(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('delete:white_label_settings');
    }

    public function restore(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('restore:white_label_settings');
    }

    public function forceDelete(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('force_delete:white_label_settings');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any:white_label_settings');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any:white_label_settings');
    }

    public function replicate(AuthUser $authUser, WhiteLabelSettings $whiteLabelSettings): bool
    {
        return $authUser->can('replicate:white_label_settings');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:white_label_settings');
    }
}
