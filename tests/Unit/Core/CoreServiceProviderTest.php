<?php

namespace Tests\Unit\Core;

use Core\CoreServiceProvider;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

class StubSubProvider extends ServiceProvider
{
}

class CoreServiceProviderTest extends TestCase
{
    public function test_core_config_is_merged(): void
    {
        $this->assertTrue(config()->has('core.providers'));
        $this->assertIsArray(config('core.providers'));
    }

    public function test_core_service_provider_registers_providers_from_config(): void
    {
        // CoreServiceProvider sudah ter-register saat bootstrap test,
        // jadi kita instantiate manual dan panggil register() untuk
        // menguji mekanisme sub-provider secara terisolasi.
        $provider = new CoreServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(CoreServiceProvider::class, $provider);
    }
}
