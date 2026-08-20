<?php

namespace App\Models;

use App\Services\RoleService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int|null $parent_role_id
 */
class Role extends SpatieRole
{
    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if ($role->parent_role_id === null) {
                return;
            }

            $service = app(RoleService::class);

            if ($service->wouldCreateCycle($role, (string) $role->parent_role_id)) {
                throw new \LogicException('Role hierarchy cycle detected: '.$role->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    /**
     * Semua ancestor (root dulu), cycle-safe.
     *
     * @return Collection<int, Role>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $seen = [$this->id];
        $current = $this->parent;

        while ($current && ! in_array($current->id, $seen, true)) {
            $ancestors->push($current);
            $seen[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }
}
