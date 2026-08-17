<?php

namespace App\Policies;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScopePolicy
{
    use HandlesAuthorization;

    public function view(User $authUser, OrganizationalUnit $unit): bool
    {
        return $authUser->can('view:organizational_unit')
            && Scope::can($authUser, $unit->id);
    }
}
