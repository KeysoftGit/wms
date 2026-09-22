<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\PurchaseReturnExecute\DeleteTransactionRequest;
use App\Http\Requests\Transaction\PurchaseReturnExecute\ExecuteTransactionRequest;
use App\Http\Requests\Transaction\PurchaseReturnExecute\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\PurchaseReturnExecute\GetTransactionRequest;
use App\Models\BukuHutang;
use App\Models\BukuStock;
use App\Models\MsPartUnit;
use App\Models\TransDirectPurchaseHD;
use App\Models\TransDirectVendorPaymentDT;
use App\Models\TransGoodsReceivingHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPurchaseInvoiceDT;
use App\Models\TransPurchaseInvoiceHD;
use App\Models\TransPurchaseReturnHD;
use App\Services\PurchaseReturnJournalService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurchaseReturnExecuteController extends Controller
{
    private const TRANSACTION_TYPE = 'PURCHASE_RETURN';

    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('pr_execute.view');

            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);
            $status = $request->status ?: 'pending';

            $query = TransPurchaseReturnHD::query()
                ->with([
                    'supplier:SupplierID,SupplierName',
                    'division:DivisionID,DivisionName',
                    'currency:CurrencyID,CurrencyName',
                ])
                ->select([
                    'id',
                    'TransactionNo',
                    'TransactionDate',
                    'ReceivingNumber',
                    'DivisionID',
                    'SupplierID',
                    'CurrencyID',
                    'GrandTotal',
                    'BasedOnGoodsReceiving',
                    'BasedOnDirectPurchase',
                    'BasedOnPurchaseInvoice',
                ])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Buku_Stock
                        WHERE Buku_Stock.TransactionNo = Trans_PurchaseReturnHD.TransactionNo
                            AND Buku_Stock.TransactionType = ?
                    ) THEN 1 ELSE 0 END AS executed
                ", [self::TRANSACTION_TYPE])
                ->when($request->term, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('Trans_PurchaseReturnHD.TransactionNo', 'like', "%{$term}%")
                            ->orWhere('Trans_PurchaseReturnHD.ReceivingNumber', 'like', "%{$term}%")
                            ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('SupplierName', 'like', "%{$term}%"));

                        try {
                            $q->orWhereDate('Trans_PurchaseReturnHD.TransactionDate', Carbon::parse($term)->format('Y-m-d'));
                        } catch (\Exception $e) {
                            // term is not a date, ignore date search
                        }
                    });
                })
                ->when($request->date_from, fn ($query, $date) => $query->whereDate('Trans_PurchaseReturnHD.TransactionDate', '>=', Carbon::parse($date)->format('Y-m-d')))
                ->when($request->date_to, fn ($query, $date) => $query->whereDate('Trans_PurchaseReturnHD.TransactionDate', '<=', Carbon::parse($date)->format('Y-m-d')));

            WarehouseAccessCriteria::applyDetails($query);
            $this->applyStatusFilter($query, $status);

            $paginator = $query
                ->orderBy('Trans_PurchaseReturnHD.TransactionDate', 'desc')
                ->orderBy('Trans_PurchaseReturnHD.TransactionNo', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $transactions = collect($paginator->items())
                ->map(fn ($transaction) => $this->formatTransaction($transaction))
                ->values();

            return ResponseFormatter::success([
                'transactions' => $transactions,
                'pagination' => $this->pagination($paginator),
            ], 'Purchase Return Execute fetched successfully')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getTransactionDetails(GetTransactionDetailsRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('pr_execute.view');

            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $transactionQuery = TransPurchaseReturnHD::query()
                ->with([
                    'supplier:SupplierID,SupplierName',
                    'division:DivisionID,DivisionName',
                    'currency:CurrencyID,CurrencyName',
                ])
                ->where('TransactionNo', $request->transaction_no)
                ->select([
                    'id',
                    'TransactionNo',
                    'TransactionDate',
                    'ReceivingNumber',
                    'DivisionID',
                    'SupplierID',
                    'CurrencyID',
                    'GrandTotal',
                    'BasedOnGoodsReceiving',
                    'BasedOnDirectPurchase',
                    'BasedOnPurchaseInvoice',
                ])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Buku_Stock
                        WHERE Buku_Stock.TransactionNo = Trans_PurchaseReturnHD.TransactionNo
                            AND Buku_Stock.TransactionType = ?
                    ) THEN 1 ELSE 0 END AS executed
                ", [self::TRANSACTION_TYPE]);

            WarehouseAccessCriteria::applyDetails($transactionQuery);
            $transaction = $transactionQuery->first();

            if (!$transaction) {
                return ResponseFormatter::success([
                    'transaction' => null,
                    'details' => [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                        'has_more' => false,
                    ],
                ], 'Purchase Return Execute details fetched successfully')->toResponse();
            }

            $detailsPaginator = $transaction->details()
                ->with([
                    'part:PartID,PartName,Active,WithSerialNo',
                    'unit:UnitID,UnitName',
                    'division:DivisionID,DivisionName',
                    'warehouse:WarehouseID,WarehouseName',
                ])
                ->orderBy('Sequence')
                ->paginate($perPage, ['*'], 'page', $page);

            $executedStocks = BukuStock::where('TransactionNo', $transaction->TransactionNo)
                ->where('TransactionType', self::TRANSACTION_TYPE)
                ->get(['Sequence', 'WarehouseID', 'Qty', 'Qty2', 'UnitID2'])
                ->keyBy('Sequence');

            $detailRows = collect($detailsPaginator->items());
            $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch($detailRows);

            $details = $detailRows
                ->map(function ($detail) use ($transaction, $executedStocks, $coilNoByPartBatch) {
                    $executedStock = $executedStocks[$detail->Sequence] ?? null;
                    $stockQty = $this->stockQty($transaction, $detail);
                    $stockUnitId = $this->stockUnit($transaction, $detail);
                    $batchNo = $detail->BatchNo ?? null;
                    $secondary = $this->secondaryQuantityForDetail(
                        $detail,
                        $stockQty,
                        Carbon::parse($transaction->TransactionDate)->format('Y-m-d')
                    );
                    $qty2 = $secondary['qty2'];
                    $unitId2 = $secondary['unit_id2'];
                    $availableStockQty = $secondary['available_stock_qty'];
                    $availableStockQty2 = $secondary['available_stock_qty2'];

                    if ($executedStock !== null) {
                        if ($qty2 === null && $executedStock->Qty2 !== null) {
                            $qty2 = abs((float) $executedStock->Qty2);
                        }
                        if ($unitId2 === null) {
                            $unitId2 = $executedStock->UnitID2;
                        }
                    }

                    $grossWeight = $detail->getAttribute('GrossWeight') !== null ? (float) $detail->getAttribute('GrossWeight') : null;
                    $coilNo = $batchNo !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $batchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($batchNo)))
                        : null;

                    return [
                        'transaction_no' => $detail->TransactionNo,
                        'sequence' => $detail->Sequence,
                        'part_id' => $detail->PartID,
                        'part' => $detail->part,
                        'warehouse_id' => $detail->WarehouseID,
                        'warehouse' => $detail->warehouse,
                        'division_id' => $detail->DivisionID,
                        'division' => $detail->division,
                        'unit_id' => $detail->UnitID,
                        'unit' => $detail->unit,
                        'qty' => (float) $detail->Qty,
                        'qty2' => $qty2,
                        'Qty2' => $qty2,
                        'unit_id2' => $unitId2,
                        'UnitID2' => $unitId2,
                        'coil_no' => $coilNo,
                        'CoilNo' => $coilNo,
                        'gross_weight' => $grossWeight,
                        'GrossWeight' => $grossWeight,
                        'weight_per_piece' => DualQuantityHelper::weightPerPiece((float) $detail->Qty, $qty2),
                        'conversion' => (float) ($detail->Conversion ?? 1),
                        'stock_qty' => $stockQty,
                        'QtyBase' => $stockQty,
                        'stock_unit_id' => $stockUnitId,
                        'UnitIDBase' => $stockUnitId,
                        'available_stock_qty' => $availableStockQty,
                        'available_stock_qty2' => $availableStockQty2,
                        'available_unit_id2' => $unitId2,
                        'total_stock_qty' => $availableStockQty,
                        'total_stock_qty2' => $availableStockQty2,
                        'batch_no' => $detail->BatchNo ?? null,
                        'serial_no' => null,
                        'exp_date' => null,
                        'executed_qty' => $executedStock ? abs((float) $executedStock->Qty) : 0,
                        'executed_qty2' => $executedStock && $executedStock->Qty2 !== null ? abs((float) $executedStock->Qty2) : null,
                        'executed' => $executedStock !== null,
                    ];
                })
                ->values();

            return ResponseFormatter::success([
                'transaction' => $this->formatTransaction($transaction),
                'details' => $details,
                'pagination' => $this->pagination($detailsPaginator),
            ], 'Purchase Return Execute details fetched successfully')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function execute(ExecuteTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('pr_execute.add');

            $transactionNo = trim($request->transaction_no);

            DB::transaction(function () use ($transactionNo) {
                $query = TransPurchaseReturnHD::with('details')
                    ->where('TransactionNo', $transactionNo)
                    ->lockForUpdate();
                WarehouseAccessCriteria::applyDetails($query);
                $purchaseReturn = $query->first();

                if (!$purchaseReturn) {
                    throw new ValidationException('Purchase Return does not exist or is not authorized.');
                }

                if ($this->isExecuted($purchaseReturn->TransactionNo)) {
                    throw new ValidationException('Purchase Return already executed.');
                }

                if ($purchaseReturn->details->isEmpty()) {
                    throw new ValidationException('Purchase Return has no detail.');
                }

                $stockRows = $this->buildStockRows($purchaseReturn);
                $this->insertInChunks(BukuStock::class, $stockRows);
                $this->createDebtRowIfNeeded($purchaseReturn);
                $this->recalculateReference($purchaseReturn);

                $this->rebuildJournal($purchaseReturn->TransactionNo);
            });

            return ResponseFormatter::success([
                'transaction_no' => $transactionNo,
                'executed' => true,
            ], 'Purchase Return successfully executed!')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function deleteExecution(DeleteTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('pr_execute.add');

            $transactionNo = trim($request->transaction_no);

            DB::transaction(function () use ($transactionNo) {
                $query = TransPurchaseReturnHD::query()
                    ->where('TransactionNo', $transactionNo)
                    ->lockForUpdate();
                WarehouseAccessCriteria::applyDetails($query);
                $purchaseReturn = $query->first();

                if (!$purchaseReturn) {
                    throw new ValidationException('Purchase Return does not exist or is not authorized.');
                }

                if (!$this->isExecuted($purchaseReturn->TransactionNo)) {
                    throw new ValidationException('Purchase Return has not been executed.');
                }

                BukuStock::where('TransactionNo', $purchaseReturn->TransactionNo)
                    ->where('TransactionType', self::TRANSACTION_TYPE)
                    ->delete();
                TransJournalDT::where('TransactionNo', $purchaseReturn->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $purchaseReturn->TransactionNo)->delete();

                if ((int) $purchaseReturn->BasedOnGoodsReceiving !== 1) {
                    BukuHutang::where('TransactionNo', $purchaseReturn->TransactionNo)->delete();
                }

                $this->recalculateReference($purchaseReturn);
            });

            return ResponseFormatter::success([
                'transaction_no' => $transactionNo,
                'executed' => false,
            ], 'Purchase Return execution successfully deleted!')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    private function buildStockRows(TransPurchaseReturnHD $purchaseReturn): array
    {
        $bukuStock = new BukuStock();
        $bukuStockSchema = Schema::connection($bukuStock->getConnectionName());
        $hasBatchColumn = $bukuStockSchema->hasColumn($bukuStock->getTable(), 'BatchNo');
        $transactionDate = Carbon::parse($purchaseReturn->TransactionDate)->format('Y-m-d');
        $requestedStockByBatch = [];
        $stockRows = [];

        foreach ($purchaseReturn->details as $detail) {
            $stockQty = $this->stockQty($purchaseReturn, $detail);
            $batchNo = $detail->BatchNo ?? null;
            $stockKey = implode('|', [
                $detail->PartID,
                $detail->WarehouseID,
                $batchNo ?? '__NULL__',
            ]);

            $requestedStockByBatch[$stockKey] = ($requestedStockByBatch[$stockKey] ?? 0) + $stockQty;

            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $detail->PartID,
                $detail->WarehouseID,
                $batchNo,
                $transactionDate
            );

            if ($requestedStockByBatch[$stockKey] > $availableStock) {
                throw new ValidationException("Can't return {$detail->PartID} because the quantity exceeds the current buku stock amount.");
            }

            $qtyDelta = $stockQty * -1;
            $stockRow = [
                'TransactionNo' => $purchaseReturn->TransactionNo,
                'TransactionDate' => $transactionDate,
                'PartID' => $detail->PartID,
                'WarehouseID' => $detail->WarehouseID,
                'Sequence' => $detail->Sequence,
                'UnitID' => $this->stockUnit($purchaseReturn, $detail),
                'Qty' => $qtyDelta,
                'TransactionType' => self::TRANSACTION_TYPE,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ];

            if ($hasBatchColumn) {
                $stockRow['BatchNo'] = $batchNo;
            }

            $stockRows[] = array_merge($stockRow, DualQuantityHelper::bukuStockSecondaryColumns(
                $qtyDelta,
                $detail->PartID,
                $detail->WarehouseID,
                $batchNo
            ));
        }

        return $stockRows;
    }

    private function stockQty(TransPurchaseReturnHD $purchaseReturn, $detail): float
    {
        return (int) $purchaseReturn->BasedOnGoodsReceiving === 1
            ? (float) $detail->Qty
            : (float) $detail->Qty * (float) $detail->Conversion;
    }

    private function stockUnit(TransPurchaseReturnHD $purchaseReturn, $detail): string
    {
        if ((int) $purchaseReturn->BasedOnGoodsReceiving === 1) {
            return $detail->UnitID;
        }

        $unit = MsPartUnit::where('PartID', $detail->PartID)
            ->where('UnitID2', $detail->UnitID)
            ->first();

        return $unit->UnitID1 ?? $detail->UnitID;
    }

    private function secondaryQuantityForDetail($detail, float $stockQty, string $transactionDate): array
    {
        $qty2 = $detail->getAttribute('Qty2') ?? $detail->getAttribute('qty2') ?? $detail->getAttribute('qty_2');
        $unitId2 = $detail->getAttribute('UnitID2') ?? $detail->getAttribute('unit_id2') ?? $detail->getAttribute('unit_id_2');

        $bukuStock = new BukuStock();
        $schema = Schema::connection($bukuStock->getConnectionName());
        if (!$schema->hasColumn($bukuStock->getTable(), 'Qty2')) {
            return [
                'qty2' => $qty2 !== null ? (float) $qty2 : null,
                'unit_id2' => $unitId2,
                'available_stock_qty' => null,
                'available_stock_qty2' => null,
            ];
        }

        $query = BukuStock::where('PartID', $detail->PartID)
            ->where('WarehouseID', $detail->WarehouseID)
            ->whereDate('TransactionDate', '<=', $transactionDate);

        if ($detail->BatchNo === null || trim((string) $detail->BatchNo) === '') {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $detail->BatchNo);
        }

        $stockTotals = (clone $query)
            ->selectRaw('SUM(Qty) as qty')
            ->selectRaw('SUM(Qty2) as qty2')
            ->first();

        $stockBaseQty = (float) ($stockTotals->qty ?? 0);
        $stockQty2 = (float) ($stockTotals->qty2 ?? 0);
        $availableStockQty = abs($stockBaseQty);
        $availableStockQty2 = abs($stockQty2);
        if ($availableStockQty > 0.000001 && $availableStockQty2 > 0.000001) {
            $qty2 = round(abs($stockQty) / $availableStockQty * $availableStockQty2, 2);
        }

        if ($unitId2 === null && $schema->hasColumn($bukuStock->getTable(), 'UnitID2')) {
            $unitId2 = (clone $query)
                ->whereNotNull('UnitID2')
                ->where('UnitID2', '<>', '')
                ->orderBy('EntryTime', 'desc')
                ->value('UnitID2');
        }

        return [
            'qty2' => $qty2 !== null ? round((float) $qty2, 2) : null,
            'unit_id2' => $unitId2,
            'available_stock_qty' => $availableStockQty,
            'available_stock_qty2' => $availableStockQty2,
        ];
    }

    private function createDebtRowIfNeeded(TransPurchaseReturnHD $purchaseReturn): void
    {
        // Invoice-based returns leave the AP side to a manually-created Debit Note
        // referencing this Purchase Return, mirroring how Sales Return stopped touching AR
        // directly for invoice-based returns - so only a plain reference (no GR, no Invoice)
        // still books Buku_Hutang directly here.
        if ((int) $purchaseReturn->BasedOnGoodsReceiving === 1 || (int) $purchaseReturn->BasedOnPurchaseInvoice === 1) {
            return;
        }

        $debtRow = [
            'BalanceNo' => $purchaseReturn->ReceivingNumber ?: $purchaseReturn->TransactionNo,
            'TransactionDate' => $purchaseReturn->TransactionDate,
            'DueDate' => $purchaseReturn->TransactionDate,
            'SupplierID' => $purchaseReturn->SupplierID,
            'CurrencyID' => $purchaseReturn->CurrencyID,
            'Rate' => $purchaseReturn->Rate ?? 1,
            'Amount' => $purchaseReturn->GrandTotal ? ($purchaseReturn->GrandTotal * -1) : 0,
            'isVoucher' => 0,
            'TransactionType' => self::TRANSACTION_TYPE,
            'CreatedBy' => Auth::user()->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
        ];

        $debtRow = array_merge($debtRow, $this->debtDualQuantityColumns($purchaseReturn));

        BukuHutang::updateOrCreate(
            ['TransactionNo' => $purchaseReturn->TransactionNo],
            $debtRow
        );
    }

    private function debtDualQuantityColumns(TransPurchaseReturnHD $purchaseReturn): array
    {
        $bukuHutang = new BukuHutang();
        $schema = Schema::connection($bukuHutang->getConnectionName());
        $table = $bukuHutang->getTable();
        $columns = [];

        if ($schema->hasColumn($table, 'Qty2')) {
            $qty2 = $purchaseReturn->details
                ->sum(function ($detail) use ($purchaseReturn) {
                    $secondary = $this->secondaryQuantityForDetail(
                        $detail,
                        $this->stockQty($purchaseReturn, $detail),
                        Carbon::parse($purchaseReturn->TransactionDate)->format('Y-m-d')
                    );

                    return $secondary['qty2'] !== null ? (float) $secondary['qty2'] : 0;
                });
            $columns['Qty2'] = $qty2 !== 0.0 ? $qty2 * -1 : null;
        }

        if ($schema->hasColumn($table, 'UnitID2')) {
            $unitIds = $purchaseReturn->details
                ->map(function ($detail) use ($purchaseReturn) {
                    $secondary = $this->secondaryQuantityForDetail(
                        $detail,
                        $this->stockQty($purchaseReturn, $detail),
                        Carbon::parse($purchaseReturn->TransactionDate)->format('Y-m-d')
                    );

                    return $secondary['unit_id2'];
                })
                ->filter(fn ($unitId) => $unitId !== null && trim((string) $unitId) !== '')
                ->unique()
                ->values();
            $columns['UnitID2'] = $unitIds->count() === 1 ? $unitIds->first() : null;
        }

        return $columns;
    }

    private function recalculateReference(TransPurchaseReturnHD $purchaseReturn): void
    {
        if ((int) $purchaseReturn->BasedOnGoodsReceiving === 1) {
            $this->checkOutstandingGR($purchaseReturn->ReceivingNumber);
        } elseif ((int) $purchaseReturn->BasedOnDirectPurchase === 1) {
            $this->checkOutstandingDP($purchaseReturn->ReceivingNumber);
        } elseif ((int) $purchaseReturn->BasedOnPurchaseInvoice === 1) {
            $this->checkOutstandingInvoice($purchaseReturn->ReceivingNumber);
        }
    }

    private function checkOutstandingGR($grNumber): void
    {
        $gr = TransGoodsReceivingHD::where('TransactionNo', $grNumber)->first();
        if (!$gr) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnGoodsReceiving', 1)
            ->where('ReceivingNumber', $grNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkGR = TransPurchaseInvoiceDT::where('ReffNumber', $grNumber)->first();
        if ($checkGR) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $gr->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    private function checkOutstandingDP($dpNumber): void
    {
        $dp = TransDirectPurchaseHD::where('TransactionNo', $dpNumber)->first();
        if (!$dp) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnDirectPurchase', 1)
            ->where('ReceivingNumber', $dpNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkPayment = TransDirectVendorPaymentDT::where('InvoiceNumber', $dpNumber)->first();
        if ($checkPayment) {
            $checkEditable = false;
        }

        $checkSum = BukuHutang::where('BalanceNo', $dpNumber)->sum('Amount');
        if ($checkSum == 0) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $dp->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    private function checkOutstandingInvoice($invoiceNumber): void
    {
        $pi = TransPurchaseInvoiceHD::where('TransactionNo', $invoiceNumber)->first();
        if (!$pi) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnPurchaseInvoice', 1)
            ->where('ReceivingNumber', $invoiceNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkPayment = TransDirectVendorPaymentDT::where('InvoiceNumber', $invoiceNumber)->first();
        if ($checkPayment) {
            $checkEditable = false;
        }

        $checkSum = BukuHutang::whereIn('BalanceNo', [$invoiceNumber, $invoiceNumber . '/VAT'])->sum('Amount');
        if ($checkSum == 0) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $pi->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'pending') {
            $query->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_PurchaseReturnHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', self::TRANSACTION_TYPE);
            });
        } elseif ($status === 'executed') {
            $query->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_PurchaseReturnHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', self::TRANSACTION_TYPE);
            });
        }
    }

    private function formatTransaction($transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_no' => $transaction->TransactionNo,
            'transaction_date' => $transaction->TransactionDate,
            'receiving_number' => $transaction->ReceivingNumber,
            'division_id' => $transaction->DivisionID,
            'division' => $transaction->division,
            'supplier_id' => $transaction->SupplierID,
            'supplier' => $transaction->supplier,
            'currency_id' => $transaction->CurrencyID,
            'currency' => $transaction->currency,
            'grand_total' => (float) ($transaction->GrandTotal ?? 0),
            'based_on_goods_receiving' => (int) ($transaction->BasedOnGoodsReceiving ?? 0),
            'based_on_direct_purchase' => (int) ($transaction->BasedOnDirectPurchase ?? 0),
            'based_on_purchase_invoice' => (int) ($transaction->BasedOnPurchaseInvoice ?? 0),
            'executed' => (bool) ($transaction->executed ?? $this->isExecuted($transaction->TransactionNo)),
            'status' => ($transaction->executed ?? $this->isExecuted($transaction->TransactionNo)) ? 'executed' : 'pending',
        ];
    }

    private function isExecuted(string $transactionNo): bool
    {
        return BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', self::TRANSACTION_TYPE)
            ->exists();
    }

    private function rebuildJournal(string $transactionNo): void
    {
        PurchaseReturnJournalService::rebuild($transactionNo);
    }

    private function insertInChunks(string $modelClass, array $rows, int $chunkSize = 500): void
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $modelClass::insert($chunk);
        }
    }

    private function ensurePermission(string $permission): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new ValidationException('Unauthenticated.');
        }

        if (method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission(['admin', $permission])) {
            return;
        }

        if (method_exists($user, 'hasPermissionTo') && ($user->hasPermissionTo('admin') || $user->hasPermissionTo($permission))) {
            return;
        }

        throw new ValidationException('You do not have permission to access Purchase Return Execute.');
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }
}
