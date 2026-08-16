<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackageMorphUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_spatie_model_id_is_uuid(): void
    {
        $this->assertSame('char', Schema::getColumnType('model_has_roles', 'model_id'));
        $this->assertSame('char', Schema::getColumnType('model_has_permissions', 'model_id'));
    }

    public function test_notifications_notifiable_id_is_uuid(): void
    {
        $this->assertSame('char', Schema::getColumnType('notifications', 'notifiable_id'));
    }

    public function test_activity_log_morphs_are_uuid(): void
    {
        $this->assertSame('char', Schema::getColumnType('activity_log', 'subject_id'));
        $this->assertSame('char', Schema::getColumnType('activity_log', 'causer_id'));
    }

    public function test_roles_and_permissions_stay_bigint(): void
    {
        $this->assertSame('bigint', Schema::getColumnType('roles', 'id'));
        $this->assertSame('bigint', Schema::getColumnType('permissions', 'id'));
    }
}
