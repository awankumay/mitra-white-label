<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class DeleteOrganizationAction
{
    public function handle(Organization $organization): void
    {
        $organization->delete();
    }
}
