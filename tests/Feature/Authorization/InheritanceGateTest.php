<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InheritanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_grants_inherited_permission_via_hierarchy(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);
        $manager->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $this->assertTrue(Gate::forUser($user)->allows('view:user'));
    }

    public function test_gate_denies_permission_not_inherited(): void
    {
        $role = Role::create(['name' => 'staff']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue(Gate::forUser($user)->denies('delete:user'));
    }
}
