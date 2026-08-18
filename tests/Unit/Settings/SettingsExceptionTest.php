<?php

namespace Tests\Unit\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;
use PHPUnit\Framework\TestCase;

class SettingsExceptionTest extends TestCase
{
    public function test_unknown_key_message_contains_key(): void
    {
        $exception = SettingsException::unknownKey('app.missing');

        $this->assertStringContainsString('app.missing', $exception->getMessage());
    }

    public function test_invalid_scope_message_contains_key_and_scope(): void
    {
        $exception = SettingsException::invalidScope('app.name', SettingScope::User);

        $this->assertStringContainsString('app.name', $exception->getMessage());
        $this->assertStringContainsString('user', $exception->getMessage());
    }
}
