<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackageMorphUuidTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tipe kolom UUID (char di MySQL/PostgreSQL) dilaporkan berbeda oleh
     * SQLite ('varchar', tanpa tipe char terpisah — type affinity).
     */
    private function uuidColumnType(): string
    {
        return Schema::getConnection()->getDriverName() === 'sqlite' ? 'varchar' : 'char';
    }

    /**
     * SQLite tidak punya tipe bigint terpisah dari integer (semua integer
     * affinity dilaporkan 'integer').
     */
    private function bigIntColumnType(): string
    {
        return Schema::getConnection()->getDriverName() === 'sqlite' ? 'integer' : 'bigint';
    }

    public function test_spatie_model_id_is_uuid(): void
    {
        $this->assertSame($this->uuidColumnType(), Schema::getColumnType('model_has_roles', 'model_id'));
        $this->assertSame($this->uuidColumnType(), Schema::getColumnType('model_has_permissions', 'model_id'));
    }

    public function test_notifications_notifiable_id_is_uuid(): void
    {
        $this->assertSame($this->uuidColumnType(), Schema::getColumnType('notifications', 'notifiable_id'));
    }

    public function test_activity_log_morphs_are_uuid(): void
    {
        $this->assertSame($this->uuidColumnType(), Schema::getColumnType('activity_log', 'subject_id'));
        $this->assertSame($this->uuidColumnType(), Schema::getColumnType('activity_log', 'causer_id'));
    }

    public function test_roles_and_permissions_stay_bigint(): void
    {
        $this->assertSame($this->bigIntColumnType(), Schema::getColumnType('roles', 'id'));
        $this->assertSame($this->bigIntColumnType(), Schema::getColumnType('permissions', 'id'));
    }
}
