<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionsUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_user_id_is_uuid(): void
    {
        $columns = Schema::getColumnListing('sessions');

        $this->assertContains('user_id', $columns);
    }

    public function test_sessions_user_id_has_foreign_key_to_users(): void
    {
        $foreignKeys = Schema::getForeignKeys('sessions');
        $usersFk = collect($foreignKeys)->first(fn ($fk) => in_array('user_id', $fk['columns']));

        $this->assertNotNull($usersFk);
        $this->assertSame('users', $usersFk['foreign_table']);
    }
}
