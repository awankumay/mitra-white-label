<?php

namespace Tests\Feature\Organization;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Tests\TestCase;

class OrganizationResourceTest extends TestCase
{
    public function test_organization_resource_exists(): void
    {
        $this->assertSame(
            Organization::class,
            OrganizationResource::getModel()
        );
    }

    public function test_organizational_unit_resource_exists(): void
    {
        $this->assertSame(
            OrganizationalUnit::class,
            OrganizationalUnitResource::getModel()
        );
    }
}
