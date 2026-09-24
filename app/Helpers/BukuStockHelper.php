<?php

namespace App\Helpers;

use App\Models\BukuStock;

class BukuStockHelper
{
    /**
     * Calculate current stock for a specific part based on Buku_Stock history.
     *
     * @param string $partId
     * @param string|null $warehouseId
     * @param string|null $batchNo
     * @return float
     */
    public static function calculateCurrentStock(
        string $partId,
        ?string $warehouseId = null,
        ?string $batchNo = null,
        ?string $serialNo = null,
        ?string $expDate = null,
        ?string $bin = null,
        ?string $loc = null
    ): float {
        $argumentCount = func_num_args();
        $query = BukuStock::where('PartID', $partId);

        self::applyNullableFilter($query, $argumentCount, 2, 'WarehouseID', $warehouseId);
        self::applyNullableFilter($query, $argumentCount, 3, 'BatchNo', $batchNo);

        return (float) ($query->sum('Qty') ?? 0);
    }

    /**
     * Calculate current stock using Batch No as the only stock detail identity.
     */
    public static function calculateCurrentStockByBatchNo(
        string $partId,
        ?string $warehouseId,
        ?string $batchNo,
        string $transactionDate
    ): float {
        $argumentCount = func_num_args();
        $query = BukuStock::where('PartID', $partId);

        self::applyNullableFilter($query, $argumentCount, 2, 'WarehouseID', $warehouseId);
        self::applyNullableFilter($query, $argumentCount, 3, 'BatchNo', $batchNo);
        $query->whereDate('TransactionDate', '<=', $transactionDate);

        return (float) ($query->sum('Qty') ?? 0);
    }

    public static function validateSerialStockDoesNotExceedOne(array $serialStockDeltas, bool $includeWarehouse = false): void
    {
        return;
    }

    /**
     * Batches currently holding stock for a part/warehouse, oldest-arrival first (FIFO).
     * A parent warehouse pools stock across itself and every descendant - each (BatchNo,
     * WarehouseID) pair is its own lot, so a batch split across two child warehouses is
     * tracked and consumed as two separate lots. A leaf/child warehouse is strict: only its
     * own stock is considered. "Arrival" is a lot's own earliest Buku_Stock entry - ties on
     * EntryTime (common, since a Goods Receiving posts every batch line with the same
     * timestamp) break on that same earliest row's TransactionNo then Sequence.
     */
    public static function fifoBatches(string $partId, string $warehouseId): \Illuminate\Support\Collection
    {
        $warehouseIds = WarehouseTreeHelper::descendantsAndSelf($warehouseId);

        return BukuStock::query()
            ->join('Ms_Warehouse as fw', 'fw.WarehouseID', '=', 'Buku_Stock.WarehouseID')
            ->where('Buku_Stock.PartID', $partId)
            ->whereIn('Buku_Stock.WarehouseID', $warehouseIds)
            ->whereNotNull('Buku_Stock.BatchNo')
            ->select('Buku_Stock.BatchNo', 'Buku_Stock.WarehouseID')
            ->selectRaw('MAX(fw.WarehouseName) as WarehouseName')
            ->selectRaw('SUM(Buku_Stock.Qty) as TotalQty')
            ->selectRaw('SUM(Buku_Stock.Qty2) as TotalQty2')
            ->selectRaw('MAX(Buku_Stock.UnitID2) as UnitID2')
            ->selectRaw('MIN(Buku_Stock.EntryTime) as FirstEntryTime')
            ->selectRaw('MIN(Buku_Stock.TransactionNo) as FirstTransactionNo')
            ->selectRaw('MIN(Buku_Stock.Sequence) as FirstSequence')
            ->groupBy('Buku_Stock.BatchNo', 'Buku_Stock.WarehouseID')
            ->havingRaw('SUM(Buku_Stock.Qty) > 0.000001')
            ->orderBy('FirstEntryTime')
            ->orderBy('FirstTransactionNo')
            ->orderBy('FirstSequence')
            ->get();
    }

    /**
     * Greedily consume the oldest-arrival lots first until $qtyNeeded is covered, splitting
     * across as many batches/warehouses as required (see fifoBatches()). Throws "Out of Stock"
     * if the total available across the resolved warehouse(s) is short.
     *
     * @return array<int, array{BatchNo: string, WarehouseID: string, WarehouseName: ?string, Qty: float, StockQty: float, StockQty2: float, UnitID2: ?string}>
     */
    public static function allocateFifo(string $partId, string $warehouseId, float $qtyNeeded): array
    {
        if ($qtyNeeded <= 0.000001) {
            return [];
        }

        $remaining = $qtyNeeded;
        $allocations = [];

        foreach (self::fifoBatches($partId, $warehouseId) as $batch) {
            if ($remaining <= 0.000001) {
                break;
            }

            $available = (float) $batch->TotalQty;
            if ($available <= 0.000001) {
                continue;
            }

            $take = min($remaining, $available);
            $ratio = $available > 0.000001 ? ((float) $batch->TotalQty2) / $available : 0.0;

            $allocations[] = [
                'BatchNo' => $batch->BatchNo,
                'WarehouseID' => $batch->WarehouseID,
                'WarehouseName' => $batch->WarehouseName,
                'Qty' => $take,
                'StockQty' => $available,
                'StockQty2' => (float) $batch->TotalQty2,
                'UnitID2' => $batch->UnitID2,
                'SourceQty' => $take * $ratio,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0.000001) {
            $totalAvailable = $qtyNeeded - $remaining;
            throw new \Exception("Out of Stock. Only {$totalAvailable} available for this part in the selected warehouse (requested {$qtyNeeded}).");
        }

        return $allocations;
    }

    private static function applyNullableFilter($query, int $argumentCount, int $position, string $column, ?string $value, bool $date = false): void
    {
        if ($argumentCount < $position) {
            return;
        }

        if ($value === null || trim($value) === '' || $value === '__NULL__') {
            $query->whereNull($column);
            return;
        }

        if ($date) {
            $query->whereDate($column, $value);
            return;
        }

        $query->where($column, $value);
    }
}
