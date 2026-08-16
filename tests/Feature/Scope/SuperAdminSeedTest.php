<?php

namespace Tests\Feature\Scope;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_role_is_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::where('name', 'super_admin')->exists());
    }
}
