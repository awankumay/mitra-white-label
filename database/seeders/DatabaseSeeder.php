<?php

namespace Database\Seeders;

use App\Models\User;
use Core\Database\Seeders\OrganizationSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['super_admin', 'administrator', 'manager', 'supervisor', 'staff', 'viewer', 'panel_user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ])->assignRole('super_admin');

        $this->call(OrganizationSeeder::class);
    }
}
