<?php

namespace Tests\Unit\Security;

use App\Models\User;
use Core\Security\Enums\SecurityEventType;
use Core\Security\Services\SecurityEventRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_creates_security_event(): void
    {
        $user = User::factory()->create();

        $recorder = new SecurityEventRecorder;

        $recorder->record(SecurityEventType::PasswordChanged, $user->getKey(), ['ip_address' => '127.0.0.1']);

        $this->assertDatabaseHas('security_events', [
            'event' => 'password_changed',
            'user_id' => $user->getKey(),
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_record_allows_null_user_for_anonymous_events(): void
    {
        $recorder = new SecurityEventRecorder;

        $recorder->record(SecurityEventType::LoginFailed, null, []);

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_failed',
            'user_id' => null,
        ]);
    }
}
