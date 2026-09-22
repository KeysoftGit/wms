<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Books the Inventory Adjustment (stock opname) journal, per adjusted part:
 *  - Qty increase (physical > book): Debit Inventory / Credit Gain on Stock Opname (GAIN_STOCK_OPNAME).
 *  - Qty decrease (physical < book): Debit Loss on Stock Opname (LOSS_STOCK_OPNAME) / Credit Inventory.
 * Amount = abs(Qty) * unit cost, using each part's most recent Material_Cost_Batch.EndingBalance
 * (Inventory Adjustment is not costed per-batch, unlike Part Usage).
 *
 * Mirrored verbatim from kawaguci-nla's app/Services/InventoryAdjustmentJournalService.php since
 * the two apps share the same database but cannot share PHP classes - kawaguci-wms's own
 * StockAdjustmentController (advanced, batch/serial-aware) writes to the very same
 * Trans_InventoryAdjustmentHD/DT/Execution rows kawaguci-nla's simpler controller does, so both
 * apps must compute the journal the same way for a given TransactionNo.
 */
class InventoryAdjustmentJournalService
{
    public static function rebuild(string $transactionNo): void
    {
        $db = DB::connection('sqlsrv');

        $header = $db->table('Trans_InventoryAdjustmentHD')->where('TransactionNo', $transactionNo)->first();

        if (!$header) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: Inventory Adjustment header was not found.");
        }

        $db->table('Trans_JournalDT')->where('TransactionNo', $transactionNo)->delete();
        $db->table('Trans_JournalHD')->where('TransactionNo', $transactionNo)->delete();

        $rows = self::adjustedRows($transactionNo)
            ->filter(fn ($row) => abs((float) $row->Qty) > 0.000001)
            ->values();

        if ($rows->isEmpty()) {
            // No actual quantity movement - nothing to post.
            return;
        }

        self::validateHeader($transactionNo, $header);

        $companyCurrencyId = $db->table('Ms_CompanyProfile')->value('CurrencyID');
        $inventoryTypeIds = $rows->pluck('InventoryTypeID')->all();
        $inventoryAccounts = self::accountMap($inventoryTypeIds, 'INVENTORY_ACCOUNT');
        $gainAccount = self::globalAccount('GAIN_STOCK_OPNAME');
        $lossAccount = self::globalAccount('LOSS_STOCK_OPNAME');
        $costPerUnit = self::costPerUnitMap($rows);

        self::validateLineData($transactionNo, $rows, $companyCurrencyId, $inventoryAccounts, $gainAccount, $lossAccount, $costPerUnit);

        $db->table('Trans_JournalHD')->insert([
            'TransactionNo' => $header->TransactionNo,
            'TransactionDate' => $header->TransactionDate,
            'TransactionType' => 'INVENTORY_ADJUSTMENT',
            'EndPeriodJournal' => 0,
            'CheckList' => 0,
            'Notes' => '',
            'PrintNo' => null,
            'PrintSeq' => null,
            'CustomerID' => null,
            'SupplierID' => null,
            'CreatedBy' => $header->CreatedBy,
            'EntryTime' => $header->EntryTime,
            'Editable' => null,
            'IsAuto' => 0,
            'LastDigit' => null,
            'created_at' => $header->EntryTime,
            'updated_at' => $header->EntryTime,
        ]);

        $notes = "Inventory Adjustment - {$transactionNo}";
        $groups = [];

        foreach ($rows as $row) {
            $qty = (float) $row->Qty;
            $amount = round(abs($qty) * $costPerUnit[$row->PartID], 2);
            $inventoryAccount = $inventoryAccounts[$row->InventoryTypeID];

            if ($qty > 0) {
                // Stock increase: Debit Inventory / Credit Gain on Stock Opname.
                self::addToGroup($groups, $inventoryAccount, $header->DivisionID, $amount, 0.0);
                self::addToGroup($groups, $gainAccount, $header->DivisionID, 0.0, $amount);
            } else {
                // Stock decrease: Debit Loss on Stock Opname / Credit Inventory.
                self::addToGroup($groups, $lossAccount, $header->DivisionID, $amount, 0.0);
                self::addToGroup($groups, $inventoryAccount, $header->DivisionID, 0.0, $amount);
            }
        }

        $journalRows = [];
        foreach ($groups as $group) {
            if ($group['debit'] > 0) {
                $journalRows[] = self::journalRow($transactionNo, $group['account'], $group['division'], $group['debit'], 0.0, $companyCurrencyId, $notes, $header->EntryTime);
            }
            if ($group['credit'] > 0) {
                $journalRows[] = self::journalRow($transactionNo, $group['account'], $group['division'], 0.0, $group['credit'], $companyCurrencyId, $notes, $header->EntryTime);
            }
        }

