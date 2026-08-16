<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\OrganizationalUnit;

final class DeleteOrganizationalUnitAction
{
    public function handle(OrganizationalUnit $unit): void
    {
        $unit->delete();
    }
}
