<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books the delivery-time inventory reduction immediately (Debit Item Delivered Unbilled /
 * Credit Inventory), using the same per-batch unit cost (Material_Cost_Batch) as the
 * Purchase-through-Production journal chain in kawaguci-nla - replaces the legacy
 * "exec sp_jurnal_delivery_order" stored procedure call from do-execute. Mirrored verbatim
 * from kawaguci-nla's app/Services/DeliveryOrderJournalService.php since the two apps share
 * the same database but cannot share PHP classes.
 */
class DeliveryOrderJournalService
{
    public static function rebuild(string $transactionNo): void
    {
        $db = DB::connection('sqlsrv');

        $header = $db->table('Trans_DeliveryOrderHD')
            ->where('TransactionNo', $transactionNo)
            ->first();

        if (!$header) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: Delivery Order header was not found.");
        }

        self::validateHeader($transactionNo, $header);

        $db->table('Trans_JournalDT')->where('TransactionNo', $transactionNo)->delete();
        $db->table('Trans_JournalHD')->where('TransactionNo', $transactionNo)->delete();

        $rows = self::shippedRows($transactionNo);

        if ($rows->isEmpty()) {
            // Nothing physically shipped yet (not executed, or every line was a service
            // part with no stock movement) - no COGS impact, so no journal to post.
            return;
        }

        $companyCurrencyId = $db->table('Ms_CompanyProfile')->value('CurrencyID');
        $inventoryTypeIds = $rows->pluck('InventoryTypeID')->all();
        $unbilledAccounts = self::accountMap($inventoryTypeIds, 'ITEM_DELIVERED_UNBILLED');
        $inventoryAccounts = self::accountMap($inventoryTypeIds, 'INVENTORY_ACCOUNT');
        $costPerUnit = self::costPerUnitMap($rows);

        self::validateLineData($transactionNo, $rows, $companyCurrencyId, $unbilledAccounts, $inventoryAccounts, $costPerUnit);

        $db->table('Trans_JournalHD')->insert([
            'TransactionNo' => $header->TransactionNo,
            'TransactionDate' => $header->TransactionDate,
            'TransactionType' => 'DELIVERY_ORDER',
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

        $notes = "Delivery Order - {$transactionNo}";
        $debitGroups = [];
        $creditGroups = [];

        foreach ($rows as $row) {
            $qty = abs((float) $row->Qty);
            $cost = $qty * $costPerUnit[self::costKey($row)];

            $debitAccount = $unbilledAccounts[$row->InventoryTypeID];
            $debitKey = $debitAccount . '|' . $row->DivisionID;
            $debitGroups[$debitKey] = $debitGroups[$debitKey] ?? ['account' => $debitAccount, 'division' => $row->DivisionID, 'amount' => 0.0];
            $debitGroups[$debitKey]['amount'] += $cost;

            $creditAccount = $inventoryAccounts[$row->InventoryTypeID];
            $creditKey = $creditAccount . '|' . $row->DivisionID;
            $creditGroups[$creditKey] = $creditGroups[$creditKey] ?? ['account' => $creditAccount, 'division' => $row->DivisionID, 'amount' => 0.0];
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
    }

    /**
     * What actually left the warehouse for this DO - read from Buku_Stock (the definitive
     * execution record) rather than Trans_DeliveryOrderDT (the originally planned quantity/
     * batch), joined back to Trans_DeliveryOrderDT only for Division.
     */
    private static function shippedRows(string $transactionNo)
    {
        return DB::connection('sqlsrv')
            ->table('Buku_Stock as bs')
            ->join('Trans_DeliveryOrderDT as dt', function ($join) {
                // PartID+Sequence alone is not unique when one SO/DO line was split across
                // multiple batches (e.g. produced as several Direct Production Reverse output
                // batches) - matching Batch No too avoids fanning one Buku_Stock row out
                // against every batch-split detail row for that line.
                $join->on('dt.TransactionNo', '=', 'bs.TransactionNo')
                    ->on('dt.PartID', '=', 'bs.PartID')
                    ->on('dt.Sequence', '=', 'bs.Sequence')
                    ->on(DB::raw("ISNULL(dt.BatchNo, '')"), '=', DB::raw("ISNULL(bs.BatchNo, '')"));
            })
            ->join('Ms_Part as part', 'part.PartID', '=', 'bs.PartID')
            ->where('bs.TransactionNo', $transactionNo)
            ->where('bs.TransactionType', 'DELIVERY_ORDER')
            ->select([
                'bs.PartID',
                'bs.Sequence',
                'bs.BatchNo',
                'bs.Qty',
                'dt.DivisionID',
                'part.InventoryTypeID',
            ])
            ->get();
    }

    /**
     * Unit cost per distinct Part/Batch shipped, read from the same Material_Cost_Batch table
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

        if (count($errors) > 0) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: " . implode(' ', $errors));
        }
    }

    private static function validateLineData(string $transactionNo, $rows, ?string $companyCurrencyId, array $unbilledAccounts, array $inventoryAccounts, array $costPerUnit): void
    {
        $errors = [];

        if (self::isBlank($companyCurrencyId)) {
            $errors[] = 'Company profile currency is required.';
        }

        foreach ($rows as $row) {
            $part = $row->PartID;
            $inventoryType = trim((string) ($row->InventoryTypeID ?? ''));
            $batchNo = trim((string) ($row->BatchNo ?? ''));

            if (self::isBlank($row->DivisionID ?? null)) {
                $errors[] = "Division is required for Part {$part}.";
            }

            if ($batchNo === '') {
                $errors[] = "Batch No is required to cost delivered Part {$part}.";
            } elseif (!isset($costPerUnit[self::costKey($row)])) {
                $errors[] = "Material cost is not available for Part {$part} batch {$batchNo}. Ensure the batch has a Material_Cost_Batch entry.";
            }

            if (self::isBlank($unbilledAccounts[$inventoryType] ?? null)) {
                $errors[] = "Item Delivered Unbilled account mapping is missing for Inventory Type {$inventoryType} / Part {$part}.";
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
