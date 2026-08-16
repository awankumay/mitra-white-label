<?php

namespace Core\Organization\Models;

use Core\Database\Factories\OrganizationalUnitFactory;
use Core\Organization\Enums\OrganizationalUnitType;
use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationalUnit extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = [
        'organization_id', 'parent_id', 'name', 'type', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'type' => OrganizationalUnitType::class,
    ];

    protected static function newFactory(): Factory
    {
        return OrganizationalUnitFactory::new();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