        foreach (array_chunk($journalRows, 100) as $chunk) {
            if (count($chunk) > 0) {
                $db->table('Trans_JournalDT')->insert($chunk);
            }
        }
    }

    /**
     * Trans_InventoryAdjustment_Execution.Qty is the signed delta (QtyOpname - QtyStock) already
     * written to Buku_Stock by StockAdjustmentController::store()/update() - reused here as-is.
     */
    private static function adjustedRows(string $transactionNo)
    {
        return DB::connection('sqlsrv')
            ->table('Trans_InventoryAdjustment_Execution as ex')
            ->join('Ms_Part as part', 'part.PartID', '=', 'ex.PartID')
            ->where('ex.TransactionNo', $transactionNo)
            ->select([
                'ex.PartID',
                'ex.Qty',
                'part.InventoryTypeID',
            ])
            ->get();
    }

    /**
     * Unit cost per distinct Part adjusted, read from the same Material_Cost_Batch table
     * Goods Receiving / Direct Production Reverse populate. Inventory Adjustment is costed
     * per-part (not per-batch), so the part's latest batch cost is used regardless of batch.
     */
    private static function costPerUnitMap($rows): array
    {
        $map = [];

        if (!Schema::hasTable('Material_Cost_Batch')) {
            return $map;
        }

        $partIds = $rows->pluck('PartID')->unique()->values();

        foreach ($partIds as $partId) {
            $balance = DB::connection('sqlsrv')
                ->table('Material_Cost_Batch')
                ->where('PartID', $partId)
                ->orderByDesc('TransactionDate')
                ->orderByDesc('created_at')
                ->value('EndingBalance');

            if ($balance !== null) {
                $map[$partId] = (float) $balance;
            }
        }

        return $map;
    }

    private static function accountMap(array $inventoryTypeIds, string $accountType): array
    {
        $inventoryTypeIds = array_values(array_unique(array_filter(
            $inventoryTypeIds,
            fn ($id) => trim((string) $id) !== ''
        )));

        if (empty($inventoryTypeIds)) {
            return [];
        }

        $rows = DB::connection('sqlsrv')
            ->table('Ms_InventoryType as type')
            ->leftJoin('Ms_AccountMapping_Inventory as inventory', function ($join) use ($accountType) {
                $join->on('inventory.InventoryTypeID', '=', 'type.InventoryTypeID')
                    ->where('inventory.AccountType', '=', $accountType);
            })
            ->leftJoin('Ms_AccountMapping_Type as inventoryDefault', function ($join) use ($accountType) {
                $join->where('inventoryDefault.AccountType', '=', $accountType);
            })
            ->whereIn('type.InventoryTypeID', $inventoryTypeIds)
            ->select([
                'type.InventoryTypeID',
                DB::raw('COALESCE(inventory.AccountNo, inventoryDefault.AccountNo) as AccountNo'),
            ])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->InventoryTypeID] = $row->AccountNo;
        }

        return $map;
    }

    private static function globalAccount(string $accountType): ?string
    {
        return DB::connection('sqlsrv')
            ->table('Ms_AccountMapping_Type')
            ->where('AccountType', $accountType)
            ->value('AccountNo');
    }

    private static function addToGroup(array &$groups, ?string $account, $division, float $debit, float $credit): void
    {
        $key = $account . '|' . $division;
        $groups[$key] = $groups[$key] ?? ['account' => $account, 'division' => $division, 'debit' => 0.0, 'credit' => 0.0];
        $groups[$key]['debit'] += $debit;
        $groups[$key]['credit'] += $credit;
    }

    private static function validateHeader(string $transactionNo, $header): void
    {
        $errors = [];

        if (self::isBlank($header->TransactionDate ?? null)) {
            $errors[] = 'Transaction date is required.';
        }
        if (self::isBlank($header->CreatedBy ?? null)) {
            $errors[] = 'Created by is required.';
        }
        if (self::isBlank($header->EntryTime ?? null)) {
            $errors[] = 'Entry time is required.';
        }
        if (self::isBlank($header->DivisionID ?? null)) {
            $errors[] = 'Division is required.';
        }

        if (count($errors) > 0) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: " . implode(' ', $errors));
        }
    }

    private static function validateLineData(string $transactionNo, $rows, ?string $companyCurrencyId, array $inventoryAccounts, ?string $gainAccount, ?string $lossAccount, array $costPerUnit): void
    {
        $errors = [];

        if (self::isBlank($companyCurrencyId)) {
            $errors[] = 'Company profile currency is required.';
        }

        if (!Schema::hasTable('Material_Cost_Batch')) {
            $errors[] = 'Material_Cost_Batch table is missing.';
        }

        $hasIncrease = $rows->contains(fn ($row) => (float) $row->Qty > 0);
        $hasDecrease = $rows->contains(fn ($row) => (float) $row->Qty < 0);

        if ($hasIncrease && self::isBlank($gainAccount)) {
            $errors[] = 'Gain on Stock Opname account mapping (GAIN_STOCK_OPNAME) is missing.';
        }
        if ($hasDecrease && self::isBlank($lossAccount)) {
            $errors[] = 'Loss on Stock Opname account mapping (LOSS_STOCK_OPNAME) is missing.';
        }

        foreach ($rows as $row) {
            $part = $row->PartID;
            $inventoryType = trim((string) ($row->InventoryTypeID ?? ''));

            if (!isset($costPerUnit[$part])) {
                $errors[] = "Material cost is not available for Part {$part}. Ensure the part has a Material_Cost_Batch entry.";
            }

            if (self::isBlank($inventoryAccounts[$inventoryType] ?? null)) {
                $errors[] = "Inventory account mapping is missing for Inventory Type {$inventoryType} / Part {$part}.";
            }
        }

        $errors = array_values(array_unique($errors));

        if (count($errors) > 0) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: " . implode(' ', $errors));
        }
    }

    private static function journalRow(string $transactionNo, ?string $accountNo, $divisionId, float $debit, float $credit, ?string $currencyId, string $notes, $timestamp): array
    {
        return [
            'TransactionNo' => $transactionNo,
            'AccountNo' => $accountNo,
            'DivisionID' => $divisionId,
            'VoucherNumber' => '',
            'Debit' => round($debit, 2),
            'Credit' => round($credit, 2),
            'CurrencyID' => $currencyId,
            'Rate' => 1.0,
            'OriginalAmount' => round($debit > 0 ? $debit : $credit, 2),
            'Notes' => $notes,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    private static function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private static function throwValidation(string $message): void
    {
        throw ValidationException::withMessages([
            'journal' => $message,
        ]);
    }
}
