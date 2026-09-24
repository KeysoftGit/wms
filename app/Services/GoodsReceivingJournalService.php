<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoodsReceivingJournalService
{
    public static function rebuild(string $transactionNo): void
    {
        $db = DB::connection('sqlsrv');

        $header = $db->table('Trans_GoodsReceivingHD as gr')
            ->join('Trans_QualityControlReceivingHD as qc', 'qc.TransactionNo', '=', 'gr.QCNumber')
            ->join('Trans_PurchaseOrderHD as po', 'po.TransactionNo', '=', 'qc.PONumber')
            ->where('gr.TransactionNo', $transactionNo)
            ->select([
                'gr.TransactionNo',
                'gr.TransactionDate',
                'po.SupplierID',
                'gr.CreatedBy',
                'gr.EntryTime',
            ])
            ->first();

        if (!$header) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: Goods Receiving, QC, or Purchase Order reference was not found.");
        }

        $baseRows = self::baseDetailRows($transactionNo);
        self::validateRequiredData($transactionNo, $header, $baseRows);

        $db->table('Trans_JournalDT')->where('TransactionNo', $transactionNo)->delete();
        $db->table('Trans_JournalHD')->where('TransactionNo', $transactionNo)->delete();

        $journalRows = array_merge(
            self::inventoryRows($baseRows),
            self::unbilledRows($baseRows)
        );
        $journalRows = array_values(array_filter($journalRows, [self::class, 'hasJournalAmount']));

        if (count($journalRows) > 0) {
            $db->table('Trans_JournalHD')->insert([
                'TransactionNo' => $header->TransactionNo,
                'TransactionDate' => $header->TransactionDate,
                'TransactionType' => 'GOODS_RECEIVING',
                'EndPeriodJournal' => 0,
                'CheckList' => 0,
                'Notes' => '',
                'PrintNo' => null,
                'PrintSeq' => null,
                'CustomerID' => null,
                'SupplierID' => $header->SupplierID,
                'CreatedBy' => $header->CreatedBy,
                'EntryTime' => $header->EntryTime,
                'Editable' => null,
                'IsAuto' => 0,
                'LastDigit' => null,
                'created_at' => $header->EntryTime,
                'updated_at' => $header->EntryTime,
            ]);

            foreach (array_chunk($journalRows, 100) as $chunk) {
                if (count($chunk) > 0) {
                    $db->table('Trans_JournalDT')->insert($chunk);
                }
            }
        }

        if (Schema::connection('sqlsrv')->hasTable('Material_Cost_Batch')) {
            $db->table('Material_Cost_Batch')->where('TransactionNo', $transactionNo)->delete();

            $materialCostBatchRows = self::materialCostBatchRows($baseRows);

            foreach (array_chunk($materialCostBatchRows, 100) as $chunk) {
                if (count($chunk) > 0) {
                    $db->table('Material_Cost_Batch')->insert($chunk);
                }
            }
        }

        if (Schema::connection('sqlsrv')->hasTable('Material_Cost_Batch_Detail')) {
            $db->table('Material_Cost_Batch_Detail')->where('TransactionNo', $transactionNo)->delete();

            $materialCostBatchDetailRows = self::materialCostBatchDetailRows($baseRows);

            foreach (array_chunk($materialCostBatchDetailRows, 100) as $chunk) {
                if (count($chunk) > 0) {
                    $db->table('Material_Cost_Batch_Detail')->insert($chunk);
                }
            }
        }

        if (count($journalRows) > 0) {
            JournalValidationService::validateBalanced($transactionNo, 'Goods Receiving');
        }
    }

    /**
     * One row per Goods Receiving detail line that has a batch number - the unit cost
     * (goods value plus its share of Rate Bea Masuk / Anti Dumping duty, both converted to
     * IDR) landed by that specific receipt, divided by the quantity received. Beginning and
     * Ending balance are the same value because each GR line establishes a fresh batch.
     */
    private static function materialCostBatchRows($baseRows): array
    {
        $rows = [];

        foreach ($baseRows as $row) {
            $batchNo = trim((string) ($row->BatchNo ?? ''));

            if ($batchNo === '') {
                continue;
            }

            $receivedQty = (float) ($row->ReceivedQty ?? 0);

            if ($receivedQty == 0.0) {
                continue;
            }

            $goodsIdr = round(self::subtotalOriginal($row) * (float) $row->Rate, 6);
            $dutyIdr = round(
                ((float) ($row->RateBeaMasuk ?? 0) + (float) ($row->AntiDumping ?? 0)) * (float) ($row->FiscalRate ?? 0),
                6
            );
            $balance = round(($goodsIdr + $dutyIdr) / $receivedQty, 6);

            $rows[] = [
                'TransactionNo' => $row->TransactionNo,
                'Period' => date('Ym', strtotime($row->TransactionDate)),
                'PartID' => $row->PartID,
                'BatchNumber' => $batchNo,
                'BeginningBalance' => $balance,
                'EndingBalance' => $balance,
                'TransactionDate' => $row->TransactionDate,
                'created_at' => $row->EntryTime,
                'updated_at' => $row->LastUpdate,
            ];
        }

        return $rows;
    }

    /**
     * Full cost breakdown behind each Material_Cost_Batch row - kept as its own table so the
     * goods/duty split (and the rates/quantities that produced it) can be shown to the user,
     * instead of just the final per-unit balance.
     */
    private static function materialCostBatchDetailRows($baseRows): array
    {
        $rows = [];

        foreach ($baseRows as $row) {
            $batchNo = trim((string) ($row->BatchNo ?? ''));

            if ($batchNo === '') {
                continue;
            }

            $receivedQty = (float) ($row->ReceivedQty ?? 0);

            if ($receivedQty == 0.0) {
                continue;
            }

            $rate = (float) $row->Rate;
            $fiscalRate = (float) ($row->FiscalRate ?? 0);
            $rateBeaMasuk = (float) ($row->RateBeaMasuk ?? 0);
            $antiDumping = (float) ($row->AntiDumping ?? 0);

            $goodsIdr = round(self::subtotalOriginal($row) * $rate, 6);
            $beaMasukIdr = round($rateBeaMasuk * $fiscalRate, 6);
            $antiDumpingIdr = round($antiDumping * $fiscalRate, 6);
            $totalIdr = $goodsIdr + $beaMasukIdr + $antiDumpingIdr;
            $balance = round($totalIdr / $receivedQty, 6);

            $rows[] = [
                'TransactionNo' => $row->TransactionNo,
                'PONumber' => $row->PONumber,
                'PartID' => $row->PartID,
                'BatchNumber' => $batchNo,
                'Qty' => $receivedQty,
                'UnitPrice' => (float) ($row->UnitPrice ?? 0),
                'RateBeaMasuk' => $rateBeaMasuk,
                'AntiDumping' => $antiDumping,
                'Rate' => $rate,
                'FiscalRate' => $fiscalRate,
                'SubtotalInventoryIDR' => $goodsIdr,
                'SubtotalBeaMasukIDR' => $beaMasukIdr,
                'SubtotalAntiDumpingIDR' => $antiDumpingIdr,
                'SubtotalTotalIDR' => $totalIdr,
                'EndingBalance' => $balance,
                'TransactionDate' => $row->TransactionDate,
                'created_at' => $row->EntryTime,
                'updated_at' => $row->LastUpdate,
            ];
        }

        return $rows;
    }

    private static function baseDetailRows(string $transactionNo)
    {
        $hasFiscalRate = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingHD', 'FiscalRate');
        $hasRateBeaMasuk = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'RateBeaMasuk');
        $hasRateBeaAccount = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'RateBeaAccount');
        $hasAntiDumping = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'AntiDumping');
        $hasAntiDumpingAccount = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'AntiDumpingAccount');

        return DB::connection('sqlsrv')
            ->table('Trans_GoodsReceivingHD as gr')
            ->join('Trans_GoodsReceivingDT as grdt', 'grdt.TransactionNo', '=', 'gr.TransactionNo')
            ->join('Ms_Part as part', 'part.PartID', '=', 'grdt.PartID')
            ->join('Trans_QualityControlReceivingHD as qc', 'qc.TransactionNo', '=', 'gr.QCNumber')
            ->join('Trans_PurchaseOrderHD as po', 'po.TransactionNo', '=', 'qc.PONumber')
            ->join('Trans_PurchaseOrderDT as podt', function ($join) {
                $join->on('podt.TransactionNo', '=', 'po.TransactionNo')
                    ->on('podt.PartID', '=', 'grdt.PartID')
                    ->on('podt.Sequence', '=', 'grdt.Sequence');
            })
            ->leftJoin('Ms_AccountMapping_Inventory as unbilled', function ($join) {
                $join->on('unbilled.InventoryTypeID', '=', 'part.InventoryTypeID')
                    ->where('unbilled.AccountType', '=', 'UNBILLED_RECEIVED_OF_INVENTORY');
            })
            ->leftJoin('Ms_AccountMapping_Type as unbilledDefault', function ($join) {
                $join->where('unbilledDefault.AccountType', '=', 'UNBILLED_RECEIVED_OF_INVENTORY');
            })
            ->leftJoin('Ms_AccountMapping_Inventory as inventory', function ($join) {
                $join->on('inventory.InventoryTypeID', '=', 'part.InventoryTypeID')
                    ->where('inventory.AccountType', '=', 'INVENTORY_ACCOUNT');
            })
            ->leftJoin('Ms_AccountMapping_Type as inventoryDefault', function ($join) {
                $join->where('inventoryDefault.AccountType', '=', 'INVENTORY_ACCOUNT');
            })
            ->where('gr.TransactionNo', $transactionNo)
            ->select([
                'gr.TransactionNo',
                'gr.TransactionDate',
                'gr.Rate',
                'gr.CurrencyID',
                'gr.EntryTime',
                'gr.LastUpdate',
                'grdt.PartID',
                'grdt.Sequence',
                'grdt.BatchNo',
                'grdt.Qty as ReceivedQty',
                'part.InventoryTypeID',
                'part.VAT2',
                'podt.UnitPrice',
                'podt.Discount',
                'podt.Conversion',
                'podt.Qty as PurchaseQty',
                'podt.DivisionID',
                'po.TransactionNo as PONumber',
                'po.VAT',
                DB::raw('COALESCE(inventory.AccountNo, inventoryDefault.AccountNo) as AccountInventory'),
                DB::raw('COALESCE(unbilled.AccountNo, unbilledDefault.AccountNo) as AccountUnbilled'),
                $hasFiscalRate ? 'gr.FiscalRate' : DB::raw('CAST(1 AS FLOAT) as FiscalRate'),
                $hasRateBeaMasuk ? 'grdt.RateBeaMasuk' : DB::raw('CAST(0 AS FLOAT) as RateBeaMasuk'),
                $hasRateBeaAccount ? 'grdt.RateBeaAccount' : DB::raw('CAST(NULL AS NVARCHAR(50)) as RateBeaAccount'),
                $hasAntiDumping ? 'grdt.AntiDumping' : DB::raw('CAST(0 AS FLOAT) as AntiDumping'),
                $hasAntiDumpingAccount ? 'grdt.AntiDumpingAccount' : DB::raw('CAST(NULL AS NVARCHAR(50)) as AntiDumpingAccount'),
            ])
            ->get();
    }

    private static function validateRequiredData(string $transactionNo, $header, $baseRows): void
    {
        $errors = [];

        foreach ([
            'TransactionDate' => 'transaction date',
            'SupplierID' => 'supplier',
            'CreatedBy' => 'created by',
            'EntryTime' => 'entry time',
        ] as $field => $label) {
            if (self::isBlank($header->{$field} ?? null)) {
                $errors[] = "Journal header requires {$label}.";
            }
        }

        if ($baseRows->isEmpty()) {
            $detailCount = DB::connection('sqlsrv')
                ->table('Trans_GoodsReceivingDT')
                ->where('TransactionNo', $transactionNo)
                ->count();

            $errors[] = $detailCount > 0
                ? 'Journal detail requires valid Part and Purchase Order detail references for each Goods Receiving detail row.'
                : 'Journal detail requires at least one Goods Receiving detail row.';
        }

        foreach (self::missingDetailReferences($transactionNo) as $row) {
            if (self::isBlank($row->PartExists)) {
                $errors[] = "Part {$row->PartID} is missing in master part.";
            }

            if (self::isBlank($row->PurchaseOrderDetailExists)) {
                $errors[] = "PO detail is missing for Part {$row->PartID}, Sequence {$row->Sequence}.";
            }
        }

        foreach ($baseRows as $row) {
            $part = trim((string) ($row->PartID ?? ''));
            $sequence = trim((string) ($row->Sequence ?? ''));
            $inventoryType = trim((string) ($row->InventoryTypeID ?? ''));

            if (self::isBlank($row->CurrencyID ?? null)) {
                $errors[] = "Currency is required for {$transactionNo}.";
            }

            if ((float) ($row->Rate ?? 0) == 0.0) {
                $errors[] = "Rate must be greater than 0 for {$transactionNo}.";
            }

            if (self::isBlank($row->DivisionID ?? null)) {
                $errors[] = "Division is required for PO detail Part {$part}, Sequence {$sequence}.";
            }

            if (self::isBlank($row->AccountInventory ?? null)) {
                $errors[] = "Inventory account mapping is missing for Inventory Type {$inventoryType} / Part {$part}.";
            }

            if (self::isBlank($row->AccountUnbilled ?? null)) {
                $errors[] = "Unbilled received inventory account mapping is missing for Inventory Type {$inventoryType} / Part {$part}.";
            }

            if ((float) ($row->Conversion ?? 0) == 0.0) {
                $errors[] = "Conversion must be greater than 0 for PO detail Part {$part}, Sequence {$sequence}.";
            }

            if (strtoupper((string) ($row->VAT ?? '')) === 'I' && (float) ($row->PurchaseQty ?? 0) == 0.0) {
                $errors[] = "PO quantity must be greater than 0 for VAT inclusive PO detail Part {$part}, Sequence {$sequence}.";
            }

            $hasDuty = (float) ($row->RateBeaMasuk ?? 0) != 0.0 || (float) ($row->AntiDumping ?? 0) != 0.0;

            if ($hasDuty && (float) ($row->FiscalRate ?? 0) == 0.0) {
                $errors[] = "Fiscal rate must be greater than 0 for {$transactionNo}.";
            }

            if ((float) ($row->RateBeaMasuk ?? 0) != 0.0 && self::isBlank($row->RateBeaAccount ?? null)) {
                $errors[] = "Rate Bea Masuk account is missing for PO detail Part {$part}, Sequence {$sequence}.";
            }

            if ((float) ($row->AntiDumping ?? 0) != 0.0 && self::isBlank($row->AntiDumpingAccount ?? null)) {
                $errors[] = "Anti Dumping account is missing for PO detail Part {$part}, Sequence {$sequence}.";
            }
        }

        $errors = array_values(array_unique($errors));

        if (count($errors) > 0) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: " . implode(' ', $errors));
        }
    }

    private static function missingDetailReferences(string $transactionNo)
    {
        return DB::connection('sqlsrv')
            ->table('Trans_GoodsReceivingHD as gr')
            ->join('Trans_GoodsReceivingDT as grdt', 'grdt.TransactionNo', '=', 'gr.TransactionNo')
            ->leftJoin('Ms_Part as part', 'part.PartID', '=', 'grdt.PartID')
            ->leftJoin('Trans_QualityControlReceivingHD as qc', 'qc.TransactionNo', '=', 'gr.QCNumber')
            ->leftJoin('Trans_PurchaseOrderHD as po', 'po.TransactionNo', '=', 'qc.PONumber')
            ->leftJoin('Trans_PurchaseOrderDT as podt', function ($join) {
                $join->on('podt.TransactionNo', '=', 'po.TransactionNo')
                    ->on('podt.PartID', '=', 'grdt.PartID')
                    ->on('podt.Sequence', '=', 'grdt.Sequence');
            })
            ->where('gr.TransactionNo', $transactionNo)
            ->where(function ($query) {
                $query->whereNull('part.PartID')
                    ->orWhereNull('podt.TransactionNo');
            })
            ->select([
                'grdt.PartID',
                'grdt.Sequence',
                'part.PartID as PartExists',
                'podt.TransactionNo as PurchaseOrderDetailExists',
            ])
            ->get();
    }

    private static function inventoryRows($baseRows): array
    {
        $groups = [];

        foreach ($baseRows as $row) {
            $accountNo = $row->AccountInventory;
            $key = implode('|', [$row->TransactionNo, $accountNo, $row->DivisionID]);
            $goodsIdr = round(self::subtotalOriginal($row) * (float) $row->Rate, 6);
            $dutyIdr = round(
                ((float) ($row->RateBeaMasuk ?? 0) + (float) ($row->AntiDumping ?? 0)) * (float) ($row->FiscalRate ?? 0),
                6
            );
            $idr = $goodsIdr + $dutyIdr;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'TransactionNo' => $row->TransactionNo,
                    'AccountNo' => $accountNo,
                    'DivisionID' => $row->DivisionID,
                    'CurrencyID' => $row->CurrencyID,
                    'Rate' => 1.0,
                    'PONumber' => $row->PONumber,
                    'VAT' => $row->VAT,
                    'created_at' => $row->EntryTime,
                    'updated_at' => $row->LastUpdate,
                    'amount' => 0.0,
                    'idr' => 0.0,
                ];
            }

            $groups[$key]['amount'] += $idr;
            $groups[$key]['idr'] += $idr;
        }

        return array_map(static function (array $group): array {
            return self::journalRow($group, round($group['idr'], 2), 0.0);
        }, array_values($groups));
    }

    /**
     * Credits the full landed cost - goods plus its share of Rate Bea Masuk / Anti Dumping
     * duty - to the unbilled-received account, mirroring inventoryRows()'s debit side line for
     * line. At Goods Receiving time the duty is only an estimate folded into the landed cost,
     * not yet a payable owed to a specific party, so it stays combined here (Rate forced to 1.0,
     * same as inventoryRows(), since goods and duty convert at different rates - Rate vs
     * FiscalRate - once merged into a single IDR figure). It only becomes a real liability on
     * RateBeaAccount / AntiDumpingAccount when the actual duty invoice is booked
     * (PurchaseInvoiceJournalService::rebuildFreightCost), at which point that invoice's own
     * amounts - not this GR posting - drive the split.
     */
    private static function unbilledRows($baseRows): array
    {
        $groups = [];

        foreach ($baseRows as $row) {
            $accountNo = $row->AccountUnbilled;
            $key = implode('|', [$row->TransactionNo, $accountNo, $row->DivisionID]);
            $goodsIdr = round(self::subtotalOriginal($row) * (float) $row->Rate, 6);
            $dutyIdr = round(
                ((float) ($row->RateBeaMasuk ?? 0) + (float) ($row->AntiDumping ?? 0)) * (float) ($row->FiscalRate ?? 0),
                6
            );
            $idr = $goodsIdr + $dutyIdr;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'TransactionNo' => $row->TransactionNo,
                    'AccountNo' => $accountNo,
                    'DivisionID' => $row->DivisionID,
                    'CurrencyID' => $row->CurrencyID,
                    'Rate' => 1.0,
                    'PONumber' => $row->PONumber,
                    'VAT' => $row->VAT,
                    'created_at' => $row->EntryTime,
                    'updated_at' => $row->LastUpdate,
                    'amount' => 0.0,
                    'idr' => 0.0,
                ];
            }

            $groups[$key]['amount'] += $idr;
            $groups[$key]['idr'] += $idr;
        }

        return array_map(static function (array $group): array {
            return self::journalRow($group, 0.0, round($group['idr'], 2));
        }, array_values($groups));
    }

    private static function hasJournalAmount(array $row): bool
    {
        return abs((float) ($row['Debit'] ?? 0)) > 0.000001
            || abs((float) ($row['Credit'] ?? 0)) > 0.000001
            || abs((float) ($row['OriginalAmount'] ?? 0)) > 0.000001;
    }

    private static function subtotalOriginal($row): float
    {
        $unitPrice = (float) ($row->UnitPrice ?? 0);
        $discount = (float) ($row->Discount ?? 0);
        $receivedQty = (float) ($row->ReceivedQty ?? 0);
        $conversion = (float) ($row->Conversion ?? 0);
        $vat2 = (float) ($row->VAT2 ?? 0);
        $vat = strtoupper((string) ($row->VAT ?? ''));

        $amount = (($unitPrice - $discount) / $conversion) * $receivedQty;

        if ($vat === 'I') {
            $amount = $amount / (1 + ($vat2 / 100));
        }

        return $amount;
    }

    private static function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private static function journalRow(array $group, float $debit, float $credit): array
    {
        return [
            'TransactionNo' => $group['TransactionNo'],
            'AccountNo' => $group['AccountNo'],
            'DivisionID' => $group['DivisionID'],
            'VoucherNumber' => '',
            'Debit' => $debit,
            'Credit' => $credit,
            'CurrencyID' => $group['CurrencyID'],
            'Rate' => $group['Rate'],
            'OriginalAmount' => $group['amount'],
            'Notes' => 'PO Number = ' . $group['PONumber'] . ' with VAT= ' . $group['VAT'],
            'created_at' => $group['created_at'],
            'updated_at' => $group['updated_at'],
        ];
    }

    private static function throwValidation(string $message): void
    {
        throw ValidationException::withMessages([
            'journal' => $message,
        ]);
    }
}
