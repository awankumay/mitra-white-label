<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_can_enable_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $user->enableTwoFactorAuthentication();

        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertNotNull($user->breezySession->two_factor_recovery_codes);
    }

    public function test_user_can_confirm_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->enableTwoFactorAuthentication();

        $user->confirmTwoFactorAuthentication();

        $this->assertTrue($user->hasConfirmedTwoFactor());
    }

    public function test_user_can_disable_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        $user->disableTwoFactorAuthentication();

        $this->assertFalse($user->hasEnabledTwoFactor());
    }

    public function test_user_with_2fa_enabled_gets_challenge_after_login(): void
    {
        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        // Simulate a new device: no valid 2FA session in this browser
        session()->forget('breezy_session_id');

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirectContains(route('filament.admin.auth.two-factor'));
    }
}
