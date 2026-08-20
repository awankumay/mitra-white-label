<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class CreateOrganizationAction
{
    public function handle(string $name, ?string $createdBy = null): Organization
    {
        return Organization::create([
            'name' => $name,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
