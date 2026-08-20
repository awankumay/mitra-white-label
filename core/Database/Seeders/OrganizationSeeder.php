<?php

namespace Core\Database\Seeders;

use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => config('app.name')],
            ['name' => config('app.name')]
        );

        OrganizationalUnit::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Head Office',
            ],
            [
                'type' => OrganizationalUnitType::HEAD_OFFICE,
                'parent_id' => null,
            ]
        );
    }
}
