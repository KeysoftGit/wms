<?php

namespace App\Helpers;

use App\Models\BukuStock;
use Illuminate\Support\Facades\Schema;

class DualQuantityHelper
{
    public static function summarySelect(?string $targetUnit = null): string
    {
        if ($targetUnit !== null && trim($targetUnit) !== '') {
            $unit = str_replace("'", "''", trim($targetUnit));

            return "
                SUM(CASE WHEN UnitID2 = '{$unit}' AND Qty2 > 0 THEN Qty2 WHEN UnitID = '{$unit}' AND Qty > 0 THEN Qty ELSE 0 END) as stock_in,
                SUM(CASE WHEN UnitID2 = '{$unit}' AND Qty2 < 0 THEN Qty2 WHEN UnitID = '{$unit}' AND Qty < 0 THEN Qty ELSE 0 END) as stock_out,
                SUM(CASE WHEN UnitID2 = '{$unit}' THEN Qty2 WHEN UnitID = '{$unit}' THEN Qty ELSE 0 END) as final_stock,
                SUM(Qty) as final_stock_qty,
                SUM(CASE WHEN Qty2 > 0 THEN Qty2 ELSE 0 END) as stock_in_qty2,
                SUM(CASE WHEN Qty2 < 0 THEN Qty2 ELSE 0 END) as stock_out_qty2,
                SUM(Qty2) as final_stock_qty2
            ";
        }

        return '
            SUM(CASE WHEN Qty > 0 THEN Qty ELSE 0 END) as stock_in,
            SUM(CASE WHEN Qty < 0 THEN Qty ELSE 0 END) as stock_out,
            SUM(Qty) as final_stock,
            SUM(Qty) as final_stock_qty,
            SUM(CASE WHEN Qty2 > 0 THEN Qty2 ELSE 0 END) as stock_in_qty2,
            SUM(CASE WHEN Qty2 < 0 THEN Qty2 ELSE 0 END) as stock_out_qty2,
            SUM(Qty2) as final_stock_qty2
        ';
    }

    public static function displayMovement($row, ?string $targetUnit = null, float $conversion = 1): array
    {
        $hasUnit2 = $row->UnitID2 !== null && trim((string) $row->UnitID2) !== '';
        $unitId1 = $row->UnitID ?? null;
        $unitId2 = $hasUnit2 ? trim((string) $row->UnitID2) : null;
        $qty1 = (float) $row->Qty;
        $qty2 = ($hasUnit2 && $row->Qty2 !== null) ? (float) $row->Qty2 : null;

        if ($targetUnit !== null && trim($targetUnit) !== '') {
            $targetUnit = trim($targetUnit);
            if ($unitId2 === $targetUnit) {
                $displayQty = $qty2 ?? 0.0;
            } elseif ($unitId1 === $targetUnit) {
                $displayQty = $qty1;
            } else {
                $displayQty = 0.0;
            }
            $displayUnit = $targetUnit;
        } else {
            $displayQty = $conversion != 0 ? $qty1 / $conversion : $qty1;
            $displayUnit = $unitId1;
        }

        return [
            'qty' => $displayQty,
            'unit_id' => $displayUnit,
            'qty2' => $qty2,
            'unit_id2' => $unitId2,
            'weight_per_piece' => self::weightPerPiece($qty1, $qty2),
        ];
    }

    public static function summary($summaryRow, ?string $targetUnit = null, float $conversion = 1): array
    {
        $hasTargetUnit = $targetUnit !== null && trim($targetUnit) !== '';
        $stockIn = (float) ($summaryRow->stock_in ?? 0);
        $stockOut = abs((float) ($summaryRow->stock_out ?? 0));
        $finalStock = (float) ($summaryRow->final_stock ?? 0);

        if (!$hasTargetUnit) {
            $stockIn = $conversion != 0 ? $stockIn / $conversion : $stockIn;
            $stockOut = $conversion != 0 ? $stockOut / $conversion : $stockOut;
            $finalStock = $conversion != 0 ? $finalStock / $conversion : $finalStock;
        }

        $finalStockQty = $summaryRow->final_stock_qty !== null ? (float) $summaryRow->final_stock_qty : null;
        $finalStockQty2 = $summaryRow->final_stock_qty2 !== null ? (float) $summaryRow->final_stock_qty2 : null;

        return [
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
            'final_stock' => $finalStock,
            'final_stock_qty' => $finalStockQty,
            'stock_in_qty2' => (float) ($summaryRow->stock_in_qty2 ?? 0),
            'stock_out_qty2' => abs((float) ($summaryRow->stock_out_qty2 ?? 0)),
            'final_stock_qty2' => $finalStockQty2,
            'weight_per_piece' => self::weightPerPiece($finalStockQty, $finalStockQty2),
        ];
    }

