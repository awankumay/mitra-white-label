<?php

namespace Tests\Feature\Scope;

use App\Models\User;
use App\Policies\ScopePolicy;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScopePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithPermission(string $permission): User
    {
        $role = Role::create(['name' => 'editor']);
        $role->givePermissionTo(Permission::create(['name' => $permission]));

        return User::factory()->create()->assignRole($role);
    }

    public function test_view_allowed_with_permission_and_assigned_unit(): void
    {
        $user = $this->makeUserWithPermission('view:organizational_unit');
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->assertTrue((new ScopePolicy)->view($user, $unit));
    }

    public function test_view_denied_without_permission(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->assertFalse((new ScopePolicy)->view($user, $unit));
    }

    public function test_view_denied_when_unit_not_assigned(): void
    {
        $user = $this->makeUserWithPermission('view:organizational_unit');
        $unit = OrganizationalUnit::factory()->create(); // not assigned

        $this->assertFalse((new ScopePolicy)->view($user, $unit));
    }

    public function test_view_allowed_for_super_admin_without_assignment(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        $user = $this->makeUserWithPermission('view:organizational_unit')->assignRole('super_admin');
        $unit = OrganizationalUnit::factory()->create(); // not assigned

        $this->assertTrue((new ScopePolicy)->view($user, $unit));
    }
}
