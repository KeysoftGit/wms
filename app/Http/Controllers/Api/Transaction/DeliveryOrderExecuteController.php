<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\DeliveryOrderExecute\DeleteTransactionRequest;
use App\Http\Requests\Transaction\DeliveryOrderExecute\ExecuteTransactionRequest;
use App\Http\Requests\Transaction\DeliveryOrderExecute\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\DeliveryOrderExecute\GetTransactionRequest;
use App\Models\BukuStock;
use App\Models\MsWarehouse;
use App\Models\TransDeliveryOrderDT;
use App\Models\TransDeliveryOrderHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransSalesOrderHD;
use App\Services\DeliveryOrderJournalService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryOrderExecuteController extends Controller
{
    private const TRANSACTION_TYPE = 'DELIVERY_ORDER';

    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('do_execute.view');

            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);
            $status = $request->status ?: 'pending';

            $query = TransDeliveryOrderHD::query()
                ->with([
                    'reff.customer:CustomerID,CustomerName',
                ])
                ->whereHas('details.part', fn ($query) => $query->where('PartType', 'S'))
                ->select([
                    'id',
                    'TransactionNo',
                    'TransactionDate',
                    'ReffNumber',
                    'VehicleID',
                    'DriverID',
                    'Notes',
                    'Outstanding',
                    'Editable',
                ])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Buku_Stock
                        WHERE Buku_Stock.TransactionNo = Trans_DeliveryOrderHD.TransactionNo
                            AND Buku_Stock.TransactionType = ?
                    ) THEN 1 ELSE 0 END AS executed
                ", [self::TRANSACTION_TYPE])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Trans_SalesOrderHD
                        WHERE Trans_SalesOrderHD.TransactionNo = Trans_DeliveryOrderHD.ReffNumber
                            AND Trans_SalesOrderHD.Closed = 1
                    ) THEN 1 ELSE 0 END AS so_closed
                ")
                ->selectRaw("(
                    SELECT SUM(Qty)
                    FROM Trans_DeliveryOrderDT
                    INNER JOIN Ms_Part ON Ms_Part.PartID = Trans_DeliveryOrderDT.PartID
                    WHERE Trans_DeliveryOrderDT.TransactionNo = Trans_DeliveryOrderHD.TransactionNo
                        AND Ms_Part.PartType = 'S'
                ) AS total_qty")
                ->selectRaw("(
                    SELECT SUM(Qty2)
                    FROM Trans_DeliveryOrderDT
                    INNER JOIN Ms_Part ON Ms_Part.PartID = Trans_DeliveryOrderDT.PartID
                    WHERE Trans_DeliveryOrderDT.TransactionNo = Trans_DeliveryOrderHD.TransactionNo
                        AND Ms_Part.PartType = 'S'
                ) AS total_qty2")
                ->when($request->term, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('Trans_DeliveryOrderHD.TransactionNo', 'like', "%{$term}%")
                            ->orWhere('Trans_DeliveryOrderHD.ReffNumber', 'like', "%{$term}%")
                            ->orWhereHas('reff.customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('CustomerName', 'like', "%{$term}%");
                            });

                        try {
                            $q->orWhereDate('Trans_DeliveryOrderHD.TransactionDate', Carbon::parse($term)->format('Y-m-d'));
                        } catch (\Exception $e) {
                            // term is not a date, ignore date search
                        }
                    });
                })
                ->when($request->date_from, fn ($query, $date) => $query->whereDate('Trans_DeliveryOrderHD.TransactionDate', '>=', Carbon::parse($date)->format('Y-m-d')))
                ->when($request->date_to, fn ($query, $date) => $query->whereDate('Trans_DeliveryOrderHD.TransactionDate', '<=', Carbon::parse($date)->format('Y-m-d')));

            $this->applyStockDetailsAccess($query);
            $this->applyStatusFilter($query, $status);

            $paginator = $query
                ->orderBy('Trans_DeliveryOrderHD.TransactionDate', 'desc')
                ->orderBy('Trans_DeliveryOrderHD.TransactionNo', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $transactions = collect($paginator->items())
                ->map(fn ($transaction) => $this->formatTransaction($transaction))
                ->values();

            return ResponseFormatter::success([
                'transactions' => $transactions,
                'pagination' => $this->pagination($paginator),
            ], 'Delivery Order Execute fetched successfully')->toResponse();
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
            $this->ensurePermission('do_execute.view');

            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $transactionQuery = TransDeliveryOrderHD::query()
                ->with([
                    'reff.customer:CustomerID,CustomerName',
                ])
                ->where('TransactionNo', $request->transaction_no)
                ->select([
                    'id',
                    'TransactionNo',
                    'TransactionDate',
                    'ReffNumber',
                    'VehicleID',
                    'DriverID',
                    'Notes',
                    'Outstanding',
                    'Editable',
                ])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Buku_Stock
                        WHERE Buku_Stock.TransactionNo = Trans_DeliveryOrderHD.TransactionNo
                            AND Buku_Stock.TransactionType = ?
                    ) THEN 1 ELSE 0 END AS executed
                ", [self::TRANSACTION_TYPE])
                ->selectRaw("
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM Trans_SalesOrderHD
                        WHERE Trans_SalesOrderHD.TransactionNo = Trans_DeliveryOrderHD.ReffNumber
                            AND Trans_SalesOrderHD.Closed = 1
                    ) THEN 1 ELSE 0 END AS so_closed
                ");

            $this->applyStockDetailsAccess($transactionQuery);
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
                ], 'Delivery Order Execute details fetched successfully')->toResponse();
            }

            $detailsPaginator = TransDeliveryOrderDT::query()
                ->where('TransactionNo', $transaction->TransactionNo)
                ->whereHas('part', fn ($query) => $query->where('PartType', 'S'))
                ->with([
                    'part:PartID,PartName,Active,WithSerialNo,PartType',
                    'unit:UnitID,UnitName',
                    'division:DivisionID,DivisionName',
                    'warehouse:WarehouseID,WarehouseName',
                ])
                ->orderBy('Sequence')
                ->paginate($perPage, [
                    'TransactionNo',
                    'PartID',
                    'Sequence',
                    'UnitID',
                    'Qty',
                    'Qty2',
                    'UnitID2',
                    'DivisionID',
                    'WarehouseID',
                    'BatchNo',
                ], 'page', $page);

            $executedStocks = BukuStock::query()
                ->where('TransactionNo', $transaction->TransactionNo)
                ->where('TransactionType', self::TRANSACTION_TYPE)
                ->get(['Sequence', 'WarehouseID', 'Qty', 'Qty2', 'UnitID2'])
                ->keyBy('Sequence');

            $allowedWarehouseIds = WarehouseAccessCriteria::allowedIdsWithChildren();
            $detailRows = collect($detailsPaginator->items());
            $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch($detailRows);

            $details = $detailRows
                ->map(function ($detail) use ($executedStocks, $allowedWarehouseIds, $coilNoByPartBatch) {
                    $executedStock = $executedStocks[$detail->Sequence] ?? null;
                    $selectedWarehouseId = $executedStock?->WarehouseID;
                    $coilNo = $detail->BatchNo !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $detail->BatchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($detail->BatchNo)))
                        : null;

                    if ($executedStock !== null) {
                        $qty2 = $executedStock->Qty2 !== null ? abs((float) $executedStock->Qty2) : null;
                        $unitId2 = $executedStock->UnitID2;
                    } else {
                        $secondary = DualQuantityHelper::proportionalQty2ForStockDelta(
                            -1 * (float) $detail->Qty,
                            $detail->PartID,
                            $detail->WarehouseID,
                            $detail->BatchNo
                        );
                        $qty2 = $secondary['qty2'] !== null ? abs((float) $secondary['qty2']) : null;
                        $unitId2 = $secondary['unit_id2'];
                    }

                    return [
                        'transaction_no' => $detail->TransactionNo,
                        'sequence' => $detail->Sequence,
                        'part_id' => $detail->PartID,
                        'part' => $detail->part,
                        'warehouse_id' => $detail->WarehouseID,
                        'warehouse' => $detail->warehouse,
                        'instruction_warehouse_id' => $detail->WarehouseID,
                        'instruction_warehouse_name' => $detail->warehouse?->WarehouseName,
                        'selected_warehouse_id' => $selectedWarehouseId,
                        'selected_warehouse_name' => $selectedWarehouseId ? MsWarehouse::where('WarehouseID', $selectedWarehouseId)->value('WarehouseName') : null,
                        'real_locations' => $this->realLocationsForDetail($detail, $allowedWarehouseIds),
                        'division_id' => $detail->DivisionID,
                        'division' => $detail->division,
                        'unit_id' => $detail->UnitID,
                        'unit' => $detail->unit,
                        'qty' => (float) $detail->Qty,
                        'qty2' => $qty2,
                        'unit_id2' => $unitId2,
                        'weight_per_piece' => DualQuantityHelper::weightPerPiece((float) $detail->Qty, $qty2),
                        'batch_no' => $detail->BatchNo,
                        'coil_no' => $coilNo,
                        'CoilNo' => $coilNo,
                        'serial_no' => null,
                        'exp_date' => null,
                        'bin' => null,
                        'loc' => null,
                        'executed_qty' => $executedStock ? abs((float) ($executedStock->Qty2 ?? $executedStock->Qty)) : 0,
                    ];
                })
                ->values();

            return ResponseFormatter::success([
                'transaction' => $this->formatTransaction($transaction),
                'details' => $details,
                'pagination' => $this->pagination($detailsPaginator),
            ], 'Delivery Order Execute details fetched successfully')->toResponse();
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
            $this->ensurePermission('do_execute.add');

            $transactionNo = trim($request->transaction_no);

            DB::transaction(function () use ($request, $transactionNo) {
                $deliveryOrder = $this->findAuthorizedDeliveryOrder($transactionNo);
                if (!$deliveryOrder) {
                    throw new ValidationException('Delivery Order does not exist or is not authorized.');
                }

                if ($this->isExecuted($deliveryOrder->TransactionNo)) {
                    throw new ValidationException('Delivery Order already executed.');
                }

                $this->ensureSalesOrderOpen($deliveryOrder);
                $this->insertExecutionRows($deliveryOrder, $request->input('details', []), $request->input('vehicle_id'), $request->input('driver_id'));

                $this->rebuildJournal($deliveryOrder->TransactionNo);
            });

            return ResponseFormatter::success([
                'transaction_no' => $transactionNo,
                'executed' => true,
            ], 'Delivery Order successfully executed!')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseFormatter::error(
                collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                StatusCodeEnum::BAD_REQUEST
            )->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function updateExecution(ExecuteTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('do_execute.add');

            $transactionNo = trim($request->transaction_no);

            DB::transaction(function () use ($request, $transactionNo) {
                $deliveryOrder = $this->findAuthorizedDeliveryOrder($transactionNo);
                if (!$deliveryOrder) {
                    throw new ValidationException('Delivery Order does not exist or is not authorized.');
                }

                if (!$this->isExecuted($deliveryOrder->TransactionNo)) {
                    throw new ValidationException('Delivery Order has not been executed.');
                }

                $this->ensureSalesOrderOpen($deliveryOrder);
                $this->clearExecutionArtifacts($deliveryOrder->TransactionNo);
                $this->insertExecutionRows($deliveryOrder, $request->input('details', []), $request->input('vehicle_id'), $request->input('driver_id'));

                $this->rebuildJournal($deliveryOrder->TransactionNo);
            });

            return ResponseFormatter::success([
                'transaction_no' => $transactionNo,
                'executed' => true,
            ], 'Delivery Order execution successfully updated!')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseFormatter::error(
                collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                StatusCodeEnum::BAD_REQUEST
            )->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function deleteExecution(DeleteTransactionRequest $request): JsonResponse
    {
        try {
            $this->ensurePermission('do_execute.add');

            $transactionNo = trim($request->transaction_no);

            DB::transaction(function () use ($transactionNo) {
                $deliveryOrder = $this->findAuthorizedDeliveryOrder($transactionNo);
                if (!$deliveryOrder) {
                    throw new ValidationException('Delivery Order does not exist or is not authorized.');
                }

                if (!$this->isExecuted($deliveryOrder->TransactionNo)) {
                    throw new ValidationException('Delivery Order has not been executed.');
                }

                $this->ensureSalesOrderOpen($deliveryOrder);
                $this->clearExecutionArtifacts($deliveryOrder->TransactionNo);

                $deliveryOrder->update([
                    'VehicleID' => null,
                    'DriverID' => null,
                    'Editable' => 1,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);
                $this->markSalesOrderReferenceNotEditable($deliveryOrder->ReffNumber);
            });

            return ResponseFormatter::success([
                'transaction_no' => $transactionNo,
                'executed' => false,
            ], 'Delivery Order execution successfully deleted!')->toResponse();
        } catch (ValidationException $e) {
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    private function findAuthorizedDeliveryOrder(string $transactionNo): ?TransDeliveryOrderHD
    {
        $query = TransDeliveryOrderHD::query()
            ->with([
                'details' => fn ($query) => $query->whereHas('part', fn ($partQuery) => $partQuery->where('PartType', 'S')),
            ])
            ->where('TransactionNo', $transactionNo);

        $this->applyStockDetailsAccess($query);

        return $query->first();
    }

    private function ensureSalesOrderOpen(TransDeliveryOrderHD $deliveryOrder): void
    {
        $salesOrder = TransSalesOrderHD::where('TransactionNo', $deliveryOrder->ReffNumber)->first();
        if ($salesOrder && (int) ($salesOrder->Closed ?? 0) === 1) {
            throw new ValidationException('Sales Order is already closed and cannot be executed.');
        }
    }

    private function insertExecutionRows(TransDeliveryOrderHD $deliveryOrder, array $payloadDetails, string $vehicleId, string $driverId): void
    {
        $requestedStock = [];
        $stockRows = [];
        $payloadDetails = collect($payloadDetails);
        $allowedWarehouseIds = WarehouseAccessCriteria::allowedIdsWithChildren();
        $transactionDate = Carbon::parse($deliveryOrder->TransactionDate)->format('Y-m-d');

        foreach ($deliveryOrder->details as $index => $detail) {
            $payloadDetail = $payloadDetails->first(function ($row) use ($detail) {
                if (isset($row['sequence']) && (int) $row['sequence'] === (int) $detail->Sequence) {
                    $rowBatch = isset($row['batch_no']) ? trim($row['batch_no']) : null;
                    $detailBatch = isset($detail->BatchNo) ? trim($detail->BatchNo) : null;
                    if ($rowBatch === $detailBatch) {
                        return true;
                    }
                }

                return false;
            });

            if (!$payloadDetail && isset($payloadDetails[$index])) {
                $payloadDetail = $payloadDetails[$index];
            }

            if (!$payloadDetail) {
                $payloadDetail = $payloadDetails->firstWhere('sequence', (int) $detail->Sequence);
            }

            if (!$payloadDetail) {
                throw new ValidationException("Selected warehouse is required for sequence {$detail->Sequence}.");
            }

            $selectedWarehouseId = trim($payloadDetail['warehouse_id']);
            $this->validateSelectedWarehouse($detail, $selectedWarehouseId, $allowedWarehouseIds, $transactionDate);

            $batchNo = $this->nullableStockValue($detail->BatchNo);
            $movement = $this->resolveDeliveryStockMovement($detail, $selectedWarehouseId, $batchNo);
            $stockQty = abs((float) $movement['Qty']);
            $stockKey = implode('|', [
                $detail->PartID,
                $selectedWarehouseId,
                $batchNo ?? '__NULL__',
            ]);

            $requestedStock[$stockKey] = ($requestedStock[$stockKey] ?? 0) + $stockQty;

            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $detail->PartID,
                $selectedWarehouseId,
                $batchNo,
                $transactionDate
            );

            if ($requestedStock[$stockKey] > $availableStock) {
                throw new ValidationException("Can't execute {$detail->PartID} because the quantity exceeds the current buku stock amount.");
            }

            $qtyDelta = $stockQty * -1;

            $stockRows[] = array_merge([
                'TransactionNo' => $deliveryOrder->TransactionNo,
                'TransactionDate' => $transactionDate,
                'PartID' => $detail->PartID,
                'WarehouseID' => $selectedWarehouseId,
                'Sequence' => $detail->Sequence,
                'UnitID' => $detail->UnitID,
                'Qty' => $qtyDelta,
                'BatchNo' => $batchNo,
                'SerialNo' => null,
                'ExpDate' => null,
                'TransactionType' => self::TRANSACTION_TYPE,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ], DualQuantityHelper::bukuStockSecondaryColumns(
                $qtyDelta,
                $detail->PartID,
                $selectedWarehouseId,
                $batchNo
            ));

            TransDeliveryOrderDT::where('TransactionNo', $detail->TransactionNo)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->update([
                    'WarehouseID' => $selectedWarehouseId,
                ]);
        }

        if (!empty($stockRows)) {
            BukuStock::insert($stockRows);
        }

        $deliveryOrder->update([
            'VehicleID' => $vehicleId,
            'DriverID' => $driverId,
            'Editable' => 0,
            'LastUpdateBy' => Auth::user()->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
        ]);
    }

    private function clearExecutionArtifacts(string $transactionNo): void
    {
        BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', self::TRANSACTION_TYPE)
            ->delete();
        TransJournalDT::where('TransactionNo', $transactionNo)->delete();
        TransJournalHD::where('TransactionNo', $transactionNo)->delete();
    }

    private function resolveDeliveryStockMovement($detail, string $warehouseId, ?string $batchNo): array
    {
        $source = $this->resolveSourceStockPair($detail, $warehouseId, $batchNo);
        $input = $this->resolveDeliveryInputQuantity($detail);

        $sourceQty = abs((float) $source['Qty']);
        $sourceQty2 = abs((float) $source['Qty2']);
        $inputQty = abs((float) $input['Qty']);

        if ($inputQty <= 0.000001) {
            throw new ValidationException("Delivery qty for part {$detail->PartID} must be greater than 0.");
        }

        if ($sourceQty <= 0.000001 || $sourceQty2 <= 0.000001) {
            throw new ValidationException("Stock Qty 2 for part {$detail->PartID} is not available.");
        }

        if ($input['UnitID'] === $source['UnitID2']) {
            $qty2 = $inputQty;
            $qty = $qty2 / $sourceQty2 * $sourceQty;
        } elseif ($input['UnitID'] === $source['UnitID']) {
            $qty = $inputQty;
            $qty2 = $qty / $sourceQty * $sourceQty2;
        } else {
            throw new ValidationException("Delivery unit {$input['UnitID']} for part {$detail->PartID} does not match stock units.");
        }

        return [
            'UnitID' => $source['UnitID'],
            'Qty' => $qty * -1,
            'Qty2' => $qty2 * -1,
            'UnitID2' => $source['UnitID2'],
        ];
    }

    private function resolveDeliveryInputQuantity($detail): array
    {
        $secondaryUnit = trim((string) ($detail->UnitID2 ?? ''));
        $secondaryQty = $detail->Qty2 !== null ? (float) $detail->Qty2 : null;

        if ($secondaryUnit !== '' && $secondaryQty !== null && abs($secondaryQty) > 0.000001) {
            return [
                'UnitID' => $secondaryUnit,
                'Qty' => $secondaryQty,
            ];
        }

        return [
            'UnitID' => trim((string) ($detail->UnitID ?? '')),
            'Qty' => (float) ($detail->Qty ?? 0),
        ];
    }

    private function resolveSourceStockPair($detail, string $warehouseId, ?string $batchNo): array
    {
        $query = BukuStock::query()
            ->where('PartID', $detail->PartID)
            ->where('WarehouseID', $warehouseId);

        if ($batchNo === null) {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $batchNo);
        }

        foreach (['SerialNo', 'BIN', 'LOC'] as $column) {
            $value = $detail->{$column} ?? null;
            if ($value === null || $value === '') {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        $expDate = $detail->ExpDate ?? null;
        if ($expDate === null || $expDate === '') {
            $query->whereNull('ExpDate');
        } else {
            $query->whereDate('ExpDate', $expDate);
        }

        $stockRows = $query
            ->select('UnitID', 'UnitID2')
            ->selectRaw('SUM(Qty) as Qty')
            ->selectRaw('SUM(Qty2) as Qty2')
            ->groupBy('UnitID', 'UnitID2')
            ->get()
            ->filter(fn ($row) => trim((string) $row->UnitID) !== ''
                && trim((string) $row->UnitID2) !== ''
                && abs((float) $row->Qty) > 0.000001
                && abs((float) $row->Qty2) > 0.000001)
            ->values();

        if ($stockRows->isEmpty()) {
            throw new ValidationException("Stock for part {$detail->PartID} is insufficient.");
        }

        $unitPairs = $stockRows
            ->map(fn ($row) => trim((string) $row->UnitID) . '|' . trim((string) $row->UnitID2))
            ->unique()
            ->values();
        if ($unitPairs->count() > 1) {
            throw new ValidationException("Stock for part {$detail->PartID} has multiple unit pairs for the selected batch.");
        }

        $stockRow = $stockRows->first();

        return [
            'UnitID' => trim((string) $stockRow->UnitID),
            'Qty' => (float) $stockRow->Qty,
            'Qty2' => (float) $stockRow->Qty2,
            'UnitID2' => trim((string) $stockRow->UnitID2),
        ];
    }

    private function markSalesOrderReferenceNotEditable(?string $salesOrderNo): void
    {
        if ($salesOrderNo === null || trim($salesOrderNo) === '') {
            return;
        }

        TransSalesOrderHD::where('TransactionNo', $salesOrderNo)
            ->update([
                'Editable' => 0,
            ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'pending') {
            $query->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DeliveryOrderHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', self::TRANSACTION_TYPE);
            });
        } elseif ($status === 'executed') {
            $query->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('Buku_Stock')
                    ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DeliveryOrderHD.TransactionNo')
                    ->where('Buku_Stock.TransactionType', self::TRANSACTION_TYPE);
            });
        }
    }

    private function applyStockDetailsAccess($query): void
    {
        $allowedIds = WarehouseAccessCriteria::allowedIds();

        if ($allowedIds === null) {
            return;
        }

        $query->whereDoesntHave('details', function ($detailQuery) use ($allowedIds) {
            $detailQuery
                ->whereHas('part', fn ($partQuery) => $partQuery->where('PartType', 'S'))
                ->where(function ($unauthorizedQuery) use ($allowedIds) {
                    $unauthorizedQuery
                        ->whereNull('WarehouseID')
                        ->orWhereNotIn('WarehouseID', $allowedIds);
                });
        });
    }

    private function validateSelectedWarehouse($detail, string $selectedWarehouseId, ?array $allowedWarehouseIds, string $transactionDate): void
    {
        $warehouse = MsWarehouse::where('WarehouseID', $selectedWarehouseId)->first();
        if (!$warehouse || (int) ($warehouse->Active ?? 0) !== 1) {
            throw new ValidationException("Selected warehouse {$selectedWarehouseId} is not active.");
        }

        if (!$this->isInstructionWarehouseOrDescendant($detail->WarehouseID, $warehouse)) {
            throw new ValidationException("Selected warehouse {$selectedWarehouseId} is not valid for sequence {$detail->Sequence}.");
        }

        if ($allowedWarehouseIds !== null && !in_array($selectedWarehouseId, $allowedWarehouseIds, true)) {
            throw new ValidationException("Selected warehouse {$selectedWarehouseId} is not authorized.");
        }

        $batchNo = $this->nullableStockValue($detail->BatchNo);
        $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
            $detail->PartID,
            $selectedWarehouseId,
            $batchNo,
            $transactionDate
        );

        if ($availableStock <= 0) {
            throw new ValidationException("Selected warehouse {$selectedWarehouseId} has no stock for {$detail->PartID}.");
        }
    }

    private function isInstructionWarehouseOrDescendant(string $instructionWarehouseId, MsWarehouse $selectedWarehouse): bool
    {
        return in_array($selectedWarehouse->WarehouseID, $this->warehouseIdsWithDescendants($instructionWarehouseId), true);
    }

    private function realLocationsForDetail($detail, ?array $allowedWarehouseIds): array
    {
        $warehouseIds = $this->warehouseIdsWithDescendants($detail->WarehouseID);

        if ($allowedWarehouseIds !== null) {
            $warehouseIds = array_values(array_intersect($warehouseIds, $allowedWarehouseIds));
        }

        if (empty($warehouseIds)) {
            return [];
        }

        $batchNo = $this->nullableStockValue($detail->BatchNo);
        $query = BukuStock::query()
            ->where('PartID', $detail->PartID)
            ->whereIn('WarehouseID', $warehouseIds);

        if ($batchNo === null) {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $batchNo);
        }

        $stockByWarehouse = $query
            ->select('WarehouseID')
            ->selectRaw('SUM(Qty) as qty_available')
            ->groupBy('WarehouseID')
            ->havingRaw('SUM(Qty) > 0')
            ->get()
            ->keyBy('WarehouseID');

        if ($stockByWarehouse->isEmpty()) {
            return [];
        }

        return MsWarehouse::whereIn('WarehouseID', $stockByWarehouse->keys()->all())
            ->get(['WarehouseID', 'WarehouseName', 'ParentID'])
            ->map(function ($warehouse) use ($stockByWarehouse) {
                return [
                    'WarehouseID' => $warehouse->WarehouseID,
                    'WarehouseName' => $warehouse->WarehouseName,
                    'ParentID' => $warehouse->ParentID,
                    'qty_available' => (float) $stockByWarehouse[$warehouse->WarehouseID]->qty_available,
                ];
            })
            ->values()
            ->all();
    }

    private function warehouseIdsWithDescendants(string $warehouseId): array
    {
        $rootQuery = MsWarehouse::where('WarehouseID', $warehouseId);
        if (is_numeric($warehouseId)) {
            $rootQuery->orWhere('id', (int) $warehouseId);
        }
        $root = $rootQuery->first();

        $warehouseIds = [$root?->WarehouseID ?? $warehouseId];
        $frontier = $this->warehouseParentIdentifiers($root, $warehouseId);

        while (!empty($frontier)) {
            $children = MsWarehouse::whereIn('ParentID', $frontier)
                ->get(['id', 'WarehouseID']);

            $childWarehouseIds = $children
                ->pluck('WarehouseID')
                ->filter()
                ->values()
                ->all();

            $childWarehouseIds = array_values(array_diff($childWarehouseIds, $warehouseIds));
            if (empty($childWarehouseIds)) {
                break;
            }

            $warehouseIds = array_values(array_unique(array_merge($warehouseIds, $childWarehouseIds)));
            $frontier = $children
                ->flatMap(fn ($warehouse) => $this->warehouseParentIdentifiers($warehouse, $warehouse->WarehouseID))
                ->unique()
                ->values()
                ->all();
        }

        return $warehouseIds;
    }

    private function warehouseParentIdentifiers(?MsWarehouse $warehouse, string $fallback): array
    {
        return collect([
            $fallback,
            $warehouse?->WarehouseID,
            $warehouse?->id !== null ? (string) $warehouse->id : null,
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function formatTransaction($transaction): array
    {
        $vehicleText = $transaction->VehicleID;
        $driverText = $transaction->DriverID;

        return [
            'id' => $transaction->id,
            'transaction_no' => $transaction->TransactionNo,
            'transaction_date' => $transaction->TransactionDate,
            'reff_number' => $transaction->ReffNumber,
            'sales_order' => $transaction->reff,
            'customer' => $transaction->reff->customer ?? null,
            'vehicle_id' => $vehicleText,
            'vehicle_name' => $vehicleText,
            'vehicle' => null,
            'driver_id' => $driverText,
            'driver_name' => $driverText,
            'driver' => null,
            'shipment_address' => $transaction->ShipmentAddress,
            'notes' => $transaction->Notes,
            'outstanding' => (int) ($transaction->Outstanding ?? 0),
            'editable' => (int) ($transaction->Editable ?? 0),
            'executed' => (bool) ($transaction->executed ?? $this->isExecuted($transaction->TransactionNo)),
            'so_closed' => (bool) ($transaction->so_closed ?? false),
            'status' => $this->transactionStatus($transaction),
            'total_qty' => isset($transaction->total_qty) ? (float) $transaction->total_qty : null,
            'total_qty2' => isset($transaction->total_qty2) ? (float) $transaction->total_qty2 : null,
        ];
    }

    private function transactionStatus($transaction): string
    {
        if ((bool) ($transaction->so_closed ?? false)) {
            return 'so_closed';
        }

        return ($transaction->executed ?? $this->isExecuted($transaction->TransactionNo)) ? 'executed' : 'pending';
    }

    private function isExecuted(string $transactionNo): bool
    {
        return BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', self::TRANSACTION_TYPE)
            ->exists();
    }

    private function rebuildJournal(string $transactionNo): void
    {
        DeliveryOrderJournalService::rebuild($transactionNo);
    }

    private function nullableStockValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '' || trim($value) === '__NULL__') {
            return null;
        }

        return trim($value);
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

        throw new ValidationException('You do not have permission to access Delivery Order Execute.');
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
