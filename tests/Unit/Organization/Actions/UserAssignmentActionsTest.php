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

    public function test_removing_non_primary_unit_keeps_primary(): void
    {
        $user = User::factory()->create();
        $primary = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach([$primary->id, $other->id]);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        app(RemoveUserFromUnitAction::class)->handle($user, $other);

        $fresh = $user->fresh();
        $this->assertSame($primary->id, $fresh->primary_organizational_unit_id);
        $this->assertFalse($fresh->units()->where('organizational_units.id', $other->id)->exists());
    }

    public function test_removing_primary_unit_nulls_primary(): void
    {
        $user = User::factory()->create();
        $primary = OrganizationalUnit::factory()->create();
        $user->units()->attach($primary);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        app(RemoveUserFromUnitAction::class)->handle($user, $primary);

        $fresh = $user->fresh();
        $this->assertNull($fresh->primary_organizational_unit_id);
        $this->assertFalse($fresh->units()->where('organizational_units.id', $primary->id)->exists());
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
