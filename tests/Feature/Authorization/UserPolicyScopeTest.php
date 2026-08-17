<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPolicyScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithUpdatePermission(): User
    {
        Permission::firstOrCreate(['name' => 'update:user']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('update:user');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_update_user_in_same_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $authUser = $this->userWithUpdatePermission();
        $target = User::factory()->create();

        $authUser->units()->attach($unit->id);
        $target->units()->attach($unit->id);

        $this->assertTrue($authUser->can('update', $target));
    }

    public function test_user_cannot_update_user_in_other_unit(): void
    {
        $org = Organization::factory()->create();
        $unitA = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $unitB = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $authUser = $this->userWithUpdatePermission();
        $target = User::factory()->create();

        $authUser->units()->attach($unitA->id);
        $target->units()->attach($unitB->id);

        $this->assertFalse($authUser->can('update', $target));
    }

    public function test_super_admin_can_update_any_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $authUser = User::factory()->create();
        $authUser->assignRole($role);
        $target = User::factory()->create();

        $this->assertTrue($authUser->can('update', $target));
    }
}
