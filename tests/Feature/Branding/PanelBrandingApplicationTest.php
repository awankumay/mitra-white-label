<?php

namespace Tests\Feature\Branding;

use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Organization\Models\Organization;
use Core\Settings\Enums\SettingScope;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelBrandingApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_name_falls_back_to_sole_organization_when_unauthenticated(): void
    {
        $org = Organization::factory()->create();
        app(SettingsRepository::class)->set('branding.company_name', 'PT Anon Branding', SettingScope::Organization, $org->id);

        $this->assertSame('PT Anon Branding', Filament::getPanel('admin')->getBrandName());
    }

    public function test_brand_name_uses_authenticated_users_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id);
        app(SettingsRepository::class)->set('branding.company_name', 'PT User Login', SettingScope::Organization, $org->id);

        $this->actingAs($user);

        $this->assertSame('PT User Login', Filament::getPanel('admin')->getBrandName());
    }

    public function test_brand_name_falls_back_to_config_app_name_when_no_branding_set(): void
    {
        $this->assertSame((string) config('app.name'), (string) Filament::getPanel('admin')->getBrandName());
    }

    public function test_colors_include_primary_hex_when_branding_set_without_dropping_secondary_default(): void
    {
        $org = Organization::factory()->create();
        app(SettingsRepository::class)->set('branding.primary_color', '#112233', SettingScope::Organization, $org->id);

        $this->actingAs(tap(User::factory()->create(), fn (User $user) => $user->organizations()->attach($org->id)));

        $this->assertSame(Color::hex('#112233'), Filament::getPanel('admin')->getColors()['primary']);
    }

    public function test_colors_keep_default_primary_when_no_branding_color_set(): void
    {
        $this->assertSame(Color::Taupe, Filament::getPanel('admin')->getColors()['primary']);
    }
}
