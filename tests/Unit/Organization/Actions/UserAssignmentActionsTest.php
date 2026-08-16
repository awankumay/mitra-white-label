<?php

namespace Tests\Unit\Organization\Actions;

use App\Actions\Organization\AssignUserToUnitAction;
use App\Actions\Organization\RemoveUserFromUnitAction;
use App\Actions\Organization\SetPrimaryUnitAction;
use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_user_to_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        app(AssignUserToUnitAction::class)->handle($user, $unit);

        $this->assertTrue($user->units()->where('organizational_units.id', $unit->id)->exists());
    }

    public function test_assign_does_not_remove_existing_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach($unitA);

        app(AssignUserToUnitAction::class)->handle($user, $unitB);

        $this->assertTrue($user->units()->where('organizational_units.id', $unitA->id)->exists());
        $this->assertTrue($user->units()->where('organizational_units.id', $unitB->id)->exists());
    }

    public function test_remove_user_from_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit);

        app(RemoveUserFromUnitAction::class)->handle($user, $unit);

        $this->assertFalse($user->units()->where('organizational_units.id', $unit->id)->exists());
    }

    public function test_set_primary_unit_requires_assignment(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SetPrimaryUnitAction::class)->handle($user, $unit);
    }

    public function test_set_primary_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit);

        app(SetPrimaryUnitAction::class)->handle($user, $unit);

        $this->assertSame($unit->id, $user->fresh()->primary_organizational_unit_id);
    }

    public function test_user_has_organizations_relation(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $this->assertTrue($user->organizations()->where('organizations.id', $organization->id)->exists());
    }
}
