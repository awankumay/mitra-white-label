<?php

namespace Tests\Feature\Organization;

use App\Actions\Organization\AssignUserToUnitAction;
use App\Actions\Organization\SetPrimaryUnitAction;
use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_assignment_flow(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $headOffice = OrganizationalUnit::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Head Office',
        ]);
        $branch = OrganizationalUnit::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Branch Bandung',
            'parent_id' => $headOffice->id,
        ]);

        app(AssignUserToUnitAction::class)->handle($user, $headOffice);
        app(AssignUserToUnitAction::class)->handle($user, $branch);
        app(SetPrimaryUnitAction::class)->handle($user, $headOffice);

        $this->assertCount(2, $user->fresh()->units);
        $this->assertSame($headOffice->id, $user->fresh()->primary_organizational_unit_id);
    }

    public function test_cannot_set_primary_to_unassigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SetPrimaryUnitAction::class)->handle($user, $unit);
    }
}
