<?php

namespace Core\Contracts;

use Core\Settings\Enums\SettingScope;

interface SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed;

    public function getForScope(string $key, SettingScope $scope, ?string $scopeId = null): mixed;

    public function set(string $key, mixed $value, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;

    public function forget(string $key, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;
}
