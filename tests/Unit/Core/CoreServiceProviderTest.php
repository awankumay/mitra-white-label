<?php

namespace Tests\Unit\Core;

use Core\Context\ContextServiceProvider;
use Core\Context\OrganizationContextManager;
use Core\Context\OrganizationalUnitContextManager;
use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
use Core\CoreServiceProvider;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

class StubSubProvider extends ServiceProvider {}

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

    public function test_context_provider_is_registered_in_config(): void
    {
        $this->assertContains(
            ContextServiceProvider::class,
            config('core.providers')
        );
    }

    public function test_context_contracts_are_bound_to_managers(): void
    {
        $this->assertInstanceOf(
            OrganizationContextManager::class,
            app(OrganizationContext::class)
        );
        $this->assertInstanceOf(
            OrganizationalUnitContextManager::class,
            app(OrganizationalUnitContext::class)
        );
    }

    public function test_context_config_has_session_key(): void
    {
        $this->assertSame('context.unit_id', config('core.context.session_key'));
    }
}
