<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_table(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumns('audit_logs', ['organization_id', 'user_id', 'action', 'subject_type', 'subject_id', 'ip_address', 'metadata', 'occurred_at']));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'deleted_at'));
    }

    public function test_security_events_table(): void
    {
        $this->assertTrue(Schema::hasTable('security_events'));
        $this->assertTrue(Schema::hasColumns('security_events', ['event', 'user_id', 'ip_address', 'user_agent', 'metadata', 'occurred_at']));
        $this->assertFalse(Schema::hasColumn('security_events', 'deleted_at'));
    }
}
