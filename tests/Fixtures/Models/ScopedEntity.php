<?php

namespace Tests\Fixtures\Models;

use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScopedEntity extends Model
{
    use SoftDeletes;
    use UsesUuid;

    protected $table = 'scoped_entities';

    protected $guarded = [];

    protected $casts = [
        'organization_id' => 'string',
        'organizational_unit_id' => 'string',
    ];
}
