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
