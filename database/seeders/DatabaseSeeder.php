<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Core\Database\Seeders\OrganizationSeeder;
use Illuminate\Database\Seeder;

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

        $administrator = Role::where('name', 'administrator')->first();
        $manager = Role::where('name', 'manager')->first();
        $supervisor = Role::where('name', 'supervisor')->first();
        $staff = Role::where('name', 'staff')->first();
        $viewer = Role::where('name', 'viewer')->first();

        $supervisor->update(['parent_role_id' => $manager->id]);
        $staff->update(['parent_role_id' => $supervisor->id]);
        $viewer->update(['parent_role_id' => $staff->id]);
        $manager->update(['parent_role_id' => $administrator->id]);
        $administrator->update(['parent_role_id' => null]);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->assignRole('super_admin');

        $this->call(OrganizationSeeder::class);
    }
}
