<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalUnitScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithUnitPermission(): User
    {
        Permission::firstOrCreate(['name' => 'view:organizational_unit']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('view:organizational_unit');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_view_assigned_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $user = $this->userWithUnitPermission();
        $user->units()->attach($unit->id);

        $this->assertTrue($user->can('view', $unit));
    }

    public function test_user_cannot_view_cross_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $user = $this->userWithUnitPermission();

        $this->assertFalse($user->can('view', $unit));
    }

    public function test_super_admin_can_view_any_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->can('view', $unit));
    }
}
