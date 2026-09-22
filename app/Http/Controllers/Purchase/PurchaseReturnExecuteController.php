<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\BukuStockHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuHutang;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsPartUnit;
use App\Models\TransDirectPurchaseHD;
use App\Models\TransDirectVendorPaymentDT;
use App\Models\TransGoodsReceivingHD;
use App\Models\TransPurchaseInvoiceDT;
use App\Models\TransPurchaseInvoiceHD;
use App\Models\TransPurchaseReturnHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Services\PurchaseReturnJournalService;
use App\Services\WarehouseAccessCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnExecuteController extends Controller
{
    public function index()
    {
        return view('purchase.pr_execute.index');
    }

    public function datatable(Request $request)
    {
        $data = TransPurchaseReturnHD::select('id', 'TransactionNo', 'TransactionDate', 'DivisionID', 'SupplierID', 'GrandTotal')
            ->with(['division', 'supplier'])
            ->selectRaw("
                CASE WHEN EXISTS (
                    SELECT 1
                    FROM Buku_Stock
                    WHERE Buku_Stock.TransactionNo = Trans_PurchaseReturnHD.TransactionNo
                        AND Buku_Stock.TransactionType = 'PURCHASE_RETURN'
                ) THEN 1 ELSE 0 END AS executed
            ");

        WarehouseAccessCriteria::applyDetails($data);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        if ($request->get('status') === 'pending') {
            $data->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_PurchaseReturnHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', 'PURCHASE_RETURN');
            });
        } elseif ($request->get('status') === 'executed') {
            $data->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_PurchaseReturnHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', 'PURCHASE_RETURN');
            });
        }

        return DataTables::of($data)
            ->editColumn('DivisionID', function ($row) {
                return optional($row->division)->DivisionName ?? $row->DivisionID;
            })
            ->editColumn('SupplierID', function ($row) {
                return optional($row->supplier)->SupplierName ?? $row->SupplierID;
            })
            ->addColumn('status', function ($row) {
                return $row->executed
                    ? '<span class="badge bg-success">Executed</span>'
                    : '<span class="badge bg-warning text-dark">Pending</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Show" href="' . route('pr_execute.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if (!$row->executed && Auth::user()->hasAnyPermission(['admin', 'pr_execute.add'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Execute" href="' . route('pr_execute.show', $row->id) . '"><i class="fa fa-fw fa-check"></i></a>';
                }

                return $btn . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function show($id)
    {
        $query = TransPurchaseReturnHD::with(['details.part', 'details.unit', 'details.division', 'details.warehouse'])
            ->where('id', $id);
        WarehouseAccessCriteria::applyDetails($query);
        $pr = $query->firstOrFail();

        $executed = $this->isExecuted($pr->TransactionNo);
        $executedStocks = BukuStock::where('TransactionNo', $pr->TransactionNo)
            ->where('TransactionType', 'PURCHASE_RETURN')
            ->get()
            ->keyBy('Sequence');
        $options = DocPrint::where('ModuleCode', 'PRETURN')
            ->where('TypeStr', 'print')
            ->get();

        return view('purchase.pr_execute.show', compact('pr', 'executed', 'executedStocks', 'options'));
    }

    public function execute(Request $request, $id)
    {
        try {
            $transactionNo = null;

            DB::transaction(function () use ($request, $id, &$transactionNo) {
                $query = TransPurchaseReturnHD::with('details')
                    ->where('id', $id)
                    ->lockForUpdate();
                WarehouseAccessCriteria::applyDetails($query);
                $pr = $query->firstOrFail();
                $transactionNo = $pr->TransactionNo;

                if ($this->isExecuted($transactionNo)) {
                    throw new \Exception('Purchase Return already executed.');
                }

                if ($pr->details->isEmpty()) {
                    throw new \Exception('Purchase Return has no detail.');
                }

                $bukuStock = new BukuStock();
                $bukuStockSchema = Schema::connection($bukuStock->getConnectionName());
                $hasBatchColumn = $bukuStockSchema->hasColumn($bukuStock->getTable(), 'BatchNo');
                $hasQty2Column = $bukuStockSchema->hasColumn($bukuStock->getTable(), 'Qty2');
                $hasUnitId2Column = $bukuStockSchema->hasColumn($bukuStock->getTable(), 'UnitID2');
                $requestedStockByBatch = [];
                $stockRows = [];

                foreach ($pr->details as $detail) {
                    $stockQty = $this->stockQty($pr, $detail);
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
                        $pr->TransactionDate
                    );

                    if ($requestedStockByBatch[$stockKey] > $availableStock) {
                        throw new \Exception("Can't return {$detail->PartID} because the quantity exceeds the current buku stock amount.");
                    }

                    $stockRow = [
                        'TransactionNo' => $pr->TransactionNo,
                        'TransactionDate' => $pr->TransactionDate,
                        'PartID' => $detail->PartID,
                        'WarehouseID' => $detail->WarehouseID,
                        'Sequence' => $detail->Sequence,
                        'UnitID' => $this->stockUnit($pr, $detail),
                        'Qty' => $stockQty * -1,
                        'TransactionType' => 'PURCHASE_RETURN',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];

                    if ($hasBatchColumn) {
                        $stockRow['BatchNo'] = $batchNo;
                    }
                    if ($hasQty2Column) {
                        $secondary = $this->secondaryQuantityForDetail($detail, $stockQty, $pr->TransactionDate);
                        $stockRow['Qty2'] = $secondary['qty2'] !== null ? abs((float) $secondary['qty2']) * -1 : null;
                    }
                    if ($hasUnitId2Column) {
                        $secondary = $secondary ?? $this->secondaryQuantityForDetail($detail, $stockQty, $pr->TransactionDate);
                        $stockRow['UnitID2'] = $secondary['unit_id2'];
                    }
                    $stockRows[] = $stockRow;
                }

                BukuStock::insert($stockRows);

                // Invoice-based returns leave the AP side to a manually-created Debit Note
                // referencing this Purchase Return, mirroring how Sales Return stopped
                // touching AR directly for invoice-based returns - so only a plain reference
                // (no GR, no Invoice) still books Buku_Hutang directly here.
                if ((int) $pr->BasedOnGoodsReceiving !== 1 && (int) $pr->BasedOnPurchaseInvoice !== 1) {
                    $debtRow = [
                        'BalanceNo' => $pr->ReceivingNumber ?: $pr->TransactionNo,
                        'TransactionDate' => $pr->TransactionDate,
                        'DueDate' => $pr->TransactionDate,
                        'SupplierID' => $pr->SupplierID,
                        'CurrencyID' => $pr->CurrencyID,
                        'Rate' => $pr->Rate ?? 1,
                        'Amount' => $pr->GrandTotal ? ($pr->GrandTotal * -1) : 0,
                        'isVoucher' => 0,
                        'TransactionType' => 'PURCHASE_RETURN',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];

                    $debtRow = array_merge($debtRow, $this->debtDualQuantityColumns($pr));

                    BukuHutang::updateOrCreate(
                        ['TransactionNo' => $pr->TransactionNo],
                        $debtRow
                    );
                }

                if ((int) $pr->BasedOnGoodsReceiving === 1) {
                    $this->checkOutstandingGR($pr->ReceivingNumber);
                } elseif ((int) $pr->BasedOnDirectPurchase === 1) {
                    $this->checkOutstandingDP($pr->ReceivingNumber);
                } elseif ((int) $pr->BasedOnPurchaseInvoice === 1) {
                    $this->checkOutstandingInvoice($pr->ReceivingNumber);
                }

                PurchaseReturnJournalService::rebuild($pr->TransactionNo);
            });

            return response()->json(['message' => 'Purchase Return successfully executed!']);
        } catch (\Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Something went wrong!',
            ], 400);
        }
    }

    public function deleteExecution($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $query = TransPurchaseReturnHD::where('id', $id);
                WarehouseAccessCriteria::applyDetails($query);
                $pr = $query->firstOrFail();

                if (!$this->isExecuted($pr->TransactionNo)) {
                    throw new \Exception('Purchase Return has not been executed.');
                }

                BukuStock::where('TransactionNo', $pr->TransactionNo)
                    ->where('TransactionType', 'PURCHASE_RETURN')
                    ->delete();

                TransJournalDT::where('TransactionNo', $pr->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $pr->TransactionNo)->delete();

                if ((int) $pr->BasedOnGoodsReceiving !== 1) {
                    BukuHutang::where('TransactionNo', $pr->TransactionNo)->delete();
                }

                if ((int) $pr->BasedOnGoodsReceiving === 1) {
                    $this->checkOutstandingGR($pr->ReceivingNumber);
                } elseif ((int) $pr->BasedOnDirectPurchase === 1) {
                    $this->checkOutstandingDP($pr->ReceivingNumber);
                } elseif ((int) $pr->BasedOnPurchaseInvoice === 1) {
                    $this->checkOutstandingInvoice($pr->ReceivingNumber);
                }
            });

            return response()->json(['message' => 'Purchase Return execution successfully deleted!']);
        } catch (\Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Something went wrong!',
            ], 400);
        }
    }

    private function isExecuted(string $transactionNo): bool
    {
        return BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', 'PURCHASE_RETURN')
            ->exists();
    }

    private function stockQty(TransPurchaseReturnHD $pr, $detail): float
    {
        return (int) $pr->BasedOnGoodsReceiving === 1
            ? (float) $detail->Qty
            : (float) $detail->Qty * (float) $detail->Conversion;
    }

    private function stockUnit(TransPurchaseReturnHD $pr, $detail): string
    {
        if ((int) $pr->BasedOnGoodsReceiving === 1) {
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

        $stockBaseQty = abs((float) ($stockTotals->qty ?? 0));
        $stockQty2 = abs((float) ($stockTotals->qty2 ?? 0));
        if ($stockBaseQty > 0.000001 && $stockQty2 > 0.000001) {
            $qty2 = round(abs($stockQty) / $stockBaseQty * $stockQty2, 2);
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
        ];
    }

    private function debtDualQuantityColumns(TransPurchaseReturnHD $pr): array
    {
        $bukuHutang = new BukuHutang();
        $schema = Schema::connection($bukuHutang->getConnectionName());
        $table = $bukuHutang->getTable();
        $columns = [];

        if ($schema->hasColumn($table, 'Qty2')) {
            $qty2 = $pr->details
                ->sum(function ($detail) use ($pr) {
                    $secondary = $this->secondaryQuantityForDetail(
                        $detail,
                        $this->stockQty($pr, $detail),
                        $pr->TransactionDate
                    );

                    return $secondary['qty2'] !== null ? (float) $secondary['qty2'] : 0;
                });
            $columns['Qty2'] = $qty2 !== 0.0 ? $qty2 * -1 : null;
        }

        if ($schema->hasColumn($table, 'UnitID2')) {
            $unitIds = $pr->details
                ->map(function ($detail) use ($pr) {
                    $secondary = $this->secondaryQuantityForDetail(
                        $detail,
                        $this->stockQty($pr, $detail),
                        $pr->TransactionDate
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

    private function checkOutstandingGR($GRNumber): void
    {
        $gr = TransGoodsReceivingHD::where('TransactionNo', $GRNumber)->first();
        if (!$gr) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnGoodsReceiving', 1)
            ->where('ReceivingNumber', $GRNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkGR = TransPurchaseInvoiceDT::where('ReffNumber', $GRNumber)->first();
        if ($checkGR) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $gr->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    private function checkOutstandingDP($DPNumber): void
    {
        $dp = TransDirectPurchaseHD::where('TransactionNo', $DPNumber)->first();
        if (!$dp) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnDirectPurchase', 1)
            ->where('ReceivingNumber', $DPNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkPayment = TransDirectVendorPaymentDT::where('InvoiceNumber', $DPNumber)
            ->first();
        if ($checkPayment) {
            $checkEditable = false;
        }

        $checkSum = BukuHutang::where('BalanceNo', $DPNumber)->sum('Amount');

        if ($checkSum == 0) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $dp->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    private function checkOutstandingInvoice($InvoiceNumber): void
    {
        $pi = TransPurchaseInvoiceHD::where('TransactionNo', $InvoiceNumber)->first();
        if (!$pi) {
            return;
        }

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnPurchaseInvoice', 1)
            ->where('ReceivingNumber', $InvoiceNumber)
            ->first();
        if ($checkReturn) {
            $checkEditable = false;
        }

        $checkPayment = TransDirectVendorPaymentDT::where('InvoiceNumber', $InvoiceNumber)
            ->first();
        if ($checkPayment) {
            $checkEditable = false;
        }

        $checkSum = BukuHutang::whereIn('BalanceNo', [$InvoiceNumber, $InvoiceNumber . '/VAT'])->sum('Amount');

        if ($checkSum == 0) {
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $pi->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }
}
