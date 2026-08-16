<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UsersUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_id_is_uuid_v7(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Uuid::isValid($user->id));
        $this->assertSame(7, Uuid::fromString($user->id)->getVersion());
    }

    public function test_users_table_has_primary_unit_column(): void
    {
        $this->assertTrue(\Schema::hasColumn('users', 'primary_organizational_unit_id'));
    }

    public function test_users_table_has_soft_deletes(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted($user);
    }
}
