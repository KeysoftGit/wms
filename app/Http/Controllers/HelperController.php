<?php

namespace App\Http\Controllers;

use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Models\BukuStock;
use App\Models\MsWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class HelperController extends Controller
{
    public function getFifoAllocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'part_id'      => 'required|string',
            'warehouse_id' => 'required|string',
            'qty'          => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $allocations = BukuStockHelper::allocateFifo(
                $request->input('part_id'),
                $request->input('warehouse_id'),
                (float) $request->input('qty')
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch(collect($allocations)->map(fn ($row) => [
            'PartID' => $request->input('part_id'),
            'BatchNo' => $row['BatchNo'],
        ]));

        $allocations = array_map(function ($row) use ($request, $coilNoByPartBatch) {
            $row['CoilNo'] = $coilNoByPartBatch->get(CoilNoHelper::key($request->input('part_id'), $row['BatchNo']))
                ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($row['BatchNo']));

            return $row;
        }, $allocations);

        return response()->json([
            'status' => 'success',
            'data' => [
                'part_id' => $request->input('part_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'qty' => (float) $request->input('qty'),
                'allocations' => $allocations,
            ],
        ]);
    }

    public function getAvailableStockDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'part_id'      => 'required|string',
            'warehouse_id' => 'nullable|string',
            'target'       => 'required|in:batch_no',
            'batch_no'     => 'nullable|string',
            'filter_qty'   => 'nullable|boolean',
            'include_children' => 'nullable|boolean',
            'include_child_warehouses' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $target = $request->target;
        $term = $request->query('term');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $selectColumn = match ($target) {
            'batch_no'  => 'BatchNo',
        };

        $query = BukuStock::query()
            ->where('PartID', $request->part_id);

        if ($request->filled('warehouse_id')) {
            $warehouseId = trim($request->warehouse_id);
            $includeChildren = $request->boolean('include_children') || $request->boolean('include_child_warehouses');
            $warehouseIds = $includeChildren
                ? $this->warehouseIdsWithDescendants($warehouseId)
                : [$warehouseId];

            $query->whereIn('WarehouseID', $warehouseIds);
        }

        $this->applyOptionalStockFilter($query, $request, $target, 'batch_no', 'BatchNo');

        $query->when($term, function ($q) use ($selectColumn, $term, $target) {
            if ($term === '__NULL__') {
                $column = match ($target) {
                    'batch_no'  => 'BatchNo',
                };

                return $q->whereNull($column);
            }

            return $q->where($selectColumn, 'like', "%{$term}%");
        });

        $bukuStock = new BukuStock();
        $schema = Schema::connection($bukuStock->getConnectionName());
        $query->select($selectColumn)
            ->selectRaw('SUM(Qty) as total_qty');

        if ($schema->hasColumn($bukuStock->getTable(), 'Qty2')) {
            $query->selectRaw('SUM(Qty2) as total_qty2');
        }

        if ($schema->hasColumn($bukuStock->getTable(), 'UnitID2')) {
            $query->selectRaw('MAX(UnitID2) as unit_id2');
        }

        $query->groupBy($selectColumn);

        if ($request->boolean('filter_qty')) {
            $query->havingRaw('SUM(Qty) > 0');
        }

        $results = $query
            ->orderBy($selectColumn)
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        $hasMore = $results->count() > $perPage;
        $results = $results->take($perPage)->values();

        if ($target === 'batch_no') {
            $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch($results->map(fn ($row) => [
                'PartID' => $request->part_id,
                'BatchNo' => $row->BatchNo,
            ]));

            $results = $results->map(function ($row) use ($request, $coilNoByPartBatch) {
                $row->CoilNo = $row->BatchNo !== null
                    ? ($coilNoByPartBatch->get(CoilNoHelper::key($request->part_id, $row->BatchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($row->BatchNo)))
                    : null;
                $row->coil_no = $row->CoilNo;

                return $row;
            })->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'part_id'      => $request->part_id,
                'warehouse_id' => $request->warehouse_id,
                'target'       => $target,
                'results'      => $results,
            ],
            'pagination' => [
                'more' => $hasMore,
            ],
        ]);
    }

    private function applyOptionalStockFilter($query, Request $request, string $target, string $input, string $column): void
    {
        if ($target === $input || !$request->has($input) || $request->input($input) === '') {
            return;
        }

        if ($request->input($input) === '__NULL__') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $request->input($input));
    }

    private function warehouseIdsWithDescendants(string $warehouseId): array
    {
        $rootQuery = MsWarehouse::where('WarehouseID', $warehouseId);
        if (is_numeric($warehouseId)) {
            $rootQuery->orWhere('id', (int) $warehouseId);
        }
        $root = $rootQuery->first();

        $warehouseIds = [$root?->WarehouseID ?? $warehouseId];
        $frontier = $this->warehouseParentIdentifiers($root, $warehouseId);

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
                ->flatMap(fn ($warehouse) => $this->warehouseParentIdentifiers($warehouse, $warehouse->WarehouseID))
                ->unique()
                ->values()
                ->all();
        }

        return $warehouseIds;
    }

    private function warehouseParentIdentifiers(?MsWarehouse $warehouse, string $fallback): array
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
