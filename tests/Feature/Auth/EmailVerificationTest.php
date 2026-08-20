<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_redirected_to_verification_prompt(): void
    {
        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.email-verification.prompt'));
    }

    public function test_verified_panel_user_can_access_dashboard(): void
    {
        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertSuccessful();
    }
}
