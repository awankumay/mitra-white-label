<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class RemoveUserFromUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->detach($unit->id);
    }
}