    public static function weightPerPiece(?float $qty, ?float $qty2): ?float
    {
        if ($qty === null || $qty2 === null || $qty2 == 0) {
            return null;
        }

        return $qty / $qty2;
    }

    public static function currentStockSecondaryQuantity(
        string $partId,
        ?string $warehouseId,
        ?string $batchNo = null,
        ?string $serialNo = null,
        ?string $expDate = null,
        ?string $bin = null,
        ?string $loc = null,
        bool $filterNulls = true
    ): array {
        $bukuStock = new BukuStock();
        $schema = Schema::connection($bukuStock->getConnectionName());
        if (!$schema->hasColumn($bukuStock->getTable(), 'Qty2')) {
            return [
                'qty1' => null,
                'qty2' => null,
                'unit_id2' => null,
            ];
        }

        $query = BukuStock::where('PartID', $partId);

        self::applyNullableStockFilter($query, 'WarehouseID', $warehouseId, true);
        self::applyNullableStockFilter($query, 'BatchNo', $batchNo, $filterNulls);
        self::applyNullableStockFilter($query, 'SerialNo', $serialNo, $filterNulls);
        self::applyNullableStockFilter($query, 'ExpDate', $expDate, $filterNulls);
        self::applyNullableStockFilter($query, 'BIN', $bin, $filterNulls);
        self::applyNullableStockFilter($query, 'LOC', $loc, $filterNulls);

        $totals = (clone $query)
            ->selectRaw('SUM(Qty) as qty1')
            ->selectRaw('SUM(Qty2) as qty2')
            ->first();

        $unitId2 = null;
        if ($schema->hasColumn($bukuStock->getTable(), 'UnitID2')) {
            $unitId2 = (clone $query)
                ->whereNotNull('UnitID2')
                ->where('UnitID2', '<>', '')
                ->orderBy('EntryTime', 'desc')
                ->value('UnitID2');
        }

        return [
            'qty1' => $totals->qty1 !== null ? (float) $totals->qty1 : null,
            'qty2' => $totals->qty2 !== null ? (float) $totals->qty2 : null,
            'unit_id2' => $unitId2,
        ];
    }

    public static function proportionalQty2ForStockDelta(
        float $qtyDelta,
        string $partId,
        ?string $warehouseId,
        ?string $batchNo = null,
        ?string $serialNo = null,
        ?string $expDate = null,
        ?string $bin = null,
        ?string $loc = null,
        int $decimals = 2,
        bool $filterNulls = true
    ): array {
        $secondary = self::currentStockSecondaryQuantity($partId, $warehouseId, $batchNo, $serialNo, $expDate, $bin, $loc, $filterNulls);
        $currentQty1 = abs((float) ($secondary['qty1'] ?? 0));
        $currentQty2 = abs((float) ($secondary['qty2'] ?? 0));
        $qty2 = null;

        if ($currentQty1 > 0.000001 && $currentQty2 > 0.000001) {
            $sign = $qtyDelta < 0 ? -1 : 1;
            $qty2 = round(abs($qtyDelta) / $currentQty1 * $currentQty2, $decimals) * $sign;
        }

        return [
            'qty2' => $qty2,
            'unit_id2' => $secondary['unit_id2'],
            'current_qty1' => $secondary['qty1'],
            'current_qty2' => $secondary['qty2'],
        ];
    }

    public static function bukuStockSecondaryColumns(
        float $qtyDelta,
        string $partId,
        ?string $warehouseId,
        ?string $batchNo = null,
        ?string $serialNo = null,
        ?string $expDate = null,
        ?string $bin = null,
        ?string $loc = null,
        bool $filterNulls = true
    ): array {
        $bukuStock = new BukuStock();
        $schema = Schema::connection($bukuStock->getConnectionName());
        $table = $bukuStock->getTable();
        $secondary = self::proportionalQty2ForStockDelta($qtyDelta, $partId, $warehouseId, $batchNo, $serialNo, $expDate, $bin, $loc, 2, $filterNulls);
        $columns = [];

        if ($schema->hasColumn($table, 'Qty2')) {
            $columns['Qty2'] = $secondary['qty2'];
        }

        if ($schema->hasColumn($table, 'UnitID2')) {
            $columns['UnitID2'] = $secondary['unit_id2'];
        }

        return $columns;
    }

    private static function applyNullableStockFilter($query, string $column, $value, bool $filterNulls): void
    {
        if ($value === null || trim((string) $value) === '') {
            if (!$filterNulls) {
                return;
            }

            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
    }
}
