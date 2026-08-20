<?php

namespace Tests\Feature\Organization;

use Core\Database\Seeders\OrganizationSeeder;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_default_organization_and_root_unit(): void
    {
        $this->seed(OrganizationSeeder::class);

        $this->assertDatabaseHas('organizations', ['name' => config('app.name')]);
        $organization = Organization::where('name', config('app.name'))->first();

        $this->assertNotNull($organization);
        $this->assertDatabaseHas('organizational_units', [
            'organization_id' => $organization->id,
            'name' => 'Head Office',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(OrganizationSeeder::class);
        $this->seed(OrganizationSeeder::class);

        $this->assertSame(1, Organization::where('name', config('app.name'))->count());
        $organization = Organization::where('name', config('app.name'))->first();
        $this->assertSame(1, OrganizationalUnit::where('organization_id', $organization->id)->count());
    }
}
