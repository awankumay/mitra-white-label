<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrimaryUnitFkTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_unit_foreign_key_exists(): void
    {
        $foreignKeys = Schema::getForeignKeys('users');
        $primaryFk = collect($foreignKeys)->first(fn ($fk) => in_array('primary_organizational_unit_id', $fk['columns']));

        $this->assertNotNull($primaryFk);
        $this->assertSame('organizational_units', $primaryFk['foreign_table']);
    }
}
