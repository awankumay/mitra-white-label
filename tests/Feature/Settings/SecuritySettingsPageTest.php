<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\SecuritySettings;
use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'security-settings-tester-'.uniqid()]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $panelRole = Role::firstOrCreate(['name' => 'panel_user']);

        return User::factory()->create()->assignRole([$role, $panelRole]);
    }

    public function test_page_accessible_with_view_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertSuccessful();
    }

    public function test_page_forbidden_without_view_permission(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertForbidden();
    }

    public function test_save_persists_values_via_settings_repository(): void
    {
        $user = $this->userWithPermissions(['view:settings', 'update:settings']);
        $this->actingAs($user);

        Livewire::test(SecuritySettings::class)
            ->fillForm([
                'security_two_factor_required' => true,
                'security_password_min_length' => 10,
                'security_password_require_complexity' => false,
            ])
            // livewire 4.4.1: fillForm state is lost before save(); apply via set() so the values stick
            ->set('data.security_two_factor_required', true)
            ->set('data.security_password_min_length', 10)
            ->set('data.security_password_require_complexity', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $repository = app(SettingsRepository::class);

        $this->assertSame(true, $repository->getForScope('security.two_factor_required', SettingScope::System));
        $this->assertSame(10, $repository->getForScope('security.password_min_length', SettingScope::System));
        $this->assertSame(false, $repository->getForScope('security.password_require_complexity', SettingScope::System));
    }

    public function test_save_forbidden_without_update_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);
        $this->actingAs($user);

        Livewire::test(SecuritySettings::class)
            ->fillForm([
                'security_two_factor_required' => true,
                'security_password_min_length' => 10,
                'security_password_require_complexity' => false,
            ])
            ->set('data.security_two_factor_required', true)
            ->set('data.security_password_min_length', 10)
            ->set('data.security_password_require_complexity', false)
            ->call('save')
            ->assertForbidden();

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('security.two_factor_required', SettingScope::System));
    }

    public function test_save_rejects_out_of_range_password_min_length(): void
    {
        $user = $this->userWithPermissions(['view:settings', 'update:settings']);
        $this->actingAs($user);

        Livewire::test(SecuritySettings::class)
            ->fillForm([
                'security_two_factor_required' => true,
                'security_password_min_length' => 3,
                'security_password_require_complexity' => false,
            ])
            // livewire 4.4.1: fillForm state is lost before save(); apply via set() so the values stick
            ->set('data.security_two_factor_required', true)
            ->set('data.security_password_min_length', 3)
            ->set('data.security_password_require_complexity', false)
            ->call('save')
            // assertHasFormErrors() prefixes the schema state path ('data'), so pass the bare field name
            ->assertHasFormErrors(['security_password_min_length']);

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('security.password_min_length', SettingScope::System));
    }
}
