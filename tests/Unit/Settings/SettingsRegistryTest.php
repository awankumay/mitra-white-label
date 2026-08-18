<?php

namespace Tests\Unit\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use PHPUnit\Framework\TestCase;

class SettingsRegistryTest extends TestCase
{
    private function registry(): SettingsRegistry
    {
        $registry = new SettingsRegistry;
        $registry->register([
            'app.name' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
        ]);

        return $registry;
    }

    public function test_has_returns_true_for_registered_key(): void
    {
        $this->assertTrue($this->registry()->has('app.name'));
        $this->assertFalse($this->registry()->has('app.unknown'));
    }

    public function test_definition_returns_registered_definition(): void
    {
        $definition = $this->registry()->definition('app.timezone');

        $this->assertSame('string', $definition['type']);
        $this->assertSame('Asia/Jakarta', $definition['default']);
    }

    public function test_definition_throws_for_unknown_key(): void
    {
        $this->expectException(SettingsException::class);

        $this->registry()->definition('app.unknown');
    }

    public function test_allows_scope_checks_whitelist(): void
    {
        $registry = $this->registry();

        $this->assertTrue($registry->allowsScope('app.timezone', SettingScope::User));
        $this->assertFalse($registry->allowsScope('app.name', SettingScope::User));
    }

    public function test_keys_in_group_filters_by_group(): void
    {
        $this->assertSame(['app.name', 'app.timezone'], $this->registry()->keysInGroup('application'));
    }

    public function test_keys_in_group_returns_empty_for_unknown_group(): void
    {
        $this->assertSame([], $this->registry()->keysInGroup('security'));
    }
}
