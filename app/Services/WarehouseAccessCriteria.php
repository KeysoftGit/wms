<?php

namespace App\Services;

use App\Models\ControlPanel;
use App\Models\MsWarehouse;
use Illuminate\Support\Facades\Schema;

class WarehouseAccessCriteria
{
    public static function allowedIds(): ?array
    {
        if (!ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            return null;
        }

        return MsWarehouse::accessibleTo(auth()->user())
            ->pluck('WarehouseID')
            ->all();
    }

    public static function allowedIdsWithChildren(): ?array
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return null;
        }

        if (empty($allowedIds)) {
            return [];
        }

        $childIds = MsWarehouse::whereIn('ParentID', $allowedIds)
            ->pluck('WarehouseID')
            ->all();

        return array_values(array_unique(array_merge($allowedIds, $childIds)));
    }

    public static function apply($query, string|array $columns = 'WarehouseID')
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        $columns = (array) $columns;

        return $query->where(function ($warehouseQuery) use ($columns, $allowedIds) {
            foreach ($columns as $column) {
                $warehouseQuery->whereIn($column, $allowedIds);
            }
        });
    }

    public static function applyWithChildren($query, string|array $columns = 'WarehouseID')
    {
        $allowedIds = self::allowedIdsWithChildren();

        if ($allowedIds === null) {
            return $query;
        }

        $columns = (array) $columns;

        return $query->where(function ($warehouseQuery) use ($columns, $allowedIds) {
            foreach ($columns as $column) {
                $warehouseQuery->whereIn($column, $allowedIds);
            }
        });
    }

    public static function monitoringAllowedIds(): ?array
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return null;
        }

        $warehouse = new MsWarehouse();
        $schema = Schema::connection($warehouse->getConnectionName());

        if ($schema->hasColumn($warehouse->getTable(), 'openInStockMonitoring')) {
            $openWarehouseIds = MsWarehouse::where('openInStockMonitoring', 1)
                ->pluck('WarehouseID')
                ->all();

            $allowedIds = array_values(array_unique(array_merge($allowedIds, $openWarehouseIds)));
        }

        return $allowedIds;
    }

    public static function applyForStockMonitoring($query, string $column = 'WarehouseID')
    {
        $allowedIds = self::monitoringAllowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        return $query->whereIn($column, $allowedIds);
    }

    public static function applyDetails($query, string $relation = 'details', string $column = 'WarehouseID')
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        return $query->whereDoesntHave($relation, function ($detailQuery) use ($column, $allowedIds) {
            $detailQuery->where(function ($unauthorizedQuery) use ($column, $allowedIds) {
                $unauthorizedQuery->whereNull($column)
                    ->orWhereNotIn($column, $allowedIds);
            });
        });
    }

    public static function applyRelation($query, string $relation, string|array $columns)
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        $columns = (array) $columns;

        return $query->whereHas($relation, function ($relationQuery) use ($columns, $allowedIds) {
            $relationQuery->where(function ($warehouseQuery) use ($columns, $allowedIds) {
                foreach ($columns as $column) {
                    $warehouseQuery->whereIn($column, $allowedIds);
                }
            });
        });
    }

    public static function applyDivisions($query, string $column = 'DivisionID')
    {
        $allowedIds = self::allowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        $allowedDivisionIds = MsWarehouse::whereIn('WarehouseID', $allowedIds)
            ->whereNotNull('DivisionID')
            ->distinct()
            ->pluck('DivisionID')
            ->all();

        return $query->whereIn($column, $allowedDivisionIds);
    }
}
