<?php

namespace Tests\Unit\Authorization;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_has_parent_and_children_relations(): void
    {
        $parent = Role::create(['name' => 'manager']);
        $child = Role::create(['name' => 'staff', 'parent_role_id' => $parent->id]);

        $this->assertSame($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('id', $child->id));
    }

    public function test_ancestors_returns_root_first_chain(): void
    {
        $top = Role::create(['name' => 'administrator']);
        $manager = Role::create(['name' => 'manager', 'parent_role_id' => $top->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);

        $ancestors = $staff->ancestors();

        $this->assertSame([$manager->id, $top->id], $ancestors->pluck('id')->all());
    }

    public function test_ancestors_is_cycle_safe(): void
    {
        $a = Role::create(['name' => 'a']);
        $b = Role::create(['name' => 'b', 'parent_role_id' => $a->id]);
        $a->update(['parent_role_id' => $b->id]); // cycle a<->b

        $a->refresh();
        $b->refresh();

        $this->assertCount(1, $a->ancestors());   // visits b once, stops before infinite loop
        $this->assertCount(1, $b->ancestors());
    }
}
