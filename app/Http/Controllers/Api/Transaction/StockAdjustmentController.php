<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StockAdjustment\DeleteTransactionRequest;
use App\Http\Requests\Transaction\StockAdjustment\GetStockDetailRequest;
use App\Http\Requests\Transaction\StockAdjustment\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\StockAdjustment\GetTransactionRequest;
use App\Http\Requests\Transaction\StockAdjustment\StoreTransactionRequest;
use App\Http\Requests\Transaction\StockAdjustment\UpdateTransactionRequest;
use App\Models\BukuStock;
use App\Models\ControlPanel;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\TransInventoryAdjustmentCheckers;
use App\Models\TransInventoryAdjustmentDT;
use App\Models\TransInventoryAdjustmentExecution;
use App\Models\TransInventoryAdjustmentHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use App\Services\InventoryAdjustmentJournalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $term = $request->term;
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $query = TransInventoryAdjustmentHD::query()
                ->when($term, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('TransactionNo', 'like', "%{$term}%");

                        try {
                            $date = Carbon::parse($term)->format('Y-m-d');
                            $q->orWhereDate('TransactionDate', $date);
                        } catch (\Exception $e) {
                            // not a date, ignore
                        }
                    });
                });

            $this->applyWarehouseAuthorization($query);

            $paginator = $query
                ->latest()
                ->paginate($perPage, [
                    'TransactionNo',
                    'TransactionDate',
                    'ExpiredDate',
                    'WarehouseID',
                    'DivisionID',
                    'InventoryTypeID',
                    'Notes',
                    'Editable',
                ], 'page', $page);

            $result = [
                'transactions' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Stock Adjustment fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getTransactionDetails(GetTransactionDetailsRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $headerQuery = TransInventoryAdjustmentHD::query()
                ->select([
                    'TransactionNo',
                    'TransactionDate',
                    'ExpiredDate',
                    'WarehouseID',
                    'DivisionID',
                    'InventoryTypeID',
                    'Notes',
                    'Editable',
                ])
                ->with([
                    'warehouse:WarehouseID,WarehouseName',
                    'division:DivisionID,DivisionName',
                    'type:InventoryTypeID,InventoryTypeName',
                ]);

            $this->applyWarehouseAuthorization($headerQuery);

            $transaction = $headerQuery->find($request->transaction_no);

            if ($transaction) {
                $detailsPaginator = TransInventoryAdjustmentDT::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->with([
                        'part:PartID,PartName,Active,WithSerialNo',
                        'unit:UnitID,UnitName',
                    ])
                    ->paginate($perPage, [
                        'TransactionNo',
                        'PartID',
                        'UnitID',
                        'QtyStock',
                        'QtyOpname',
                        'BatchNo',
                        'Notes',
                    ], 'page', $page);

                $details = $detailsPaginator->items();
                $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch(collect($details));
                foreach ($details as $detail) {
                    $secondary = DualQuantityHelper::currentStockSecondaryQuantity(
                        $detail->PartID,
                        $transaction->WarehouseID,
                        $detail->BatchNo
                    );
                    $qty2Stock = $secondary['qty2'];
                    $detail->CoilNo = $detail->BatchNo !== null ? $coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $detail->BatchNo)) : null;
                    $detail->Qty2 = $qty2Stock;
                    $detail->Qty2Stock = $qty2Stock;
                    $detail->UnitID2 = $secondary['unit_id2'];
                    $detail->weight_per_piece = DualQuantityHelper::weightPerPiece((float) $detail->QtyStock, $qty2Stock);
                }

                $pagination = [
                    'current_page' => $detailsPaginator->currentPage(),
                    'per_page' => $detailsPaginator->perPage(),
                    'total' => $detailsPaginator->total(),
                    'last_page' => $detailsPaginator->lastPage(),
                    'has_more' => $detailsPaginator->hasMorePages(),
                ];

                $checkers = TransInventoryAdjustmentCheckers::where('TransactionNo', $transaction->TransactionNo)
                    ->with('employee:EmployeeID,FirstName,LastName')
                    ->get();
            } else {
                $details = [];
                $pagination = [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                    'has_more' => false,
                ];
                $checkers = [];
            }

            $result = [
                'transaction' => $transaction,
                'details' => $details,
                'checkers' => $checkers,
                'pagination' => $pagination,
            ];

            return ResponseFormatter::success($result, 'Stock Adjustment details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getStockDetail(GetStockDetailRequest $request): JsonResponse
    {
        try {
            $part = MsPart::where('PartID', $request->part_id)->firstOrFail();
            $stockFilters = $this->submittedStockFilters($request->all());
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);
            $transactionDate = Carbon::parse($request->input('transaction_date', Carbon::today()))->format('Y-m-d');

            $stock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $request->part_id,
                $request->warehouse_id,
                $stockFilters['BatchNo'],
                $transactionDate
            );
            $secondary = DualQuantityHelper::currentStockSecondaryQuantity(
                $request->part_id,
                $request->warehouse_id,
                $stockFilters['BatchNo']
            );
            $stockQty2 = $secondary['qty2'];
            $unitId2 = $secondary['unit_id2'];

            $result = [
                'part_id' => $part->PartID,
                'part_name' => $part->PartName ?? '',
                'with_serial_no' => 0,
                'unit_id' => $this->lowestUnit($request->part_id),
                'qty_stock' => (float) $stock,
                'qty2_stock' => $stockQty2,
                'unit_id2' => $unitId2,
                'weight_per_piece' => DualQuantityHelper::weightPerPiece((float) $stock, $stockQty2),
                'batch_no' => $stockAttributes['BatchNo'],
                'coil_no' => $stockAttributes['BatchNo'] !== null ? CoilNoHelper::get($part->PartID, $stockAttributes['BatchNo']) : null,
            ];

            return ResponseFormatter::success($result, 'Stock detail fetched successfully')->toResponse();
        } catch (ValidationException $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function storeTransaction(StoreTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $this->validateDetails($request->details, $request->warehouse_id);
            $this->validateCheckers($request->checkers);

            $numbering = $this->resolveTransactionNo($request);
            $id = $numbering['transaction_no'];
            $user = Auth::user();

            TransInventoryAdjustmentHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'ExpiredDate' => Carbon::parse($request->expired_date)->format('Y-m-d'),
                'StockOpnameNo' => '',
                'WarehouseID' => $request->warehouse_id,
                'DivisionID' => $request->division_id,
                'InventoryTypeID' => $request->inventory_type_id ?: null,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'CreatedBy' => $user->UserID,
                'EntryTime' => now(),
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
                'IsAuto' => $request->transaction_no ? 0 : 1,
                'LastDigit' => $numbering['last_digit'],
                'Editable' => true,
            ]);

            $this->insertAdjustmentRows($request->details, $id, $request->warehouse_id, $request->transaction_date);
            $this->insertCheckers($request->checkers, $id);

            InventoryAdjustmentJournalService::rebuild($id);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Stock Adjustment successfully created!',
                'transaction_no' => $id,
            ], 'Stock Adjustment successfully created!')->toResponse();
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function updateTransaction(UpdateTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $adjust = TransInventoryAdjustmentHD::with('details')->where('TransactionNo', $request->transaction_no)->firstOrFail();

            if ($adjust->Editable == 0) {
                throw new ValidationException('This transaction cannot be updated.');
            }

            $this->validateDetails($request->details, $request->warehouse_id);
            $this->validateCheckers($request->checkers);

            $user = Auth::user();
            BukuStock::where('TransactionNo', $adjust->TransactionNo)->delete();
            TransInventoryAdjustmentCheckers::where('TransactionNo', $adjust->TransactionNo)->delete();
            TransInventoryAdjustmentExecution::where('TransactionNo', $adjust->TransactionNo)->delete();
            TransInventoryAdjustmentDT::where('TransactionNo', $adjust->TransactionNo)->delete();

            $adjust->update([
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'ExpiredDate' => Carbon::parse($request->expired_date)->format('Y-m-d'),
                'WarehouseID' => $request->warehouse_id,
                'DivisionID' => $request->division_id,
                'InventoryTypeID' => $request->inventory_type_id ?: null,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
            ]);

            $this->insertAdjustmentRows($request->details, $adjust->TransactionNo, $request->warehouse_id, $request->transaction_date);
            $this->insertCheckers($request->checkers, $adjust->TransactionNo);

            InventoryAdjustmentJournalService::rebuild($adjust->TransactionNo);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Stock Adjustment successfully updated!',
                'transaction_no' => $adjust->TransactionNo,
            ], 'Stock Adjustment successfully updated!')->toResponse();
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function deleteTransaction(DeleteTransactionRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $adjust = TransInventoryAdjustmentHD::with('details')->where('TransactionNo', $request->transaction_no)->first();
                if (!$adjust) {
                    throw new ValidationException('Stock Adjustment does not exist.');
                }

                if ($adjust->Editable == 0) {
                    throw new ValidationException('This transaction cannot be deleted.');
                }

                BukuStock::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransJournalDT::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentCheckers::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentExecution::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentDT::where('TransactionNo', $adjust->TransactionNo)->delete();
                $adjust->delete();
            });

            return ResponseFormatter::success([
                'message' => 'Stock Adjustment successfully deleted!',
            ], 'Stock Adjustment successfully deleted!')->toResponse();
        } catch (ValidationException $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    /**
     * Same pattern as this app's other Api\Transaction controllers
     * (Trans_UserWarehouseHD/DT lookup). Unlike PartUsage,
     * WarehouseID lives on the header here, so it's filtered directly
     * instead of via a details relation.
     */
    private function applyWarehouseAuthorization($query, string $column = 'WarehouseID'): void
    {
        if (!ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            return;
        }

        $user = Auth::user();
        if (!$user || $user->hasPermissionTo('admin')) {
            return;
        }

        $userId = $user->UserID;
        $allowedWarehouseIds = [];

        $hd = TransUserWarehouseHD::where('UserID', $userId)
            ->orderBy('EntryTime', 'desc')
            ->first();

        if ($hd) {
            $effectiveDate = Carbon::parse($hd->EffectiveDate);

            if (Carbon::now()->gte($effectiveDate)) {
                $allowedWarehouseIds = TransUserWarehouseDT::where('UserID', $userId)
                    ->pluck('WarehouseID')
                    ->toArray();
            }
        }

        if (empty($allowedWarehouseIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn($column, $allowedWarehouseIds);
        }
    }

    private function resolveTransactionNo($request): array
    {
        if ($request->transaction_no) {
            return [
                'transaction_no' => trim($request->transaction_no),
                'last_digit' => null,
            ];
        }

        $masterAuto = MsAutoNumber::find('1');
        $transactionDate = Carbon::parse($request->transaction_date);

        $checkLast = TransInventoryAdjustmentHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', $transactionDate->month)
            ->whereYear('TransactionDate', $transactionDate->year)
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;

        do {
            $id = $masterAuto->Inventory04 . '/' . $transactionDate->format('Y')
                . '/' . $transactionDate->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

            $exists = TransInventoryAdjustmentHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        return [
            'transaction_no' => $id,
            'last_digit' => $digit,
        ];
    }

    /**
     * Manual adjustment only: QtyStock is always the current calculated
     * stock (no Stock Opname linking in the Api version).
     */
    private function insertAdjustmentRows(array $detailsData, string $transactionNo, string $warehouseId, string $transactionDate): void
    {
        $details = [];
        $executions = [];
        $bukuStockRows = [];
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');
        $user = Auth::user();

        foreach ($detailsData as $i => $detail) {
            $partId = $detail['part_id'];
            $part = MsPart::where('PartID', $partId)->firstOrFail();
            $stockFilters = $this->submittedStockFilters($detail);
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);

            $unitId = $detail['unit_id'] ?? $this->lowestUnit($partId);
            $qtyStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $partId,
                $warehouseId,
                $stockFilters['BatchNo'],
                $formattedTransactionDate
            );
            $qtyOpname = (float) ($detail['qty_opname'] ?? 0);
            $difference = $qtyOpname - $qtyStock;

            $details[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'QtyStock' => $qtyStock,
                'QtyOpname' => $qtyOpname,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'Notes' => '',
            ];

            $executions[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'Qty' => $difference,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ];

            $secondaryColumns = DualQuantityHelper::bukuStockSecondaryColumns(
                $difference,
                $partId,
                $warehouseId,
                $stockAttributes['BatchNo']
            );

            $bukuStockRows[] = array_merge([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $formattedTransactionDate,
                'PartID' => $partId,
                'WarehouseID' => $warehouseId,
                'Sequence' => $i,
                'UnitID' => $unitId,
                'Qty' => $difference,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'TransactionType' => 'INVENTORY_ADJUSTMENT',
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ], $secondaryColumns);
        }

        $this->insertInChunks(TransInventoryAdjustmentDT::class, $details);
        $this->insertInChunks(TransInventoryAdjustmentExecution::class, $executions);
        $this->insertInChunks(BukuStock::class, $bukuStockRows);
    }

    private function insertCheckers(array $checkersData, string $transactionNo): void
    {
        $checkers = [];
        foreach ($checkersData as $checker) {
            $checkers[] = [
                'TransactionNo' => $transactionNo,
                'EmployeeID' => $checker['employee_id'],
                'Status' => $checker['status'] ?? 'STAFF',
            ];
        }

        $this->insertInChunks(TransInventoryAdjustmentCheckers::class, $checkers);
    }

    private function validateDetails(array $details, string $warehouseId): void
    {
        $seen = [];
        foreach ($details as $detail) {
            $partId = $detail['part_id'];
            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new ValidationException("Part {$partId} does not exist.");
            }

            $qtyOpname = (float) ($detail['qty_opname'] ?? 0);
            if ($qtyOpname < 0) {
                throw new ValidationException('Opname Qty is invalid!');
            }

            $stockAttributes = $this->stockFiltersForInsert($this->submittedStockFilters($detail));

            $key = implode('|', [
                $partId,
                $detail['unit_id'] ?? '',
                $stockAttributes['BatchNo'] ?? '',
            ]);

            if (isset($seen[$key])) {
                throw new ValidationException("Duplicate stock detail found for Part {$partId}.");
            }

            $seen[$key] = true;
        }
    }

    private function validateCheckers(array $checkers): void
    {
        $hasSupervisor = collect($checkers)->contains(fn ($checker) => ($checker['status'] ?? null) === 'SUPERVISOR');

        if (!$hasSupervisor) {
            throw new ValidationException('Need at least 1 checker as supervisor!');
        }
    }

    private function submittedStockFilters(array $detail): array
    {
        return [
            'BatchNo' => $this->submittedStockValue($detail['batch_no'] ?? null),
        ];
    }

    private function submittedStockValue($value): ?string
    {
        $value = $this->nullableDetailValue($value);

        return $value === null ? '__NULL__' : $value;
    }

    private function stockFiltersForInsert(array $stockFilters): array
    {
        $attributes = [];
        foreach ($stockFilters as $key => $value) {
            $attributes[$key] = $value === '__NULL__' ? null : $value;
        }

        return $attributes;
    }

    private function nullableDetailValue(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }

    private function lowestUnit(string $partId): ?string
    {
        $unit = MsPartUnit::where('PartID', $partId)
            ->where('Conversion', 1)
            ->orderBy('Sequence')
            ->first();

        if ($unit) {
            return $unit->UnitID2;
        }

        $fallback = MsPartUnit::where('PartID', $partId)
            ->orderBy('Sequence')
            ->first();

        return $fallback ? ($fallback->UnitID1 ?? $fallback->UnitID2) : null;
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            if (count($chunk) > 0) {
                $modelClass::insert($chunk);
            }
        }
    }
}
