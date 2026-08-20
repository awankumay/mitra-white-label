<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class PasskeyJavaScriptTest extends TestCase
{
    /**
     * Verify that @simplewebauthn/browser is bundled in assets
     */
    public function test_webauthn_library_is_bundled(): void
    {
        // Read compiled JavaScript bundle
        $appJsFile = collect(glob(public_path('build/assets/app-*.js')))->first();
        $this->assertNotNull($appJsFile, 'Compiled JavaScript bundle not found');

        $appJsContent = file_get_contents($appJsFile);

        // Verify that startRegistration function is in the bundle
        $this->assertStringContainsString(
            'startRegistration',
            $appJsContent,
            'startRegistration function not found in compiled JavaScript bundle'
        );

        // Verify that window.startRegistration is assigned
        $this->assertStringContainsString(
            'window.startRegistration',
            $appJsContent,
            'window.startRegistration assignment not found in bundle'
        );

        // Verify that error handling for WebAuthn is present
        $this->assertStringContainsString(
            'WebAuthn',
            $appJsContent,
            'WebAuthn references not found in bundle'
        );
    }

    /**
     * Verify passkeys are enabled in configuration
     */
    public function test_passkeys_enabled_in_config(): void
    {
        $this->assertTrue(
            config('core.auth.passkey.enabled'),
            'Passkeys should be enabled in core.auth.passkey.enabled config'
        );

        $this->assertNotEmpty(
            config('core.auth.passkey.relying_party_name'),
            'Passkey relying party name should be configured'
        );
    }
}
