<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class RemoveUserFromUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->detach($unit->id);

        // Jangan biarkan primary unit menunjuk unit yang sudah tidak lagi ditugaskan.
        if ($user->primary_organizational_unit_id === $unit->id) {
            $user->update(['primary_organizational_unit_id' => null]);
        }
    }
}
