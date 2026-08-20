<?php

namespace Core\Security\Models;

use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use UsesUuid;

    protected $table = 'security_events';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}
