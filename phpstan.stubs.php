<?php

namespace Illuminate\Foundation\Auth;

use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Stub for proper type hinting of trait methods.
 * This helps PHPStan recognize methods added by traits.
 */

// HasRoles trait methods
interface HasRolesStub
{
    /**
     * Determine if the model has (one of) the given role(s).
     */
    public function hasRole(mixed $roles, ?string $guard = null): bool;
}

// TwoFactorAuthenticatable trait methods
interface TwoFactorAuthenticatableStub
{
    /**
     * Determine if the user has confirmed two-factor authentication.
     */
    public function hasConfirmedTwoFactor(): bool;

    /**
     * Determine if the user has a valid two-factor session.
     */
    public function hasValidTwoFactorSession(): bool;

    /**
     * Get the user's two-factor secret.
     */
    public function getTwoFactorSecret(): ?string;

    /**
     * Enable two-factor authentication for the user.
     */
    public function enableTwoFactorAuthentication(): void;

    /**
     * Disable two-factor authentication for the user.
     */
    public function disableTwoFactorAuthentication(): void;

    /**
     * Get the user's recovery codes.
     */
    public function getRecoveryCodes(): array;

    /**
     * Generate new recovery codes for the user.
     */
    public function generateRecoveryCodes(): void;
}
