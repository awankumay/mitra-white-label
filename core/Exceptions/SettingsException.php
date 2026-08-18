<?php

namespace Core\Exceptions;

use Core\Settings\Enums\SettingScope;

class SettingsException extends CoreException
{
    public static function unknownKey(string $key): self
    {
        return new self("Settings key [{$key}] is not registered.");
    }

    public static function invalidScope(string $key, SettingScope $scope): self
    {
        return new self("Settings key [{$key}] does not allow scope [{$scope->value}].");
    }
}
