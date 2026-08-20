<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class UpdateOrganizationAction
{
    public function handle(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }
}
