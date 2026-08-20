<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PivotSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizational_unit_user_table(): void
    {
        $this->assertTrue(Schema::hasTable('organizational_unit_user'));
        $this->assertTrue(Schema::hasColumns('organizational_unit_user', ['organizational_unit_id', 'user_id']));
    }

    public function test_organization_user_table(): void
    {
        $this->assertTrue(Schema::hasTable('organization_user'));
        $this->assertTrue(Schema::hasColumns('organization_user', ['organization_id', 'user_id']));
    }
}
