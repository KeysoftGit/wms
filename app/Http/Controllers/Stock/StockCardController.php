<?php

namespace App\Http\Controllers\Stock;

use App\Models\ControlPanel;
use Carbon\Carbon;
use App\Models\MsWarehouse;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\BukuStock;
use App\Models\PartBatchCoil;
use App\Models\TransGoodsReceivingDT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class StockCardController extends Controller
{
    public function index()
    {
        return $this->advancedIndex();
    }

    public function advancedIndex()
    {
        $implementWms = !ControlPanel::isEnabled('implement_wms');

        return view('stock.card.advanced', compact('implementWms'));
    }

    public function advancedDatatable(Request $request)
    {
        $request->validate([
            'part_id' => 'required_without:warehouse_id|nullable|string',
            'warehouse_id' => 'required_without:part_id|nullable|string',
            'unit_id' => 'nullable|string',
            'serial_no' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'exp_date' => 'nullable|string',
        ]);

        $query = BukuStock::query();

        if ($request->filled('part_id')) {
            $query->where('PartID', $request->part_id);
        }
        $this->applyWarehouseFilter($query, $request);
        $this->applyOptionalStockFilter($query, $request, 'serial_no', 'SerialNo');
        $this->applyOptionalStockFilter($query, $request, 'batch_no', 'BatchNo');
        $this->applyOptionalStockFilter($query, $request, 'exp_date', 'ExpDate');

        // ambil collection sekali untuk summary
        $stocks = $query->with(['part', 'warehouse'])
            ->orderBy('TransactionDate', 'desc')
            ->get();

        $requestedUnitId = $request->input('unit_id');
        $defaultConversion = $this->defaultUnitConversion($request->part_id, $requestedUnitId);
        $batchUnitRatios = $requestedUnitId
            ? $this->batchUnitRatios($request, $requestedUnitId)
            : collect();
        $coilNoByStockKey = $this->coilNoByStockKey($stocks);

        $stocks->transform(function ($stock) use ($requestedUnitId, $defaultConversion, $batchUnitRatios, $coilNoByStockKey) {
            $stock->DisplayUnitID = $requestedUnitId ?: ($stock->UnitID ?: $this->lowestUnitId($stock->PartID));
            $stock->Qty = $this->displayQty($stock, $requestedUnitId, $defaultConversion, $batchUnitRatios);
            $stock->CoilNo = $this->coilNoForStock($stock, $coilNoByStockKey);

            return $stock;
        });

        $stock_in = $stocks->where('Qty', '>', 0)->sum('Qty');
        $stock_out = $stocks->where('Qty', '<', 0)->sum('Qty');
        $final_stock = $stocks->sum('Qty');

        return DataTables::of($stocks) // <- pake collection, biar konsisten
            ->addColumn('PartName', function ($row) {
                return $row->part ? $row->part->PartName : '-';
            })
            ->addColumn('WarehouseName', function ($row) {
                return $row->warehouse ? $row->warehouse->WarehouseName : null;
            })
            ->addColumn('WarehouseDisplay', function ($row) {
                return $this->joinCodeName($row->WarehouseID, $row->warehouse->WarehouseName ?? null);
            })
            ->editColumn('ExpDate', function ($row) {
                return $row->ExpDate ? Carbon::parse($row->ExpDate)->format('Y-m-d') : null;
            })
            ->editColumn('TransactionDate', function ($row) {
                return $row->TransactionDate ? Carbon::parse($row->TransactionDate)->format('Y-m-d') : null;
            })
            ->with([
                'summary' => [
                    'stock_in' => $stock_in,
                    'stock_out' => abs($stock_out),
                    'final_stock' => $final_stock,
                ]
            ])
            ->make(true);
    }

    public function detail(Request $request)
    {
        $request->validate([
            'part_id' => 'required|string',
            'warehouse_id' => 'nullable|string',
            'unit_id' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'transaction_no' => 'nullable|string',
            'sequence' => 'nullable|string',
        ]);

        $query = BukuStock::query()->where('PartID', $request->part_id);
        $this->applyWarehouseFilter($query, $request);
        $this->applyOptionalStockFilter($query, $request, 'batch_no', 'BatchNo');

        $stocks = $query->with(['part.category', 'part.specification', 'part.variant', 'warehouse'])
            ->orderByRaw('CASE WHEN created_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('created_at', 'asc')
            ->orderBy('TransactionDate', 'asc')
            ->orderBy('TransactionNo', 'asc')
            ->orderBy('Sequence', 'asc')
            ->get();

        $requestedUnitId = $request->input('unit_id');
        $defaultConversion = $this->defaultUnitConversion($request->part_id, $requestedUnitId);
        $batchUnitRatios = $requestedUnitId && $request->filled('part_id')
            ? $this->batchUnitRatios($request, $requestedUnitId)
            : collect();
        $coilNoByStockKey = $this->coilNoByStockKey($stocks);

        $runningStock = 0;
        $stockIn = 0;
        $stockOut = 0;
        $selectedMovement = null;

        $journey = $stocks->map(function ($stock) use ($requestedUnitId, $defaultConversion, $batchUnitRatios, $coilNoByStockKey, &$runningStock, &$stockIn, &$stockOut, &$selectedMovement, $request) {
            $qty = $this->displayQty($stock, $requestedUnitId, $defaultConversion, $batchUnitRatios);
            $initialStock = $runningStock;
            $runningStock += $qty;

            if ($qty > 0) {
                $stockIn += $qty;
            } elseif ($qty < 0) {
                $stockOut += abs($qty);
            }

            $movement = [
                'date' => $stock->created_at ? Carbon::parse($stock->created_at)->format('Y-m-d H:i:s') : '-',
                'warehouse' => $this->joinCodeName($stock->WarehouseID, $stock->warehouse->WarehouseName ?? null),
                'type' => $stock->TransactionType,
                'transaction_no' => $stock->TransactionNo,
                'sequence' => $stock->Sequence,
                'initial' => $initialStock,
                'qty' => $qty,
                'final' => $runningStock,
                'unit' => $requestedUnitId ?: ($stock->UnitID ?: $this->lowestUnitId($stock->PartID)),
                'serial_no' => $stock->SerialNo,
                'batch_no' => $stock->BatchNo,
                'coil_no' => $this->coilNoForStock($stock, $coilNoByStockKey),
                'exp_date' => $stock->ExpDate ? Carbon::parse($stock->ExpDate)->format('Y-m-d') : null,
                'notes' => $stock->Notes ?? '',
            ];

            if ($request->filled('transaction_no')
                && $stock->TransactionNo == $request->transaction_no
                && (!$request->filled('sequence') || (string) $stock->Sequence === (string) $request->sequence)) {
                $selectedMovement = $movement;
            }

            return $movement;
        });

        $part = $stocks->first()?->part ?: MsPart::with(['category', 'specification', 'variant'])->find($request->part_id);

        return response()->json([
            'part' => $part ? [
                'PartID' => $part->PartID,
                'PartName' => $part->PartName,
                'Category' => $this->joinCodeName($part->CategoryID, $part->category->CategoryName ?? null),
                'Specification' => $this->joinCodeName($part->SpecificationID, $part->specification->SpecificationName ?? null),
                'Variant' => $this->joinCodeName($part->VariantID, $part->variant->VariantName ?? null),
            ] : null,
            'movement' => $selectedMovement ?: $journey->last(),
            'journey' => $journey->reverse()->values(),
            'summary' => [
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'final_stock' => $runningStock,
            ],
        ]);
    }

    private function applyOptionalStockFilter($query, Request $request, string $input, string $column): void
    {
        if (!$request->has($input) || $request->input($input) === '') {
            return;
        }

        if ($request->input($input) === '__NULL__') {
            $query->whereNull($column);
            return;
        }

        if ($input === 'exp_date') {
            $query->whereDate($column, $request->input($input));
            return;
        }

        $query->where($column, $request->input($input));
    }

    private function applyWarehouseFilter($query, Request $request): void
    {
        if (!$request->filled('warehouse_id')) {
            return;
        }

        $query->whereIn('WarehouseID', $this->warehouseIdsForFilter($request->input('warehouse_id')));
    }

    private function warehouseIdsForFilter(string $warehouseId): array
    {
        $warehouseId = trim($warehouseId);
        if ($warehouseId === '') {
            return [];
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

    private function displayQty(BukuStock $stock, ?string $requestedUnitId, float $defaultConversion, $batchUnitRatios): float
    {
        if (!$requestedUnitId) {
            return (float) $stock->Qty;
        }

        if ($stock->UnitID2 === $requestedUnitId && (float) ($stock->Qty2 ?? 0) !== 0.0) {
            return (float) $stock->Qty2;
        }

        $batchKey = $this->batchRatioKey($stock->PartID, $stock->BatchNo, $stock->SerialNo, $stock->ExpDate, $requestedUnitId);
        $batchRatio = $batchUnitRatios->get($batchKey);
        if ($batchRatio && abs((float) $batchRatio) > 0.0000001) {
            return (float) $stock->Qty * (float) $batchRatio;
        }

        if ($stock->UnitID === $requestedUnitId) {
            return (float) $stock->Qty;
        }

        return (float) $stock->Qty / $defaultConversion;
    }

    private function batchUnitRatios(Request $request, string $unitId)
    {
        $query = BukuStock::query()
            ->where('PartID', $request->part_id)
            ->where('UnitID2', $unitId)
            ->whereNotNull('Qty2')
            ->where('Qty2', '<>', 0);

        $this->applyWarehouseFilter($query, $request);
        $this->applyOptionalStockFilter($query, $request, 'batch_no', 'BatchNo');
        $this->applyOptionalStockFilter($query, $request, 'serial_no', 'SerialNo');
        $this->applyOptionalStockFilter($query, $request, 'exp_date', 'ExpDate');

        return $query
            ->select(
                'PartID',
                'BatchNo',
                'SerialNo',
                'ExpDate',
                'UnitID2',
                DB::raw('SUM(Qty) as TotalQty'),
                DB::raw('SUM(Qty2) as TotalQty2')
            )
            ->groupBy('PartID', 'BatchNo', 'SerialNo', 'ExpDate', 'UnitID2')
            ->get()
            ->filter(fn ($row) => abs((float) $row->TotalQty) > 0.0000001)
            ->mapWithKeys(fn ($row) => [
                $this->batchRatioKey($row->PartID, $row->BatchNo, $row->SerialNo, $row->ExpDate, $row->UnitID2)
                    => (float) $row->TotalQty2 / (float) $row->TotalQty,
            ]);
    }

    private function defaultUnitConversion(?string $partId, ?string $unitId): float
    {
        if (!$partId || !$unitId) {
            return 1.0;
        }

        return (float) (MsPartUnit::where('PartID', $partId)
            ->where('UnitID2', $unitId)
            ->value('Conversion') ?: 1);
    }

    private function lowestUnitId(string $partId): string
    {
        return (string) (MsPartUnit::where('PartID', $partId)
            ->where('Conversion', 1)
            ->value('UnitID1') ?: '');
    }

    private function batchRatioKey($partId, $batchNo, $serialNo, $expDate, $unitId): string
    {
        return implode('|', [
            $partId ?? '',
            $batchNo ?? '__NULL__',
            $serialNo ?? '__NULL__',
            $this->stockDateKey($expDate),
            $unitId ?? '',
        ]);
    }

    private function stockDateKey($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : '__NULL__';
    }

    private function coilNoByStockKey(Collection $stocks): Collection
    {
        if ($stocks->isEmpty()) {
            return collect();
        }

        $coilNoByStockKey = collect();
        $this->fillCoilNoFromPartBatchCoil($stocks, $coilNoByStockKey);

        if (!Schema::hasColumn('Trans_GoodsReceivingDT', 'CoilNo')) {
            return $coilNoByStockKey;
        }

        $transactionNos = $stocks->pluck('TransactionNo')->filter()->unique()->values();

        foreach ($transactionNos->chunk(800) as $transactionNoChunk) {
            TransGoodsReceivingDT::query()
                ->whereIn('TransactionNo', $transactionNoChunk->all())
                ->get(['TransactionNo', 'PartID', 'Sequence', 'BatchNo', 'CoilNo'])
                ->each(function ($detail) use ($coilNoByStockKey) {
                    $coilNoByStockKey->put($this->stockDetailKey(
                        $detail->TransactionNo,
                        $detail->PartID,
                        $detail->Sequence,
                        $detail->BatchNo
                    ), $detail->CoilNo);

                    if ($detail->CoilNo !== null && $detail->CoilNo !== '') {
                        $coilNoByStockKey->put($this->stockBatchKey($detail->PartID, $detail->BatchNo), $detail->CoilNo);
                    }
                });
        }

        return $coilNoByStockKey;
    }

    private function fillCoilNoFromPartBatchCoil(Collection $stocks, Collection $coilNoByStockKey): void
    {
        if (!Schema::hasTable('PartBatchCoil') || !Schema::hasTable('Ms_Coil')) {
            return;
        }

        $stockKeys = $stocks
            ->filter(fn ($stock) => trim((string) $stock->PartID) !== '' && trim((string) $stock->BatchNo) !== '')
            ->map(fn ($stock) => [
                'part_id' => trim((string) $stock->PartID),
                'batch_no' => trim((string) $stock->BatchNo),
            ])
            ->unique(fn ($item) => strtolower($item['part_id']) . '|' . strtolower($item['batch_no']))
            ->values();

        foreach ($stockKeys->chunk(500) as $chunk) {
            PartBatchCoil::query()
                ->join('Ms_Coil', 'PartBatchCoil.CoilID', '=', 'Ms_Coil.CoilID')
                ->where(function ($query) use ($chunk) {
                    foreach ($chunk as $key) {
                        $query->orWhere(function ($subQuery) use ($key) {
                            $subQuery->where('PartBatchCoil.PartID', $key['part_id'])
                                ->where('PartBatchCoil.BatchNo', $key['batch_no']);
                        });
                    }
                })
                ->get(['PartBatchCoil.PartID', 'PartBatchCoil.BatchNo', 'Ms_Coil.CoilNo'])
                ->each(function ($row) use ($coilNoByStockKey) {
                    if ($row->CoilNo !== null && $row->CoilNo !== '') {
                        $coilNoByStockKey->put($this->stockBatchKey($row->PartID, $row->BatchNo), $row->CoilNo);
                    }
                });
        }
    }

    private function coilNoForStock(BukuStock $stock, Collection $coilNoByStockKey): ?string
    {
        return $coilNoByStockKey->get($this->stockDetailKey(
            $stock->TransactionNo,
            $stock->PartID,
            $stock->Sequence,
            $stock->BatchNo
        )) ?: $coilNoByStockKey->get($this->stockBatchKey($stock->PartID, $stock->BatchNo));
    }

    private function stockDetailKey($transactionNo, $partId, $sequence, $batchNo): string
    {
        return implode('|', array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, [$transactionNo, $partId, $sequence, $batchNo]));
    }

    private function stockBatchKey($partId, $batchNo): string
    {
        return 'batch|' . implode('|', array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, [$partId, $batchNo]));
    }

    private function joinCodeName($code, $name): ?string
    {
        if (!$code && !$name) {
            return null;
        }

        return trim(($code ?: '') . ($name ? ' - ' . $name : ''));
    }
}
