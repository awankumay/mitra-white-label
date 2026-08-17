<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_records_security_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_succeeded',
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_login_failure_records_security_event(): void
    {
        event(new Failed('web', null, []));

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_failed',
            'user_id' => null,
        ]);
    }

    public function test_password_change_records_security_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        SecurityEventOccurred::dispatch(
            SecurityEventType::PasswordChanged,
            $user->getKey(),
        );

        $this->assertDatabaseHas('security_events', [
            'event' => 'password_changed',
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_two_factor_enable_records_security_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        SecurityEventOccurred::dispatch(
            SecurityEventType::TwoFactorEnabled,
            $user->getKey(),
        );

        $this->assertDatabaseHas('security_events', [
            'event' => 'two_factor_enabled',
            'user_id' => $user->getKey(),
        ]);
    }
}
