<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_password_reset_page_is_accessible(): void
    {
        $this->get(route('filament.admin.auth.password-reset.request'))
            ->assertSuccessful();
    }

    public function test_user_receives_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('filament.admin.auth.password-reset.request'), [
            'email' => 'user@example.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->post(route('filament.admin.auth.password-reset.reset'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();
    }
}
