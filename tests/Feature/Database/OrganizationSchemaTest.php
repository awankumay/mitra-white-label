<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizations_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('organizations'));
        $this->assertSame('char', Schema::getColumnType('organizations', 'id'));
        $this->assertTrue(Schema::hasColumns('organizations', ['name', 'created_by', 'updated_by', 'deleted_at']));
    }

    public function test_organizational_units_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('organizational_units'));
        $this->assertSame('char', Schema::getColumnType('organizational_units', 'id'));
        $this->assertTrue(Schema::hasColumns('organizational_units', ['organization_id', 'parent_id', 'name', 'type', 'deleted_at']));
    }

    public function test_organizations_name_is_unique(): void
    {
        $unique = collect(Schema::getIndexes('organizations'))->first(fn ($i) => $i['unique'] && in_array('name', $i['columns']));
        $this->assertNotNull($unique);
    }
}
