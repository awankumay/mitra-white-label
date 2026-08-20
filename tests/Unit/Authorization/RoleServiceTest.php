<?php

namespace Tests\Unit\Authorization;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoleService;
    }

    public function test_permissions_for_includes_inherited_ancestor_permissions(): void
    {
        $top = Role::create(['name' => 'administrator']);
        $manager = Role::create(['name' => 'manager', 'parent_role_id' => $top->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);

        $top->givePermissionTo(Permission::create(['name' => 'view:organization']));
        $staff->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $names = $this->service->permissionsFor($user)->pluck('name');

        $this->assertTrue($names->contains('view:user'));
        $this->assertTrue($names->contains('view:organization'));   // inherited via manager→administrator
    }

    public function test_user_has_permission_checks_inheritance(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);
        $manager->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $this->assertTrue($this->service->userHasPermission($user, 'view:user'));
        $this->assertFalse($this->service->userHasPermission($user, 'delete:user'));
    }

    public function test_super_admin_has_every_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($this->service->userHasPermission($user, 'anything:at_all'));
    }

    public function test_descendants_of_returns_whole_subtree(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $supervisor = Role::create(['name' => 'supervisor', 'parent_role_id' => $manager->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $supervisor->id]);

        $ids = $this->service->descendantsOf($manager)->pluck('id');

        $this->assertTrue($ids->contains($supervisor->id));
        $this->assertTrue($ids->contains($staff->id));
    }

    public function test_would_create_cycle_detects_self_and_descendant(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $supervisor = Role::create(['name' => 'supervisor', 'parent_role_id' => $manager->id]);

        $this->assertTrue($this->service->wouldCreateCycle($manager, $manager->id));       // self
        $this->assertTrue($this->service->wouldCreateCycle($manager, $supervisor->id));    // descendant
        $this->assertFalse($this->service->wouldCreateCycle($manager, null));              // valid detach
    }
}
