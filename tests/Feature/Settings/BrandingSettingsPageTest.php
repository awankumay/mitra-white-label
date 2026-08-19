<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\BrandingSettings;
use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Organization\Models\Organization;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandingSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions, ?Organization $organization = null): User
    {
        $role = Role::create(['name' => 'branding-tester-'.uniqid()]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        // panel_user is required for HTTP-level panel access checks (canAccessPanel(),
        // via App\Models\User's HasPanelShield trait) — mirrors the convention already
        // established in ApplicationSettingsPageTest::userWithPermissions().
        $panelRole = Role::firstOrCreate(['name' => 'panel_user']);

        $user = User::factory()->create()->assignRole([$role, $panelRole]);

        if ($organization !== null) {
            $user->organizations()->attach($organization->id);
        }

        return $user;
    }

    public function test_page_forbidden_without_view_permission(): void
    {
        $user = $this->userWithPermissions([], Organization::factory()->create());

        $this->actingAs($user)
            ->get(BrandingSettings::getUrl())
            ->assertForbidden();
    }

    public function test_page_accessible_with_view_permission(): void
    {
        $user = $this->userWithPermissions(['view:branding'], Organization::factory()->create());

        $this->actingAs($user)
            ->get(BrandingSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_mount_fills_form_from_organization_tier_of_users_organization(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithPermissions(['view:branding'], $org);
        app(SettingsRepository::class)->set('branding.company_name', 'PT Sudah Diisi', SettingScope::Organization, $org->id);

        $this->actingAs($user);

        Livewire::test(BrandingSettings::class)
            ->assertSchemaStateSet(['branding_company_name' => 'PT Sudah Diisi']);
    }

    public function test_save_persists_to_organization_scope_not_system(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithPermissions(['view:branding', 'update:branding'], $org);
        $this->actingAs($user);

        Livewire::test(BrandingSettings::class)
            ->fillForm([
                'branding_company_name' => 'PT Baru',
                'branding_footer_text' => 'Copyright PT Baru',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $repository = app(SettingsRepository::class);
        $this->assertSame('PT Baru', $repository->getForScope('branding.company_name', SettingScope::Organization, $org->id));
        $this->assertNull($repository->getForScope('branding.company_name', SettingScope::System));
    }

    public function test_save_deletes_old_logo_file_when_replaced(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = $this->userWithPermissions(['view:branding', 'update:branding'], $org);
        Storage::disk('public')->put('brand/old-logo.png', 'fake-content');
        app(SettingsRepository::class)->set('branding.logo', 'brand/old-logo.png', SettingScope::Organization, $org->id);
        $this->actingAs($user);

        Livewire::test(BrandingSettings::class)
            // Filament's FileUploadStateCast::get() resolves a single-file field's
            // dehydrated value via Arr::first() over its raw keyed state. mount()
            // pre-fills branding_logo with the existing 'brand/old-logo.png' entry
            // (keyed by a generated UUID), so filling in a new UploadedFile only
            // *appends* a second entry — Arr::first() would still resolve to the old
            // one at save() time. Clearing the raw field first empties that keyed
            // state so the newly uploaded file becomes the sole (and thus first)
            // entry, matching how the real replace-in-browser flow behaves.
            ->set('data.branding_logo', [])
            ->fillForm(['branding_logo' => UploadedFile::fake()->image('new-logo.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('brand/old-logo.png');
    }

    public function test_save_never_deletes_old_value_outside_brand_directory(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = $this->userWithPermissions(['view:branding', 'update:branding'], $org);
        Storage::disk('public')->put('outside/sensitive.txt', 'do-not-delete');
        // Simulates a previously-crafted/legacy stored value for this key that does not
        // live under this page's own FileUpload directory ('brand/') — this is what a
        // malicious Livewire payload on a prior save could have produced.
        app(SettingsRepository::class)->set('branding.logo', 'outside/sensitive.txt', SettingScope::Organization, $org->id);
        $this->actingAs($user);

        Livewire::test(BrandingSettings::class)
            ->set('data.branding_logo', [])
            ->fillForm(['branding_logo' => UploadedFile::fake()->image('new-logo.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertExists('outside/sensitive.txt');
    }

    public function test_save_forbidden_without_update_permission(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithPermissions(['view:branding'], $org);
        $this->actingAs($user);

        // abort_unless(..., 403) inside a Livewire action throws an HttpException, but
        // Livewire::test()->call() catches it internally and exposes it as an
        // assertable response rather than letting it propagate to the test — mirrors
        // ApplicationSettingsPageTest::test_save_forbidden_without_update_permission().
        Livewire::test(BrandingSettings::class)
            ->fillForm(['branding_company_name' => 'Tidak Boleh Simpan'])
            ->call('save')
            ->assertForbidden();

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('branding.company_name', SettingScope::Organization, $org->id));
    }

    public function test_save_blocked_when_no_organization_context(): void
    {
        $user = $this->userWithPermissions(['view:branding', 'update:branding']);
        $this->actingAs($user);

        Livewire::test(BrandingSettings::class)
            ->fillForm(['branding_company_name' => 'Tanpa Organisasi'])
            ->call('save')
            ->assertForbidden();
    }
}
