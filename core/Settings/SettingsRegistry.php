<?php

namespace Core\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;

final class SettingsRegistry
{
    /** @var array<string, array{type: string, default: mixed, scopes: SettingScope[], group: string}> */
    private array $definitions = [];

    /**
     * @param  array<string, array{type: string, default: mixed, scopes: SettingScope[], group: string}>  $definitions
     */
    public function register(array $definitions): void
    {
        $this->definitions = [...$this->definitions, ...$definitions];
    }

    /**
     * @return array{type: string, default: mixed, scopes: SettingScope[], group: string}
     */
    public function definition(string $key): array
    {
        if (! $this->has($key)) {
            throw SettingsException::unknownKey($key);
        }

        return $this->definitions[$key];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->definitions);
    }

    public function allowsScope(string $key, SettingScope $scope): bool
    {
        return in_array($scope, $this->definition($key)['scopes'], true);
    }

    /**
     * @return string[]
     */
    public function keysInGroup(string $group): array
    {
        return array_keys(array_filter(
            $this->definitions,
            fn (array $definition): bool => $definition['group'] === $group,
        ));
    }
}
