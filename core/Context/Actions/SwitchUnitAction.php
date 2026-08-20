<?php

namespace Core\Context\Actions;

use Core\Contracts\OrganizationalUnitContext;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Support\Facades\DB;

final class SwitchUnitAction
{
    public function __construct(
        private readonly OrganizationalUnitContext $context,
    ) {}

    public function handle(string $userId, string $unitId): void
    {
        $assigned = DB::table('organizational_unit_user')
            ->where('organizational_unit_id', $unitId)
            ->where('user_id', $userId)
            ->exists();

        if (! $assigned) {
            throw OrganizationException::invalidAssignment(
                'Unit tujuan harus merupakan unit yang di-assign ke pengguna.'
            );
        }

        $unit = OrganizationalUnit::findOrFail($unitId);

        $this->context->set($unit);
    }
}
