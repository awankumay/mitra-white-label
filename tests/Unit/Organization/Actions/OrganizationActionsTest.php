<?php

namespace Tests\Unit\Organization\Actions;

use Core\Organization\Actions\CreateOrganizationAction;
use Core\Organization\Actions\DeleteOrganizationAction;
use Core\Organization\Actions\UpdateOrganizationAction;
use Core\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_organization(): void
    {
        $organization = app(CreateOrganizationAction::class)->handle('PT ABC Indonesia');

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertSame('PT ABC Indonesia', $organization->name);
        $this->assertDatabaseHas('organizations', ['name' => 'PT ABC Indonesia']);
    }

    public function test_update_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Old Name']);

        app(UpdateOrganizationAction::class)->handle($organization, ['name' => 'New Name']);

        $this->assertSame('New Name', $organization->fresh()->name);
    }

    public function test_delete_organization_soft_deletes(): void
    {
        $organization = Organization::factory()->create();

        app(DeleteOrganizationAction::class)->handle($organization);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }
}
