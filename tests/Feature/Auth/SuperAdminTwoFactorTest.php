<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_super_admin_without_2fa_is_redirected_to_profile(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.pages.my-profile'));
    }

    public function test_super_admin_with_confirmed_2fa_reaches_challenge_not_profile(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        // Simulate a new device: no valid 2FA session in this browser
        session()->forget('breezy_session_id');

        // Confirmed 2FA but no valid session → 2FA challenge, NOT profile redirect
        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirectContains(route('filament.admin.auth.two-factor'));
    }
}
