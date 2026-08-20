<?php

namespace Tests\Unit\Security;

use Core\Security\Enums\SecurityEventType;
use PHPUnit\Framework\TestCase;

class SecurityEventTypeTest extends TestCase
{
    public function test_enum_has_expected_cases_and_values(): void
    {
        $this->assertSame('login_succeeded', SecurityEventType::LoginSucceeded->value);
        $this->assertSame('login_failed', SecurityEventType::LoginFailed->value);
        $this->assertSame('password_changed', SecurityEventType::PasswordChanged->value);
        $this->assertSame('two_factor_enabled', SecurityEventType::TwoFactorEnabled->value);
        $this->assertSame('two_factor_disabled', SecurityEventType::TwoFactorDisabled->value);
        $this->assertSame('passkey_registered', SecurityEventType::PasskeyRegistered->value);
        $this->assertSame('passkey_revoked', SecurityEventType::PasskeyRevoked->value);
        $this->assertSame('session_revoked', SecurityEventType::SessionRevoked->value);
    }
}
