<?php

namespace Tests\Feature\Settings;

use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsCacheInvalidationTest extends TestCase
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
        ]);
    }

    public function test_get_is_cached_and_does_not_reflect_direct_db_writes(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);

        $this->assertSame('First', $repository->get('app.name'));

        // Ubah langsung di DB, bypass repository → cache belum tahu.
        DB::table('settings')
            ->where('key', 'app.name')
            ->whereNull('organization_id')
            ->whereNull('organizational_unit_id')
            ->whereNull('user_id')
            ->update(['value' => json_encode('Changed Directly')]);

        $this->assertSame('First', $repository->get('app.name'));
    }

    public function test_set_invalidates_cache_immediately(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);
        $this->assertSame('First', $repository->get('app.name'));

        $repository->set('app.name', 'Second', SettingScope::System);

        $this->assertSame('Second', $repository->get('app.name'));
    }

    public function test_forget_invalidates_cache_and_falls_back_to_default(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);
        $this->assertSame('First', $repository->get('app.name'));

        $repository->forget('app.name', SettingScope::System);

        $this->assertSame('Default App', $repository->get('app.name'));
    }
}
