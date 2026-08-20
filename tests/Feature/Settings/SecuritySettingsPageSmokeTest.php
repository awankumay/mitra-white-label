<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\SecuritySettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_view_permission(): void
    {
        $role = Role::create(['name' => 'security-smoke-tester-'.uniqid()]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view:settings']));
        $user = User::factory()->create()->assignRole([$role, Role::firstOrCreate(['name' => 'panel_user'])]);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertSuccessful();
    }
}
