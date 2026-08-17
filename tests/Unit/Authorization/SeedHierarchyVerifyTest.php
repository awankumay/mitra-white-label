<?php

namespace Tests\Unit\Authorization;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedHierarchyVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_sets_default_hierarchy_per_brief(): void
    {
        $this->seed();

        $administrator = Role::where('name', 'administrator')->first();
        $manager = Role::where('name', 'manager')->first();
        $supervisor = Role::where('name', 'supervisor')->first();
        $staff = Role::where('name', 'staff')->first();
        $viewer = Role::where('name', 'viewer')->first();
        $superAdmin = Role::where('name', 'super_admin')->first();
        $panelUser = Role::where('name', 'panel_user')->first();

        // Exactly what the seeder sets (with the manager→administrator link):
        $this->assertSame($administrator->id, $manager->parent_role_id);
        $this->assertSame($manager->id, $supervisor->parent_role_id);
        $this->assertSame($supervisor->id, $staff->parent_role_id);
        $this->assertSame($staff->id, $viewer->parent_role_id);
        $this->assertNull($administrator->parent_role_id);

        // NOT in hierarchy:
        $this->assertNull($superAdmin->parent_role_id);
        $this->assertNull($panelUser->parent_role_id);

        $this->assertCount(7, Role::all());
    }
}
