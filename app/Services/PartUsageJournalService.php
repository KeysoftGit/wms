<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books the Part Usage expense entry (Debit each detail line's own AccountNo /
 * Credit Inventory per InventoryTypeID), costed via the same per-batch unit cost
 * (Material_Cost_Batch) as the Purchase-through-Production journal chain in
 * kawaguci-nla. Mirrored verbatim from kawaguci-nla's
 * app/Services/PartUsageJournalService.php since the two apps share the same
 * database but cannot share PHP classes.
 *
 * kawaguci-wms's own PartUsageController (web + api) never assigns AccountNo -
 * only kawaguci-nla's PartUsageController::store()/update() (WMS disabled) or
 * ::updateAccount() (WMS owns creation) does. rebuild() silently skips posting
 * (after clearing any stale journal) until every line has an AccountNo, so
 * calling it from this app's own store/update/destroy is a harmless no-op
 * before accounting finalizes the transaction in kawaguci-nla.
 */
class PartUsageJournalService
{
    public static function rebuild(string $transactionNo): void
    {
        $db = DB::connection('sqlsrv');

        $header = $db->table('Trans_PartUsageHD')->where('TransactionNo', $transactionNo)->first();

        if (!$header) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: Part Usage header was not found.");
        }

        $db->table('Trans_JournalDT')->where('TransactionNo', $transactionNo)->delete();
        $db->table('Trans_JournalHD')->where('TransactionNo', $transactionNo)->delete();

        $rows = self::usedRows($transactionNo);

        if ($rows->isEmpty() || $rows->contains(fn ($row) => self::isBlank($row->AccountNo ?? null))) {
            // Not finalized yet - nothing to post until every line has an expense account.
            return;
        }

        self::validateHeader($transactionNo, $header);

        $companyCurrencyId = $db->table('Ms_CompanyProfile')->value('CurrencyID');
        $inventoryTypeIds = $rows->pluck('InventoryTypeID')->all();
        $inventoryAccounts = self::accountMap($inventoryTypeIds, 'INVENTORY_ACCOUNT');
        $costPerUnit = self::costPerUnitMap($rows);

        self::validateLineData($transactionNo, $rows, $companyCurrencyId, $inventoryAccounts, $costPerUnit);

        $db->table('Trans_JournalHD')->insert([
            'TransactionNo' => $header->TransactionNo,
            'TransactionDate' => $header->TransactionDate,
            'TransactionType' => 'PART_USAGE',
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

        $notes = "Part Usage - {$transactionNo}";
        $debitGroups = [];
        $creditGroups = [];

        foreach ($rows as $row) {
            $qty = abs((float) $row->Qty);
            $cost = $qty * $costPerUnit[self::costKey($row)];

            $debitAccount = $row->AccountNo;
            $debitKey = $debitAccount . '|' . $header->DivisionID;
            $debitGroups[$debitKey] = $debitGroups[$debitKey] ?? ['account' => $debitAccount, 'division' => $header->DivisionID, 'amount' => 0.0];
            $debitGroups[$debitKey]['amount'] += $cost;

            $creditAccount = $inventoryAccounts[$row->InventoryTypeID];
            $creditKey = $creditAccount . '|' . $header->DivisionID;
            $creditGroups[$creditKey] = $creditGroups[$creditKey] ?? ['account' => $creditAccount, 'division' => $header->DivisionID, 'amount' => 0.0];
            $creditGroups[$creditKey]['amount'] += $cost;
        }

        $journalRows = [];
        foreach ($debitGroups as $group) {
            $journalRows[] = self::journalRow($transactionNo, $group['account'], $group['division'], round($group['amount'], 2), 0.0, $companyCurrencyId, $notes, $header->EntryTime);
        }
        foreach ($creditGroups as $group) {
            $journalRows[] = self::journalRow($transactionNo, $group['account'], $group['division'], 0.0, round($group['amount'], 2), $companyCurrencyId, $notes, $header->EntryTime);
        }

        foreach (array_chunk($journalRows, 100) as $chunk) {
            if (count($chunk) > 0) {
                $db->table('Trans_JournalDT')->insert($chunk);
            }
        }

        JournalValidationService::validateBalanced($transactionNo, 'Part Usage');
    }

    /**
     * Trans_PartUsageDT.Qty is already the actual base-unit quantity used (both
     * PartUsageController implementations write the same value into Qty and into
     * Buku_Stock.Qty at insert time) - unlike Purchase Return, there is no separate
     * execute step and no unit-mismatch to correct for here.
     */
    private static function usedRows(string $transactionNo)
    {
        return DB::connection('sqlsrv')
            ->table('Trans_PartUsageDT as dt')
            ->join('Ms_Part as part', 'part.PartID', '=', 'dt.PartID')
            ->where('dt.TransactionNo', $transactionNo)
            ->select([
                'dt.PartID',
                'dt.BatchNo',
                'dt.Qty',
                'dt.AccountNo',
                'part.InventoryTypeID',
            ])
            ->get();
    }

    /**
     * Unit cost per distinct Part/Batch used, read from the same Material_Cost_Batch table
     * Goods Receiving / Direct Production Reverse populate - never recomputed here.
     */
    private static function costPerUnitMap($rows): array
    {
        $map = [];

        $distinct = $rows->map(fn ($row) => [
            'part_id' => $row->PartID,
            'batch_no' => trim((string) ($row->BatchNo ?? '')),
        ])->unique(fn ($item) => $item['part_id'] . '|' . $item['batch_no']);

        foreach ($distinct as $item) {
            if ($item['batch_no'] === '') {
                continue;
            }

            $balance = DB::connection('sqlsrv')
                ->table('Material_Cost_Batch')
                ->where('PartID', $item['part_id'])
                ->where('BatchNumber', $item['batch_no'])
                ->orderByDesc('TransactionDate')
                ->orderByDesc('created_at')
                ->value('EndingBalance');

            if ($balance !== null) {
                $map[$item['part_id'] . '|' . $item['batch_no']] = (float) $balance;
            }
        }

        return $map;
    }

    private static function costKey($row): string
    {
        return $row->PartID . '|' . trim((string) ($row->BatchNo ?? ''));
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

    private static function validateLineData(string $transactionNo, $rows, ?string $companyCurrencyId, array $inventoryAccounts, array $costPerUnit): void
    {
        $errors = [];

        if (self::isBlank($companyCurrencyId)) {
            $errors[] = 'Company profile currency is required.';
        }

        foreach ($rows as $row) {
            $part = $row->PartID;
            $inventoryType = trim((string) ($row->InventoryTypeID ?? ''));
            $batchNo = trim((string) ($row->BatchNo ?? ''));

            if ($batchNo === '') {
                $errors[] = "Batch No is required to cost used Part {$part}.";
            } elseif (!isset($costPerUnit[self::costKey($row)])) {
                $errors[] = "Material cost is not available for Part {$part} batch {$batchNo}. Ensure the batch has a Material_Cost_Batch entry.";
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
