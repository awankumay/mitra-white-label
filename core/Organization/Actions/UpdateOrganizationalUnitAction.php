<?php

namespace Core\Organization\Actions;

use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;

final class UpdateOrganizationalUnitAction
{
    public function handle(OrganizationalUnit $unit, array $data): OrganizationalUnit
    {
        if (array_key_exists('parent_id', $data)) {
            $this->assertValidParent($unit, $data['parent_id']);
        }

        $unit->update($data);

        return $unit->fresh();
    }

    private function assertValidParent(OrganizationalUnit $unit, ?string $parentId): void
    {
        if ($parentId === null) {
            return; // jadikan root — valid
        }

        if ($parentId === $unit->id) {
            throw OrganizationException::invalidHierarchy(
                'Unit tidak dapat menjadi induk dari dirinya sendiri.'
            );
        }

        $parent = OrganizationalUnit::find($parentId);

        if ($parent === null || $parent->organization_id !== $unit->organization_id) {
            throw OrganizationException::invalidHierarchy(
                'Unit induk harus berada dalam organisasi yang sama.'
            );
        }

        $this->assertNoCycle($unit, $parent);
        $this->assertDepthWithinLimit($parent);
    }

    private function assertNoCycle(OrganizationalUnit $unit, OrganizationalUnit $parent): void
    {
        $ancestor = $parent;

        while ($ancestor !== null) {
            if ($ancestor->id === $unit->id) {
                throw OrganizationException::invalidHierarchy(
                    'Hierarki unit tidak boleh membentuk siklus.'
                );
            }

            $ancestor = $ancestor->parent;
        }
    }

    private function assertDepthWithinLimit(OrganizationalUnit $unit): void
    {
        $maxDepth = (int) config('core.organization.max_depth', 10);
        $depth = 1;

        while ($unit->parent !== null) {
            $depth++;
            $unit = $unit->parent;

            if ($depth > $maxDepth) {
                throw OrganizationException::invalidHierarchy(
                    "Kedalaman hierarki melebihi batas maksimum {$maxDepth} level."
                );
            }
        }
    }
}
