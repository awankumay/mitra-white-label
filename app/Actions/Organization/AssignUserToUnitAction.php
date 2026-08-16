<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class AssignUserToUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->syncWithoutDetaching([$unit->id]);
    }
}
