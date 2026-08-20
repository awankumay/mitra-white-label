<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class RoleService
{
    /**
     * Semua permission efektif user = permission semua role user + semua ancestor.
     *
     * @return Collection<int, Permission>
     */
    public function permissionsFor(User $user): Collection
    {
        $permissions = new Collection;

        /** @var Role $role */
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions->push($permission);
            }

            /** @var Role $ancestor */
            foreach ($role->ancestors() as $ancestor) {
                foreach ($ancestor->permissions as $permission) {
                    $permissions->push($permission);
                }
            }
        }

        return $permissions->unique('id')->values();
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $this->permissionsFor($user)->contains('name', $permission);
    }

    /**
     * Semua descendant role (anak, cucu, dst.).
     *
     * @return Collection<int, Role>
     */
    public function descendantsOf(Role $role): Collection
    {
        $descendants = new Collection;
        $queue = $role->children;

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            /** @var Role $current */
            $descendants->push($current);
            $queue = $queue->merge($current->children);
        }

        return $descendants->unique('id')->values();
    }

    public function wouldCreateCycle(Role $role, ?string $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ((string) $role->id === $newParentId) {
            return true;
        }

        return $this->descendantsOf($role)->contains(function (Role $descendant) use ($newParentId): bool {
            return (string) $descendant->id === $newParentId;
        });
    }
}
