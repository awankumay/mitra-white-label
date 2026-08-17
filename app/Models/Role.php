<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
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
