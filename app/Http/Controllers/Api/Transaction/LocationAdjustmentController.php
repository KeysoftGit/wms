<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\LocationAdjustment\DeleteTransactionRequest;
use App\Http\Requests\Transaction\LocationAdjustment\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\LocationAdjustment\GetTransactionRequest;
use App\Http\Requests\Transaction\LocationAdjustment\StockLocationCheckRequest;
use App\Http\Requests\Transaction\LocationAdjustment\StoreTransactionRequest;
use App\Http\Requests\Transaction\LocationAdjustment\UpdateTransactionRequest;
use App\Models\BukuStock;
use App\Models\ControlPanel;
use App\Models\MsAutoNumber;
use App\Models\MsEmployee;
use App\Models\MsPart;
use App\Models\MsWarehouse;
use App\Models\TransDirectItemTransferDT;
use App\Models\TransDirectItemTransferHD;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LocationAdjustmentController extends Controller
{
    private const TRANSACTION_TYPE = 'LOCATION_ADJUSTMENT';
    private const DEFAULT_SOURCE_WAREHOUSE_ID = 'XXX';

    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $term = $request->term;
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $query = TransDirectItemTransferHD::query()
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
                })
                ->when($request->warehouse_id, function ($query, $warehouseId) {
                    $query->where(function ($q) use ($warehouseId) {
                        $q->where('WarehouseIDTo', $warehouseId)
                            ->orWhereExists(function ($subQuery) use ($warehouseId) {
                                $subQuery->selectRaw('1')
                                    ->from('Trans_DirectItemTransferDT')
                                    ->whereColumn('Trans_DirectItemTransferDT.TransactionNo', 'Trans_DirectItemTransferHD.TransactionNo')
                                    ->where('Trans_DirectItemTransferDT.WarehouseIDFrom', $warehouseId);
                            });
                    });
                });

            $this->applyLocationAdjustmentFilter($query);
            $this->applyWarehouseAuthorization($query);

            $paginator = $query
                ->orderBy('Trans_DirectItemTransferHD.TransactionDate', 'desc')
                ->orderBy('Trans_DirectItemTransferHD.TransactionNo', 'desc')
                ->paginate($perPage, [
                    'Trans_DirectItemTransferHD.TransactionNo as TransactionNo',
                    'Trans_DirectItemTransferHD.TransactionDate as TransactionDate',
                    'Trans_DirectItemTransferHD.WarehouseIDFrom as WarehouseIDFrom',
                    'Trans_DirectItemTransferHD.WarehouseIDTo as WarehouseIDTo',
                    'Trans_DirectItemTransferHD.StaffInChargeIDFrom as StaffInChargeIDFrom',
                    'Trans_DirectItemTransferHD.StaffInChargeIDTo as StaffInChargeIDTo',
                    'Trans_DirectItemTransferHD.Notes as Notes',
                    'Trans_DirectItemTransferHD.Editable as Editable',
                ], 'page', $page);

            return ResponseFormatter::success([
                'transactions' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ], 'Location Adjustment fetched successfully')->toResponse();
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

            $headerQuery = TransDirectItemTransferHD::query()
                ->select([
                    'TransactionNo',
                    'TransactionDate',
                    'WarehouseIDFrom',
                    'WarehouseIDTo',
                    'StaffInChargeIDFrom',
                    'StaffInChargeIDTo',
                    'Notes',
                    'Editable',
                ])
                ->with([
                    'warehouseFrom:WarehouseID,WarehouseName',
                    'warehouseTo:WarehouseID,WarehouseName',
                    'staffFrom:EmployeeID,FirstName,LastName',
                    'staffTo:EmployeeID,FirstName,LastName',
                ]);

            $this->applyLocationAdjustmentFilter($headerQuery);
            $this->applyWarehouseAuthorization($headerQuery);

            $txNo = trim($request->transaction_no);
            $transaction = $headerQuery->where(function ($q) use ($txNo) {
                $q->where('TransactionNo', $txNo)
                    ->orWhere('TransactionNo', 'like', $txNo . '%');
            })->first();

            if ($transaction) {
                $detailsPaginator = TransDirectItemTransferDT::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->with([
                        'part:PartID,PartName,Active,WithSerialNo',
                        'unit:UnitID,UnitName',
                    ])
                    ->paginate($perPage, [
                        'TransactionNo',
                        'PartID',
                        'UnitID',
                        'Qty',
                        'Conversion',
                        'Dimension',
                        'CartoonNo',
                        'Notes',
                        'Sequence',
                        'WarehouseIDFrom',
                    ], 'page', $page);

                $details = $detailsPaginator->items();
                $sourceStockBySequence = BukuStock::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->where('TransactionType', self::TRANSACTION_TYPE)
                    ->where('Qty', '<', 0)
                    ->get(['Sequence', 'WarehouseID', 'BatchNo', 'Qty', 'UnitID', 'Qty2', 'UnitID2'])
                    ->keyBy('Sequence');
                $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch(collect($details));

                foreach ($details as $detail) {
                    $sourceStock = $sourceStockBySequence[$detail->Sequence] ?? null;
                    $detail->BatchNo = $sourceStock->BatchNo ?? null;
                    $detail->WarehouseIDFrom = $detail->WarehouseIDFrom ?? ($sourceStock->WarehouseID ?? null);
                    $detail->WarehouseIDTo = $transaction->WarehouseIDTo;
                    $detail->QtyBase = $sourceStock && $sourceStock->Qty !== null ? abs((float) $sourceStock->Qty) : null;
                    $detail->UnitIDBase = $sourceStock->UnitID ?? null;
                    $detail->Qty2 = $sourceStock && $sourceStock->Qty2 !== null ? abs((float) $sourceStock->Qty2) : null;
                    $detail->UnitID2 = $sourceStock->UnitID2 ?? null;
                    $detail->CoilNo = $detail->BatchNo !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $detail->BatchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($detail->BatchNo)))
                        : null;
                    $detail->weight_per_piece = DualQuantityHelper::weightPerPiece($detail->QtyBase, $detail->Qty2);
                }

                $pagination = [
                    'current_page' => $detailsPaginator->currentPage(),
                    'per_page' => $detailsPaginator->perPage(),
                    'total' => $detailsPaginator->total(),
                    'last_page' => $detailsPaginator->lastPage(),
                    'has_more' => $detailsPaginator->hasMorePages(),
                ];
            } else {
                $details = [];
                $pagination = [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                    'has_more' => false,
                ];
            }

            return ResponseFormatter::success([
                'transaction' => $transaction,
                'details' => $details,
                'pagination' => $pagination,
            ], 'Location Adjustment details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function checkStockLocation(StockLocationCheckRequest $request): JsonResponse
    {
        try {
            $physicalWarehouseId = $request->physical_warehouse_id;
            $this->ensureWarehousesAuthorized([$physicalWarehouseId]);

            $batchNo = $this->submittedStockValue($request->batch_no);
            $batchForResponse = $batchNo === '__NULL__' ? null : $batchNo;

            $locationsQuery = BukuStock::query()
                ->selectRaw('WarehouseID as warehouse_id, SUM(Qty) as qty, SUM(Qty2) as qty2')
                ->where('PartID', $request->part_id)
                ->groupBy('WarehouseID')
                ->havingRaw('SUM(Qty) > 0');

            $this->applyBatchFilter($locationsQuery, $batchNo);
            $this->applyStockWarehouseAuthorization($locationsQuery);

            $locationRows = $locationsQuery->get();

            $locations = $locationRows
                ->map(function ($row) use ($request, $batchNo) {
                    $unitId2 = $this->latestUnitId2($request->part_id, $row->warehouse_id, $batchNo);
                    $qty = (float) $row->qty;
                    $qty2 = $row->qty2 !== null ? (float) $row->qty2 : null;

                    return [
                        'warehouse_id' => $row->warehouse_id,
                        'qty' => $qty,
                        'qty2' => $qty2,
                        'unit_id2' => $unitId2,
                        'weight_per_piece' => DualQuantityHelper::weightPerPiece($qty, $qty2),
                    ];
                })
                ->values();

            $physicalLocation = $locations->firstWhere('warehouse_id', $physicalWarehouseId);
            $otherLocations = $locations
                ->filter(fn ($row) => $row['warehouse_id'] !== $physicalWarehouseId)
                ->values();

            $matched = $physicalLocation !== null && $physicalLocation['qty'] > 0;
            $suggestedFromWarehouseId = null;
            $maxAdjustableQty = 0.0;
            $maxAdjustableQty2 = null;
            $suggestedUnitId2 = null;
            $requiresManualReview = false;

            if (!$matched) {
                if ($otherLocations->count() === 1) {
                    $suggestedFromWarehouseId = $otherLocations[0]['warehouse_id'];
                    $maxAdjustableQty = (float) $otherLocations[0]['qty'];
                    $maxAdjustableQty2 = $otherLocations[0]['qty2'];
                    $suggestedUnitId2 = $otherLocations[0]['unit_id2'];
                } else {
                    $requiresManualReview = true;
                }
            }

            return ResponseFormatter::success([
                'part_id' => $request->part_id,
                'batch_no' => $batchForResponse,
                'physical_warehouse_id' => $physicalWarehouseId,
                'system_locations' => $locations,
                'matched' => $matched,
                'suggested_from_warehouse_id' => $suggestedFromWarehouseId,
                'suggested_to_warehouse_id' => $matched ? null : $physicalWarehouseId,
                'max_adjustable_qty' => $matched ? 0.0 : $maxAdjustableQty,
                'max_adjustable_qty2' => $matched ? null : $maxAdjustableQty2,
                'suggested_unit_id2' => $matched ? null : $suggestedUnitId2,
                'requires_manual_review' => $requiresManualReview,
            ], 'Stock location checked successfully')->toResponse();
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
            $physicalWarehouseId = $request->physical_warehouse_id;
            $this->ensureDefaultSourceWarehouseExists();
            $this->validateDetails($request->details, $physicalWarehouseId, $request->transaction_date);

            $user = Auth::user();
            $staffId = $this->resolveEmployeeIdForUser($user, $request->staff_in_charge_id);

            $numbering = $this->resolveTransactionNo($request->transaction_date, $request->transaction_no);
            $transactionNo = $numbering['transaction_no'];
            $this->createLocationAdjustment(
                $transactionNo,
                $numbering['last_digit'],
                $request->transaction_date,
                $physicalWarehouseId,
                $staffId,
                $request->notes,
                $request->details
            );

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Location Adjustment successfully created!',
                'transaction_no' => $transactionNo,
                'transaction_nos' => [$transactionNo],
            ], 'Location Adjustment successfully created!')->toResponse();
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
            $transaction = $this->findLocationAdjustment($request->transaction_no);
            if (!$transaction) {
                throw new ValidationException('Location Adjustment does not exist.');
            }

            if ($transaction->Editable == 0) {
                throw new ValidationException('This transaction cannot be updated.');
            }

            $physicalWarehouseId = $request->physical_warehouse_id;
            $this->ensureDefaultSourceWarehouseExists();

            BukuStock::where('TransactionNo', $transaction->TransactionNo)
                ->where('TransactionType', self::TRANSACTION_TYPE)
                ->delete();
            TransDirectItemTransferDT::where('TransactionNo', $transaction->TransactionNo)->delete();

            $this->validateDetails($request->details, $physicalWarehouseId, $request->transaction_date);

            $user = Auth::user();
            $staffId = $this->resolveEmployeeIdForUser($user, $request->staff_in_charge_id);

            $transaction->update([
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'WarehouseIDFrom' => self::DEFAULT_SOURCE_WAREHOUSE_ID,
                'StaffInChargeIDFrom' => $staffId,
                'WarehouseIDTo' => $physicalWarehouseId,
                'StaffInChargeIDTo' => $staffId,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
            ]);

            $this->insertAdjustmentRows($request->details, $transaction->TransactionNo, $physicalWarehouseId, $request->transaction_date);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Location Adjustment successfully updated!',
                'transaction_no' => $transaction->TransactionNo,
            ], 'Location Adjustment successfully updated!')->toResponse();
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
                $transaction = $this->findLocationAdjustment($request->transaction_no);
                if (!$transaction) {
                    throw new ValidationException('Location Adjustment does not exist.');
                }

                if ($transaction->Editable == 0) {
                    throw new ValidationException('This transaction cannot be deleted.');
                }

                BukuStock::where('TransactionNo', $transaction->TransactionNo)
                    ->where('TransactionType', self::TRANSACTION_TYPE)
                    ->delete();
                TransDirectItemTransferDT::where('TransactionNo', $transaction->TransactionNo)->delete();
                $transaction->delete();
            });

            return ResponseFormatter::success([
                'message' => 'Location Adjustment successfully deleted!',
            ], 'Location Adjustment successfully deleted!')->toResponse();
        } catch (ValidationException $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    private function applyLocationAdjustmentFilter($query): void
    {
        $query->whereExists(function ($subQuery) {
            $subQuery->selectRaw('1')
                ->from('Buku_Stock')
                ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DirectItemTransferHD.TransactionNo')
                ->where('Buku_Stock.TransactionType', self::TRANSACTION_TYPE);
        });
    }

    private function findLocationAdjustment(string $transactionNo): ?TransDirectItemTransferHD
    {
        $query = TransDirectItemTransferHD::query()->where('TransactionNo', $transactionNo);
        $this->applyLocationAdjustmentFilter($query);
        $this->applyWarehouseAuthorization($query);

        return $query->first();
    }

    private function isUserAdmin($user): bool
    {
        if (!$user) {
            return true;
        }
        try {
            if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                return true;
            }
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('admin')) {
                return true;
            }
        } catch (\Throwable $e) {
            // Permission or role does not exist
        }
        return false;
    }

    /**
     * Ported from keyone-wms's tested implement_user_warehouse_mapping logic
     * (Trans_UserWarehouseHD/DT lookup), applied to Location Adjustment header & details.
     */
    private function applyWarehouseAuthorization($query): void
    {
        if (!ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            return;
        }

        $user = Auth::user();
        if ($this->isUserAdmin($user)) {
            return;
        }

        $userId = $user->UserID;
        $allowedWarehouseIds = $this->allowedWarehouseIds($userId);

        if (empty($allowedWarehouseIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('Trans_DirectItemTransferHD.WarehouseIDTo', $allowedWarehouseIds)
                ->whereDoesntHave('details', function ($detailQuery) use ($allowedWarehouseIds) {
                    $detailQuery->where(function ($q) use ($allowedWarehouseIds) {
                        $q->whereNotIn('WarehouseIDFrom', $allowedWarehouseIds)
                            ->orWhereNull('WarehouseIDFrom');
                    });
                });
        }
    }

    private function applyStockWarehouseAuthorization($query): void
    {
        if (!ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            return;
        }

        $user = Auth::user();
        if ($this->isUserAdmin($user)) {
            return;
        }

        $allowedWarehouseIds = $this->allowedWarehouseIds($user->UserID);
        if (empty($allowedWarehouseIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('WarehouseID', $allowedWarehouseIds);
    }

    private function ensureWarehousesAuthorized(array $warehouseIds): void
    {
        if (!ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            return;
        }

        $user = Auth::user();
        if ($this->isUserAdmin($user)) {
            return;
        }

        $allowedWarehouseIds = $this->allowedWarehouseIds($user->UserID);
        foreach (array_unique($warehouseIds) as $warehouseId) {
            if (!in_array($warehouseId, $allowedWarehouseIds, true)) {
                throw new ValidationException("You are not authorized to access Warehouse {$warehouseId}.");
            }
        }
    }

    private function resolveEmployeeIdForUser($user, ?string $fallbackStaffId = null): string
    {
        if ($user && !empty($user->EmployeeID) && MsEmployee::where('EmployeeID', $user->EmployeeID)->exists()) {
            return $user->EmployeeID;
        }

        if ($user && !empty($user->UserID) && MsEmployee::where('EmployeeID', $user->UserID)->exists()) {
            return $user->UserID;
        }

        if (!empty($fallbackStaffId) && MsEmployee::where('EmployeeID', $fallbackStaffId)->exists()) {
            return $fallbackStaffId;
        }

        throw new ValidationException(
            'User login belum ter-map ke Employee. Hubungi admin untuk set Employee ID di master User.'
        );
    }

    private function allowedWarehouseIds(string $userId): array
    {
        $hd = TransUserWarehouseHD::where('UserID', $userId)
            ->orderBy('EntryTime', 'desc')
            ->first();

        if ($hd) {
            $effectiveDate = Carbon::parse($hd->EffectiveDate);

            if (Carbon::now()->gte($effectiveDate)) {
                return TransUserWarehouseDT::where('UserID', $userId)
                    ->pluck('WarehouseID')
                    ->toArray();
            }
        }

        return [];
    }

    private function validateDetails(array $details, string $physicalWarehouseId, string $transactionDate): void
    {
        $warehouseIds = [$physicalWarehouseId];
        $seen = [];
        $sourceTotals = [];
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');

        foreach ($details as $detail) {
            $warehouseIdFrom = $detail['warehouse_id_from'];
            $warehouseIds[] = $warehouseIdFrom;

            if ($warehouseIdFrom === $physicalWarehouseId) {
                throw new ValidationException('Source warehouse must not be the same as physical warehouse.');
            }

            $partId = $detail['part_id'];
            if (!MsPart::where('PartID', $partId)->exists()) {
                throw new ValidationException("Part {$partId} does not exist.");
            }

            $qty = (float) ($detail['qty'] ?? 0);
            $conversion = (float) ($detail['conversion'] ?? 1);
            if ($qty <= 0) {
                throw new ValidationException("Qty must be greater than 0 for Part {$partId}.");
            }

            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForKey = $batchNo === '__NULL__' ? null : $batchNo;

            $key = implode('|', [
                $warehouseIdFrom,
                $partId,
                $detail['unit_id'] ?? '',
                $batchForKey ?? '',
            ]);

            if (isset($seen[$key])) {
                throw new ValidationException("Duplicate location adjustment detail found for Part {$partId}.");
            }
            $seen[$key] = true;

            $sourceKey = implode('|', [$warehouseIdFrom, $partId, $batchForKey ?? '']);
            if (!isset($sourceTotals[$sourceKey])) {
                $sourceTotals[$sourceKey] = [
                    'warehouse_id_from' => $warehouseIdFrom,
                    'part_id' => $partId,
                    'batch_no' => $batchNo,
                    'batch_for_message' => $batchForKey,
                    'qty' => 0.0,
                ];
            }
            $sourceTotals[$sourceKey]['qty'] += $qty * $conversion;
        }

        $this->ensureWarehousesAuthorized($warehouseIds);

        foreach ($sourceTotals as $sourceTotal) {
            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $sourceTotal['part_id'],
                $sourceTotal['warehouse_id_from'],
                $sourceTotal['batch_no'],
                $formattedTransactionDate
            );

            if ($sourceTotal['qty'] > $availableStock) {
                throw new ValidationException($this->stockNotEnoughMessage(
                    $sourceTotal['part_id'],
                    $sourceTotal['warehouse_id_from'],
                    $sourceTotal['batch_for_message']
                ));
            }
        }
    }

    private function createLocationAdjustment(
        string $transactionNo,
        ?int $lastDigit,
        string $transactionDate,
        string $warehouseIdTo,
        string $staffId,
        ?string $notes,
        array $details
    ): void {
        $user = Auth::user();

        TransDirectItemTransferHD::create([
            'TransactionNo' => $transactionNo,
            'TransactionDate' => Carbon::parse($transactionDate)->format('Y-m-d'),
            'WarehouseIDFrom' => self::DEFAULT_SOURCE_WAREHOUSE_ID,
            'StaffInChargeIDFrom' => $staffId,
            'WarehouseIDTo' => $warehouseIdTo,
            'StaffInChargeIDTo' => $staffId,
            'Notes' => $notes ? trim($notes) : null,
            'CreatedBy' => $user->UserID,
            'EntryTime' => now(),
            'LastUpdateBy' => $user->UserID,
            'LastUpdate' => now(),
            'IsAuto' => $lastDigit === null ? 0 : 1,
            'LastDigit' => $lastDigit,
            'Editable' => true,
        ]);

        $this->insertAdjustmentRows($details, $transactionNo, $warehouseIdTo, $transactionDate);
    }

    private function insertAdjustmentRows(array $detailsData, string $transactionNo, string $warehouseIdTo, string $transactionDate): void
    {
        $details = [];
        $bukuStockRows = [];
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');
        $user = Auth::user();
        $hasBatchColumn = Schema::connection((new TransDirectItemTransferDT())->getConnectionName())
            ->hasColumn('Trans_DirectItemTransferDT', 'BatchNo');

        foreach ($detailsData as $i => $detail) {
            $partId = $detail['part_id'];
            $warehouseIdFrom = $detail['warehouse_id_from'];
            $qty = (float) $detail['qty'];
            $conversion = (float) $detail['conversion'];
            $baseQty = $qty * $conversion;
            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForInsert = $batchNo === '__NULL__' ? null : $batchNo;

            $detailRow = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $detail['unit_id'],
                'Qty' => $qty,
                'Conversion' => $conversion,
                'Dimension' => $detail['dimension'] ?? '',
                'CartoonNo' => $detail['cartoon_no'] ?? '',
                'Notes' => self::TRANSACTION_TYPE,
                'Sequence' => $i,
                'WarehouseIDFrom' => $warehouseIdFrom,
            ];
            if ($hasBatchColumn) {
                $detailRow['BatchNo'] = $batchForInsert;
            }
            $details[] = $detailRow;

            $secondaryColumnsSource = DualQuantityHelper::bukuStockSecondaryColumns(
                $baseQty * -1,
                $partId,
                $warehouseIdFrom,
                $batchForInsert
            );

            $secondaryColumnsTarget = [];
            if (array_key_exists('Qty2', $secondaryColumnsSource)) {
                $secondaryColumnsTarget['Qty2'] = $secondaryColumnsSource['Qty2'] !== null
                    ? -1 * $secondaryColumnsSource['Qty2']
                    : null;
            }
            if (array_key_exists('UnitID2', $secondaryColumnsSource)) {
                $secondaryColumnsTarget['UnitID2'] = $secondaryColumnsSource['UnitID2'];
            }

            $bukuStockRows[] = array_merge([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $formattedTransactionDate,
                'PartID' => $partId,
                'WarehouseID' => $warehouseIdFrom,
                'Sequence' => $i,
                'UnitID' => $detail['unit_id'],
                'Qty' => $baseQty * -1,
                'BatchNo' => $batchForInsert,
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'TransactionType' => self::TRANSACTION_TYPE,
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ], $secondaryColumnsSource);

            $bukuStockRows[] = array_merge([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $formattedTransactionDate,
                'PartID' => $partId,
                'WarehouseID' => $warehouseIdTo,
                'Sequence' => $i,
                'UnitID' => $detail['unit_id'],
                'Qty' => $baseQty,
                'BatchNo' => $batchForInsert,
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'TransactionType' => self::TRANSACTION_TYPE,
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ], $secondaryColumnsTarget);
        }

        $this->insertInChunks(TransDirectItemTransferDT::class, $details);
        $this->insertInChunks(BukuStock::class, $bukuStockRows);
    }

    private function resolveTransactionNo(string $transactionDate, ?string $manualTransactionNo = null): array
    {
        if ($manualTransactionNo) {
            return [
                'transaction_no' => trim($manualTransactionNo),
                'last_digit' => null,
            ];
        }

        $masterAuto = MsAutoNumber::find('1');
        $date = Carbon::parse($transactionDate);

        $last = TransDirectItemTransferHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', $date->month)
            ->whereYear('TransactionDate', $date->year)
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $last ? ((int) $last->LastDigit + 1) : 1;

        do {
            $id = $masterAuto->Inventory10 . '/' . $date->format('Y')
                . '/' . $date->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);
            $exists = TransDirectItemTransferHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        return [
            'transaction_no' => $id,
            'last_digit' => $digit,
        ];
    }

    private function latestUnitId2(string $partId, string $warehouseId, ?string $batchNo): ?string
    {
        $query = BukuStock::query()
            ->where('PartID', $partId)
            ->where('WarehouseID', $warehouseId)
            ->whereNotNull('UnitID2')
            ->where('UnitID2', '<>', '');

        if ($batchNo === null) {
            // no filter, aggregate across all batches
        } elseif ($batchNo === '__NULL__') {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $batchNo);
        }

        return $query->orderBy('EntryTime', 'desc')->value('UnitID2');
    }

    private function applyBatchFilter($query, ?string $batchNo): void
    {
        if ($batchNo === null) {
            return;
        }

        if ($batchNo === '__NULL__') {
            $query->whereNull('BatchNo');
            return;
        }

        $query->where('BatchNo', $batchNo);
    }

    private function submittedStockValue($value): ?string
    {
        $value = $value === null || trim((string) $value) === '' ? null : trim((string) $value);

        return $value === null ? '__NULL__' : $value;
    }

    private function stockNotEnoughMessage(string $partId, string $warehouseId, ?string $batchNo): string
    {
        $details = [
            'Part' => $partId,
            'Warehouse' => $warehouseId,
            'Batch No' => $batchNo,
        ];

        $detailText = collect($details)
            ->map(fn ($value, $label) => $label . ': ' . ($value === null || $value === '' ? '(Empty)' : $value))
            ->implode(', ');

        return 'Stock is not enough for selected stock details. ' . $detailText . '.';
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            if (count($chunk) > 0) {
                $modelClass::insert($chunk);
            }
        }
    }

    private function ensureDefaultSourceWarehouseExists(): void
    {
        MsWarehouse::firstOrCreate(
            ['WarehouseID' => self::DEFAULT_SOURCE_WAREHOUSE_ID],
            [
                'WarehouseName' => 'NONE',
                'CreatedBy' => Auth::user()->UserID ?? 'SYSTEM',
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID ?? 'SYSTEM',
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => 1,
                'IsAuto' => 0,
            ]
        );
    }
}
