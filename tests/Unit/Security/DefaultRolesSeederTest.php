<?php

namespace Tests\Unit\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DefaultRolesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_default_roles(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
        $this->assertDatabaseHas('roles', ['name' => 'administrator']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'supervisor']);
        $this->assertDatabaseHas('roles', ['name' => 'staff']);
        $this->assertDatabaseHas('roles', ['name' => 'viewer']);
        $this->assertDatabaseHas('roles', ['name' => 'panel_user']);

        $this->assertSame(7, Role::count());
    }
}
