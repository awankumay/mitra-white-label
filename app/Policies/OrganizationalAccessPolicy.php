<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;

class OrganizationalAccessPolicy
{
    public function assignUser(AuthUser $authUser): bool
    {
        return $authUser->can('assign_user_to_unit');
    }

    public function removeUser(AuthUser $authUser): bool
    {
        return $authUser->can('remove_user_from_unit');
    }

    public function setPrimaryUnit(AuthUser $authUser): bool
    {
        return $authUser->can('set_primary_unit');
    }
}
