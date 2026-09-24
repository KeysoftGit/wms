<?php

namespace App\Helpers;

use App\Models\MsWarehouse;

class WarehouseTreeHelper
{
    /**
     * The given warehouse plus every descendant (recursively) in the Ms_Warehouse tree.
     * Returns just [$warehouseId] for a leaf/child warehouse. ParentID can reference either
     * WarehouseID or the numeric id column, so both are matched at each level.
     */
    public static function descendantsAndSelf(string $warehouseId): array
    {
        $rootQuery = MsWarehouse::where('WarehouseID', $warehouseId);
        if (is_numeric($warehouseId)) {
            $rootQuery->orWhere('id', (int) $warehouseId);
        }
        $root = $rootQuery->first();

        $warehouseIds = [$root?->WarehouseID ?? $warehouseId];
        $frontier = self::parentIdentifiers($root, $warehouseId);

        while (!empty($frontier)) {
            $children = MsWarehouse::whereIn('ParentID', $frontier)
                ->get(['id', 'WarehouseID']);

            $childWarehouseIds = $children
                ->pluck('WarehouseID')
                ->filter()
                ->values()
                ->all();

            $childWarehouseIds = array_values(array_diff($childWarehouseIds, $warehouseIds));
            if (empty($childWarehouseIds)) {
                break;
            }

            $warehouseIds = array_values(array_unique(array_merge($warehouseIds, $childWarehouseIds)));
            $frontier = $children
                ->flatMap(fn ($warehouse) => self::parentIdentifiers($warehouse, $warehouse->WarehouseID))
                ->unique()
                ->values()
                ->all();
        }

        return $warehouseIds;
    }

    private static function parentIdentifiers(?MsWarehouse $warehouse, string $fallback): array
    {
        return collect([
            $fallback,
            $warehouse?->WarehouseID,
            $warehouse?->id !== null ? (string) $warehouse->id : null,
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
