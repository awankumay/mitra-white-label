<?php

namespace Tests\Feature\Organization;

use App\Models\User;
use App\Policies\OrganizationalAccessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_user_permission_granted(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'assign_user_to_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->assignUser($user));
    }

    public function test_assign_user_permission_denied(): void
    {
        $user = User::factory()->create();

        $this->assertFalse((new OrganizationalAccessPolicy)->assignUser($user));
    }

    public function test_remove_user_permission(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'remove_user_from_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->removeUser($user));
    }

    public function test_set_primary_unit_permission(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'set_primary_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->setPrimaryUnit($user));
    }
}
