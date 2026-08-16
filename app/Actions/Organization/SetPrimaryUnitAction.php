<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;

final class SetPrimaryUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $assigned = $user->units()->where('organizational_units.id', $unit->id)->exists();

        if (! $assigned) {
            throw OrganizationException::invalidAssignment(
                'Unit utama harus merupakan unit yang di-assign ke pengguna.'
            );
        }

        $user->update(['primary_organizational_unit_id' => $unit->id]);
    }
}
