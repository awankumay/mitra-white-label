<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Core\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithOrgPermission(): User
    {
        Permission::firstOrCreate(['name' => 'update:organization']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('update:organization');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_update_assigned_organization(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithOrgPermission();
        $user->organizations()->attach($org->id);

        $this->assertTrue($user->can('update', $org));
    }

    public function test_user_cannot_update_unassigned_organization(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithOrgPermission();

        $this->assertFalse($user->can('update', $org));
    }

    public function test_super_admin_can_update_any_organization(): void
    {
        $org = Organization::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->can('update', $org));
    }
}
