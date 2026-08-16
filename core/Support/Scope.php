<?php

namespace Core\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class Scope
{
    public static function unit(Builder $query, ?string $unitId): Builder
    {
        if ($unitId === null) {
            return $query;
        }

        return $query->where('organizational_unit_id', $unitId);
    }

    public static function organization(Builder $query, ?string $orgId): Builder
    {
        if ($orgId === null) {
            return $query;
        }

        return $query->where('organization_id', $orgId);
    }

    public static function userUnits(Builder $query, Authenticatable $user): Builder
    {
        $unitIds = DB::table('organizational_unit_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->pluck('organizational_unit_id');

        return $query->whereIn('organizational_unit_id', $unitIds);
    }

    public static function userOrganizations(Builder $query, Authenticatable $user): Builder
    {
        $orgIds = DB::table('organization_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->pluck('organization_id');

        return $query->whereIn('organization_id', $orgIds);
    }

    public static function userScope(Builder $query, Authenticatable $user): Builder
    {
        $unitIds = DB::table('organizational_unit_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->pluck('organizational_unit_id');

        $orgIds = DB::table('organization_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->pluck('organization_id');

        return $query->where(function (Builder $q) use ($unitIds, $orgIds) {
            $q->whereIn('organizational_unit_id', $unitIds)
                ->orWhereIn('organization_id', $orgIds);
        });
    }

    public static function can(Authenticatable $user, ?string $unitId): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if ($unitId === null) {
            return false;
        }

        return DB::table('organizational_unit_user')
            ->where('organizational_unit_id', $unitId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    public static function isSuperAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super_admin');
    }
}
