<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books the Purchase Return's own inventory reversal (Debit the return account / Credit
 * Inventory), using the same per-batch unit cost (Material_Cost_Batch) as the
 * Purchase-through-Production journal chain - replaces the legacy "exec
 * sp_jurnal_purchase_return" stored procedure call from the Purchase Return
 * store/update/execute flows. Mirrored verbatim from kawaguci-nla's
 * app/Services/PurchaseReturnJournalService.php since the two apps share the same database
 * but cannot share PHP classes.
 *
 * A Goods-Receiving-based return debits ITEM UNBILLED (the goods were never invoiced yet);
 * None- and Invoice-based returns debit the PROCUREMENT RETURN account instead. The AP/VAT
 * side is not created here - for a return based on a Purchase Invoice, that side is booked
 * manually via Debit Note.
 */
class PurchaseReturnJournalService
{
    public static function rebuild(string $transactionNo): void
    {
        $db = DB::connection('sqlsrv');

        $header = $db->table('Trans_PurchaseReturnHD')
            ->where('TransactionNo', $transactionNo)
            ->first();

        if (!$header) {
            self::throwValidation("Cannot rebuild journal for {$transactionNo}: Purchase Return header was not found.");
        }

        self::validateHeader($transactionNo, $header);

        $debitAccountType = ($header->BasedOnGoodsReceiving ?? 0) ? 'UNBILLED_RECEIVED_OF_INVENTORY' : 'PROCUREMENT_RETURN_ACCOUNT';

        self::rebuildInventoryReversal($transactionNo, $header, $debitAccountType);
    }

    private static function rebuildInventoryReversal(string $transactionNo, $header, string $debitAccountType): void
    {
        $db = DB::connection('sqlsrv');

        $db->table('Trans_JournalDT')->where('TransactionNo', $transactionNo)->delete();
        $db->table('Trans_JournalHD')->where('TransactionNo', $transactionNo)->delete();

        $rows = self::returnedRows($transactionNo, $header);

        if ($rows->isEmpty()) {
            // Nothing to reverse (e.g. a header saved with no detail lines) - no journal.
            return;
        }

        $companyCurrencyId = $db->table('Ms_CompanyProfile')->value('CurrencyID');
        $inventoryTypeIds = $rows->pluck('InventoryTypeID')->all();
        $debitAccounts = self::accountMap($inventoryTypeIds, $debitAccountType);
        $inventoryAccounts = self::accountMap($inventoryTypeIds, 'INVENTORY_ACCOUNT');
        $costPerUnit = self::costPerUnitMap($rows);

        self::validateLineData($transactionNo, $rows, $companyCurrencyId, $debitAccountType, $debitAccounts, $inventoryAccounts, $costPerUnit);

        $db->table('Trans_JournalHD')->insert([
            'TransactionNo' => $header->TransactionNo,
            'TransactionDate' => $header->TransactionDate,
            'TransactionType' => 'PURCHASE_RETURN',
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

        $notes = "Purchase Return - {$transactionNo}";
        $debitGroups = [];
        $creditGroups = [];

        foreach ($rows as $row) {
            $qty = abs((float) $row->RealQty);
            $cost = $qty * $costPerUnit[self::costKey($row)];

            $debitAccount = $debitAccounts[$row->InventoryTypeID];
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

        JournalValidationService::validateBalanced($transactionNo, 'Purchase Return');
    }

    /**
     * The returned lines, read directly off Trans_PurchaseReturnDT (which already carries
     * PartID/Qty/BatchNo/DivisionID itself) joined to Ms_Part for InventoryTypeID - no need to
     * join back through Buku_Stock or the originating GR/Invoice detail.
     *
     * Trans_PurchaseReturnDT.Qty is stored in whatever unit was picked on the line (e.g. PCS
     * for a coil), not necessarily the base unit Material_Cost_Batch's EndingBalance is priced
     * in (e.g. KG) - a None-based line needs qty*Conversion to get the real base-unit quantity,
     * exactly mirroring store()'s own qtyBase formula for Buku_Stock. GR/Invoice-based lines
     * already enter Qty Return in the base unit, so no multiplication applies there.
     */
    private static function returnedRows(string $transactionNo, $header)
    {
        $useConversion = !($header->BasedOnGoodsReceiving ?? 0) && !($header->BasedOnPurchaseInvoice ?? 0);

        return DB::connection('sqlsrv')
            ->table('Trans_PurchaseReturnDT as dt')
            ->join('Ms_Part as part', 'part.PartID', '=', 'dt.PartID')
            ->where('dt.TransactionNo', $transactionNo)
            ->select([
                'dt.PartID',
                'dt.Sequence',
                'dt.BatchNo',
                'dt.Qty',
                'dt.Conversion',
                'dt.DivisionID',
                'part.InventoryTypeID',
            ])
            ->get()
            ->map(function ($row) use ($useConversion) {
                $row->RealQty = $useConversion
                    ? (float) $row->Qty * (float) ($row->Conversion ?: 1)
                    : (float) $row->Qty;

                return $row;
            });
    }

    /**
     * Unit cost per distinct Part/Batch returned, read from the same Material_Cost_Batch table
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

    private static function validateLineData(string $transactionNo, $rows, ?string $companyCurrencyId, string $debitAccountType, array $debitAccounts, array $inventoryAccounts, array $costPerUnit): void
    {
        $errors = [];

        if (self::isBlank($companyCurrencyId)) {
            $errors[] = 'Company profile currency is required.';
        }

        $debitAccountLabel = $debitAccountType === 'UNBILLED_RECEIVED_OF_INVENTORY' ? 'Unbilled Received of Inventory' : 'Procurement return';

        foreach ($rows as $row) {
            $part = $row->PartID;
            $inventoryType = trim((string) ($row->InventoryTypeID ?? ''));
            $batchNo = trim((string) ($row->BatchNo ?? ''));

            if (self::isBlank($row->DivisionID ?? null)) {
                $errors[] = "Division is required for Part {$part}.";
            }

            if ($batchNo === '') {
                $errors[] = "Batch No is required to cost returned Part {$part}.";
            } elseif (!isset($costPerUnit[self::costKey($row)])) {
                $errors[] = "Material cost is not available for Part {$part} batch {$batchNo}. Ensure the batch has a Material_Cost_Batch entry.";
            }

            if (self::isBlank($debitAccounts[$inventoryType] ?? null)) {
                $errors[] = "{$debitAccountLabel} account mapping is missing for Inventory Type {$inventoryType} / Part {$part}.";
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
