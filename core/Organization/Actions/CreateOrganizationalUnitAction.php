<?php

namespace Core\Organization\Actions;

use Core\Exceptions\OrganizationException;
use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;

final class CreateOrganizationalUnitAction
{
    public function handle(
        Organization $organization,
        string $name,
        ?OrganizationalUnitType $type = null,
        ?string $parentId = null,
        ?string $createdBy = null,
    ): OrganizationalUnit {
        if ($parentId !== null) {
            $this->assertValidParent($organization, $parentId);
        }

        return OrganizationalUnit::create([
            'organization_id' => $organization->id,
            'parent_id' => $parentId,
            'name' => $name,
            'type' => $type ?? OrganizationalUnitType::HEAD_OFFICE,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }

    private function assertValidParent(Organization $organization, string $parentId): void
    {
        $parent = OrganizationalUnit::find($parentId);

        if ($parent === null || $parent->organization_id !== $organization->id) {
            throw OrganizationException::invalidHierarchy(
                'Unit induk harus berada dalam organisasi yang sama.'
            );
        }

        $this->assertDepthWithinLimit($parent);
    }

    private function assertDepthWithinLimit(OrganizationalUnit $unit): void
    {
        $maxDepth = (int) config('core.organization.max_depth', 10);
        $depth = 1;

        while ($unit->parent !== null) {
            $depth++;
            $unit = $unit->parent;

            if ($depth >= $maxDepth) {
                throw OrganizationException::invalidHierarchy(
                    "Kedalaman hierarki melebihi batas maksimum {$maxDepth} level."
                );
            }
        }
    }
}
