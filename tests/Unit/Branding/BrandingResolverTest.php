<?php

namespace Tests\Unit\Branding;

use Core\Branding\BrandingResolver;
use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Organization\Models\Organization;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_organization_tier_value_when_context_available(): void
    {
        $org = Organization::factory()->create();
        app(OrganizationContext::class)->set($org);
        app(SettingsRepository::class)->set('branding.company_name', 'PT Org Aktif', SettingScope::Organization, $org->id);

        $this->assertSame('PT Org Aktif', app(BrandingResolver::class)->get('branding.company_name'));
    }

    public function test_get_falls_back_to_system_tier_when_organization_tier_empty(): void
    {
        $org = Organization::factory()->create();
        app(OrganizationContext::class)->set($org);
        app(SettingsRepository::class)->set('branding.company_name', 'Fallback System', SettingScope::System);

        $this->assertSame('Fallback System', app(BrandingResolver::class)->get('branding.company_name'));
    }

    public function test_get_falls_back_to_sole_organization_when_no_user_context(): void
    {
        $org = Organization::factory()->create();
        app(SettingsRepository::class)->set('branding.company_name', 'PT Anon Org', SettingScope::Organization, $org->id);

        // Tidak ada user login sama sekali -> OrganizationContext::organizationId() null.
        $this->assertSame('PT Anon Org', app(BrandingResolver::class)->get('branding.company_name'));
    }

    public function test_get_returns_registry_default_when_no_organization_exists(): void
    {
        $this->assertNull(app(BrandingResolver::class)->get('branding.company_name'));
    }

    public function test_url_returns_null_when_path_empty(): void
    {
        $this->assertNull(app(BrandingResolver::class)->url('branding.logo'));
    }

    public function test_url_returns_disk_url_when_path_set(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        app(OrganizationContext::class)->set($org);
        app(SettingsRepository::class)->set('branding.logo', 'brand/logo.png', SettingScope::Organization, $org->id);

        $this->assertSame(
            Storage::disk('public')->url('brand/logo.png'),
            app(BrandingResolver::class)->url('branding.logo'),
        );
    }
}
