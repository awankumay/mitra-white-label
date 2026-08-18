<?php

namespace Tests\Unit\Settings;

use Core\Contracts\OrganizationalUnitContext;
use Core\Contracts\SettingsRepository;
use Core\Exceptions\SettingsException;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsRegistry::class)->register([
            'app.name' => [
                'type' => 'string',
                'default' => 'Default App',
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
            'security.session_lifetime_minutes' => [
                'type' => 'int',
                'default' => 120,
                'scopes' => [SettingScope::System],
                'group' => 'security',
            ],
        ]);
    }

    public function test_get_returns_registry_default_when_nothing_stored(): void
    {
        $this->assertSame('Default App', app(SettingsRepository::class)->get('app.name'));
    }

    public function test_get_casts_value_to_registered_type(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('security.session_lifetime_minutes', 60, SettingScope::System);

        $this->assertSame(60, $repository->get('security.session_lifetime_minutes'));
    }

    public function test_get_throws_for_unknown_key(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->get('unknown.key');
    }

    public function test_set_throws_when_scope_not_allowed_for_key(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.name', 'X', SettingScope::User, 'some-user-id');
    }

    public function test_set_throws_when_scope_id_missing_for_non_system_scope(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.timezone', 'UTC', SettingScope::User, null);
    }

    public function test_set_throws_when_scope_id_given_for_system_scope(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.name', 'X', SettingScope::System, 'not-null');
    }

    public function test_get_for_scope_returns_null_when_not_set(): void
    {
        $this->assertNull(app(SettingsRepository::class)->getForScope('app.name', SettingScope::System));
    }

    public function test_get_for_scope_does_not_fallback_to_registry_default(): void
    {
        // Belum pernah di-set → tetap null, BUKAN 'Default App' (beda dari get()).
        $this->assertNull(app(SettingsRepository::class)->getForScope('app.name', SettingScope::System));
    }

    public function test_cascade_prefers_unit_over_system(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $repository = app(SettingsRepository::class);

        $repository->set('app.timezone', 'Asia/Jakarta', SettingScope::System);
        $repository->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unit->id);

        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame('Asia/Makassar', $repository->get('app.timezone'));
    }

    public function test_forget_removes_override_and_falls_back_to_next_tier(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $repository = app(SettingsRepository::class);

        $repository->set('app.timezone', 'Asia/Jakarta', SettingScope::System);
        $repository->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unit->id);
        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame('Asia/Makassar', $repository->get('app.timezone'));

        $repository->forget('app.timezone', SettingScope::Unit, $unit->id);

        $this->assertSame('Asia/Jakarta', $repository->get('app.timezone'));
    }
}
