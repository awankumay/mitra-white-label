<?php

namespace Tests\Unit\Scope;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Support\Scope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_organization_when_assigned(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id);

        $this->assertTrue(Scope::canAccessOrganization($user, $org->id));
    }

    public function test_can_access_organization_false_when_not_assigned(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse(Scope::canAccessOrganization($user, $org->id));
    }

    public function test_can_access_organization_false_when_null(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Scope::canAccessOrganization($user, null));
    }

    public function test_super_admin_can_access_any_organization(): void
    {
        $org = Organization::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue(Scope::canAccessOrganization($user, $org->id));
    }
}
