<?php

namespace Core\Security\Enums;

enum SecurityEventType: string
{
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case PasswordChanged = 'password_changed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case PasskeyRegistered = 'passkey_registered';
    case PasskeyRevoked = 'passkey_revoked';
    case SessionRevoked = 'session_revoked';
}
