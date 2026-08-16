<?php

namespace Tests\Feature\Context;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContextFilamentTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view_dashboard']));

        return User::factory()->create()->assignRole($role);
    }

    public function test_switcher_renders_when_user_has_multiple_units(): void
    {
        $user = $this->adminUser();
        $unitA = OrganizationalUnit::factory()->create(['name' => 'Head Office']);
        $unitB = OrganizationalUnit::factory()->create(['name' => 'Branch Bandung']);
        $user->units()->attach([$unitA->id, $unitB->id]);
        session(['context.unit_id' => $unitA->id]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('Head Office')
            ->assertSee('Branch Bandung');
    }

    public function test_switcher_hidden_when_user_has_single_unit(): void
    {
        $user = $this->adminUser();
        $unit = OrganizationalUnit::factory()->create(['name' => 'Head Office']);
        $user->units()->attach($unit->id);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful()
            ->assertDontSee('unit-switcher-active');
    }

    public function test_switcher_marks_active_unit(): void
    {
        $user = $this->adminUser();
        $unitA = OrganizationalUnit::factory()->create(['name' => 'Head Office']);
        $unitB = OrganizationalUnit::factory()->create(['name' => 'Branch Bandung']);
        $user->units()->attach([$unitA->id, $unitB->id]);
        session(['context.unit_id' => $unitA->id]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('unit-switcher-active');
    }
}
