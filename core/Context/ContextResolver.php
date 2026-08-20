<?php

namespace Core\Context;

use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

final class ContextResolver
{
    public function sessionKey(): string
    {
        return (string) config('core.context.session_key', 'context.unit_id');
    }

    public function resolveCurrentUnit(?Authenticatable $user): ?OrganizationalUnit
    {
        if ($user === null) {
            return null;
        }

        $sessionKey = $this->sessionKey();

        if (Session::has($sessionKey)) {
            $unit = OrganizationalUnit::find(Session::get($sessionKey));

            if ($unit !== null && $this->isAssigned($user, $unit->id)) {
                return $unit;
            }

            Session::forget($sessionKey);
        }

        $primaryUnitId = $this->primaryUnitId($user);

        if ($primaryUnitId !== null) {
            $primary = OrganizationalUnit::find($primaryUnitId);

            if ($primary !== null && $this->isAssigned($user, $primary->id)) {
                return $primary;
            }
        }

        return $this->firstAssignedUnit($user);
    }

    public function resolveOrganization(?Authenticatable $user): ?Organization
    {
        if ($user === null) {
            return null;
        }

        $unit = $this->resolveCurrentUnit($user);

        if ($unit !== null) {
            return Organization::find($unit->organization_id);
        }

        return $this->firstOrganization($user);
    }

    private function isAssigned(Authenticatable $user, string $unitId): bool
    {
        return DB::table('organizational_unit_user')
            ->where('organizational_unit_id', $unitId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    private function primaryUnitId(Authenticatable $user): ?string
    {
        return DB::table('users')
            ->where('id', $user->getAuthIdentifier())
            ->value('primary_organizational_unit_id');
    }

    private function firstAssignedUnit(Authenticatable $user): ?OrganizationalUnit
    {
        $unitId = DB::table('organizational_unit_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderBy('organizational_unit_id')
            ->value('organizational_unit_id');

        return $unitId !== null ? OrganizationalUnit::find($unitId) : null;
    }

    private function firstOrganization(Authenticatable $user): ?Organization
    {
        $organizationId = DB::table('organization_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderBy('organization_id')
            ->value('organization_id');

        return $organizationId !== null ? Organization::find($organizationId) : null;
    }
}
