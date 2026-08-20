<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ApplicationSettings;
use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'settings-tester-'.uniqid()]);

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
            ->get(ApplicationSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_page_forbidden_without_view_permission(): void
    {
        // panel_user grants panel reachability without granting view:settings, so a 403 here
        // can only come from ApplicationSettings::canAccess() itself, not panel access.
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(ApplicationSettings::getUrl())
            ->assertForbidden();
    }

    public function test_save_persists_values_via_settings_repository(): void
    {
        $user = $this->userWithPermissions(['view:settings', 'update:settings']);
        $this->actingAs($user);

        Livewire::test(ApplicationSettings::class)
            ->fillForm([
                'app_name' => 'Mitra Baru',
                'app_locale' => 'en',
                'app_timezone' => 'Asia/Makassar',
            ])
            // livewire 4.4.1: fillForm state is lost before save(); apply via set() so the values stick
            ->set('data.app_name', 'Mitra Baru')
            ->set('data.app_locale', 'en')
            ->set('data.app_timezone', 'Asia/Makassar')
            ->call('save')
            ->assertHasNoFormErrors();

        $repository = app(SettingsRepository::class);

        $this->assertSame('Mitra Baru', $repository->getForScope('app.name', SettingScope::System));
        $this->assertSame('en', $repository->getForScope('app.locale', SettingScope::System));
        $this->assertSame('Asia/Makassar', $repository->getForScope('app.timezone', SettingScope::System));
    }

    public function test_save_forbidden_without_update_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);
        $this->actingAs($user);

        Livewire::test(ApplicationSettings::class)
            ->fillForm([
                'app_name' => 'Should Not Save',
                'app_locale' => 'en',
                'app_timezone' => 'Asia/Makassar',
            ])
            ->call('save')
            ->assertForbidden();

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('app.name', SettingScope::System));
    }
}
