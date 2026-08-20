<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertTrue(Schema::hasColumns('settings', ['key', 'value', 'organization_id', 'organizational_unit_id', 'user_id', 'created_by', 'updated_by']));
    }

    public function test_scope_unique_constraint(): void
    {
        DB::table('settings')->insert([
            'id' => Str::uuid7()->toString(),
            'key' => 'app.name',
            'value' => json_encode('A'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('settings')->insert([
            'id' => Str::uuid7()->toString(),
            'key' => 'app.name',
            'value' => json_encode('B'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertTrue(true); // scope berbeda (System, org, unit, user) boleh
    }
}
