<?php

namespace Core\Organization\Models;

use Core\Database\Factories\OrganizationFactory;
use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = ['name', 'created_by', 'updated_by'];

    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }

    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class);
    }
}
