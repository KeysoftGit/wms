<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockMonitor\GetStockDetailOptionsRequest;
use App\Http\Requests\StockMonitor\GetStockMovementsRequest;
use App\Models\BukuStock;
use App\Models\MsWarehouse;
use App\Models\MsPartUnit;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StockMonitorController extends Controller
{
    protected array $movementColumns = [
        'TransactionNo', 'TransactionType', 'TransactionDate', 'PartID', 'WarehouseID',
        'BatchNo', 'Notes', 'Qty', 'UnitID', 'Qty2', 'UnitID2', 'created_at',
    ];

    protected array $detailColumnMap = [
        'batch_no' => 'BatchNo',
    ];

    /**
     * Paginated stock ledger for a part using batch-only stock identity.
     */
    public function getMovements(GetStockMovementsRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);
            $conversion = $this->stockConversion($request);

            $query = $this->buildMovementsQuery($request);
            $targetUnit = $request->filled('unit_id') ? trim((string) $request->unit_id) : null;
            $summaryRow = (clone $query)
                ->selectRaw(DualQuantityHelper::summarySelect($targetUnit))
                ->first();

            $paginator = (clone $query)
                ->with('part:PartID,PartName')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, $this->movementColumns, 'page', $page);

            $movementRows = collect($paginator->items());
            $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch($movementRows);

            $movements = $movementRows->map(function ($row) use ($conversion, $coilNoByPartBatch, $targetUnit) {
                $display = DualQuantityHelper::displayMovement($row, $targetUnit, $conversion);

                return [
                    'TransactionNo' => $row->TransactionNo,
                    'TransactionType' => $row->TransactionType,
                    'TransactionDate' => $row->TransactionDate ? Carbon::parse($row->TransactionDate)->format('Y-m-d') : null,
                    'PartID' => $row->PartID,
                    'PartName' => $row->part->PartName ?? '-',
                    'WarehouseID' => $row->WarehouseID,
                    'BatchNo' => $row->BatchNo,
                    'CoilNo' => $row->BatchNo !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($row->PartID, $row->BatchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($row->BatchNo)))
                        : null,
                    'Notes' => $this->formatNotes($row),
                    'Qty' => $display['qty'],
                    'UnitID' => $display['unit_id'],
                    'Qty2' => $display['qty2'],
                    'UnitID2' => $display['unit_id2'],
                    'weight_per_piece' => $display['weight_per_piece'],
                    'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toDateTimeString() : null,
                ];
            });

            $result = [
                'movements' => $movements,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
                'summary' => DualQuantityHelper::summary($summaryRow, $targetUnit, $conversion),
            ];

            return ResponseFormatter::success($result, 'Stock movements fetched successfully')->toResponse();
        } catch (\Throwable $e) {
            Log::error('StockMonitorController getMovements error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    /**
     * Distinct batch values (with summed qty) for a part.
     */
    public function getDetailOptions(GetStockDetailOptionsRequest $request): JsonResponse
    {
        try {
            $target = $request->target;
            $term = $request->term;
            $column = $this->detailColumnMap[$target];

            $query = BukuStock::query()
                ->where('PartID', $request->part_id)
                ->whereHas('warehouse', function ($warehouseQuery) {
                    $warehouseQuery->where('Active', 1);
                });

            if ($request->filled('warehouse_id')) {
                $query->whereIn('WarehouseID', $this->warehouseIdsForFilter($request));
            }

            foreach ($this->detailColumnMap as $input => $col) {
                if ($input === $target) {
                    continue;
                }
                $this->applyOptionalFilter($query, $request, $input, $col);
            }

            if ($term === '__NULL__') {
                $query->whereNull($column);
            } elseif ($term) {
                $query->where($column, 'like', "%{$term}%");
            }

            $query->selectRaw("{$column} as value, SUM(Qty) as total_qty, SUM(Qty2) as total_qty2, MAX(created_at) as created_at")
                ->groupBy($column);

            if ($request->boolean('filter_qty')) {
                $query->havingRaw('SUM(Qty) > 0');
            }

            $results = $query->limit(10)->get();
            $coilNoByPartBatch = $target === 'batch_no'
                ? CoilNoHelper::lookupByPartBatch($results->map(fn ($row) => [
                    'part_id' => $request->part_id,
                    'batch_no' => $row->value,
                ]))
                : collect();

            if ($target === 'batch_no') {
                $results = $results->map(function ($row) use ($request, $coilNoByPartBatch) {
                    $row->coil_no = $row->value !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($request->part_id, $row->value)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($row->value)))
                        : null;
                    $qty1 = (float) ($row->total_qty ?? 0);
                    $qty2 = $row->total_qty2 !== null ? (float) $row->total_qty2 : null;
                    $row->weight_per_piece = DualQuantityHelper::weightPerPiece($qty1, $qty2);

                    return $row;
                });
            }

            $result = [
                'target' => $target,
                'results' => $results,
            ];

            return ResponseFormatter::success($result, 'Stock detail options fetched successfully')->toResponse();
        } catch (\Throwable $e) {
            Log::error('StockMonitorController getDetailOptions error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildMovementsQuery(GetStockMovementsRequest $request): Builder
    {
        $query = BukuStock::query()
            ->where('PartID', $request->part_id)
            ->whereHas('warehouse', function ($warehouseQuery) {
                $warehouseQuery->where('Active', 1);
            });

        WarehouseAccessCriteria::apply($query);

        if ($request->filled('warehouse_id')) {
            $query->whereIn('WarehouseID', $this->warehouseIdsForFilter($request));
        }

        $this->applyOptionalFilter($query, $request, 'batch_no', 'BatchNo');

        return $query;
    }

    protected function applyOptionalFilter($query, $request, string $input, string $column): void
    {
        if (!$request->filled($input)) {
            return;
        }

        if ($request->input($input) === '__NULL__') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $request->input($input));
    }

    protected function warehouseIdsForFilter($request): array
    {
        $warehouseId = trim((string) $request->input('warehouse_id'));
        if ($warehouseId === '') {
            return [];
        }

        if (!$request->boolean('include_children', true)) {
            return [$warehouseId];
        }

        $warehouses = MsWarehouse::query()->get(['WarehouseID', 'ParentID']);
        $childrenByParent = $warehouses->groupBy(function ($warehouse) {
            return trim((string) $warehouse->ParentID);
        });

        $ids = [];
        $queue = [$warehouseId];

        while ($queue) {
            $currentId = array_shift($queue);
            if (isset($ids[$currentId])) {
                continue;
            }

            $ids[$currentId] = true;

            foreach ($childrenByParent->get($currentId, collect()) as $child) {
                $queue[] = $child->WarehouseID;
            }
        }

        return array_keys($ids);
    }

    protected function formatNotes($row): string
    {
        if ($row->TransactionType === 'ITEMTRANSFER_RECEIVE_ANOMALI') {
            if (strpos((string) $row->Notes, 'Surplus') !== false) {
                return 'Surplus';
            }

            if (strpos((string) $row->Notes, 'Shortage') !== false) {
                return 'Shortage';
            }

            return (string) $row->Notes;
        }

        return '';
    }

    protected function stockConversion(GetStockMovementsRequest $request): float
    {
        if (!$request->filled('unit_id')) {
            return 1;
        }

        $conversion = MsPartUnit::where('PartID', $request->part_id)
            ->where('UnitID2', $request->unit_id)
            ->value('Conversion');

        return $conversion ? (float) $conversion : 1;
    }

}
