<?php

namespace App\Http\Controllers\Sales;

use App\Helpers\BukuStockHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsPartUnit;
use App\Models\MsUnit;
use App\Models\MsWarehouse;
use App\Models\TransDeliveryOrderHD;
use App\Models\TransSalesOrderDT;
use App\Models\TransSalesOrderHD;
use App\Services\DeliveryOrderJournalService;
use App\Services\WarehouseAccessCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class DeliveryOrderExecuteController extends Controller
{
    private function isServicePartType(?string $partType): bool
    {
        return strtoupper(trim((string) $partType)) === 'N';
    }

    private function isServicePart($detail): bool
    {
        return $this->isServicePartType($detail->part->PartType ?? null);
    }

    private function applyDeliveryOrderWarehouseAccess($query)
    {
        $allowedIds = WarehouseAccessCriteria::allowedIds();

        if ($allowedIds === null) {
            return $query;
        }

        return $query->whereDoesntHave('details', function ($detailQuery) use ($allowedIds) {
            $detailQuery
                ->where(function ($unauthorizedQuery) use ($allowedIds) {
                    $unauthorizedQuery->whereNull('WarehouseID')
                        ->orWhereNotIn('WarehouseID', $allowedIds);
                })
                ->where(function ($partQuery) {
                    $partQuery->whereDoesntHave('part')
                        ->orWhereHas('part', function ($part) {
                            $part->whereNull('PartType')
                                ->orWhere('PartType', '<>', 'N');
                        });
                });
        });
    }

    public function index()
    {
        return view('sales.do_execute.index');
    }

    public function datatable(Request $request)
    {
        $data = TransDeliveryOrderHD::select('id', 'TransactionNo', 'TransactionDate', 'ReffNumber', 'VehicleID', 'DriverID', 'updated_at')
            ->selectRaw("
                CASE WHEN EXISTS (
                    SELECT 1
                    FROM Buku_Stock
                    WHERE Buku_Stock.TransactionNo = Trans_DeliveryOrderHD.TransactionNo
                        AND Buku_Stock.TransactionType = 'DELIVERY_ORDER'
                ) OR ISNULL(Trans_DeliveryOrderHD.Editable, 1) = 0 THEN 1 ELSE 0 END AS executed
            ")
            ->selectRaw("
                CASE WHEN EXISTS (
                    SELECT 1
                    FROM Trans_SalesOrderHD
                    WHERE Trans_SalesOrderHD.TransactionNo = Trans_DeliveryOrderHD.ReffNumber
                        AND Trans_SalesOrderHD.Closed = 1
                ) THEN 1 ELSE 0 END AS so_closed
            ");

        $this->applyDeliveryOrderWarehouseAccess($data);

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
            $data->where(function ($query) {
                $query->whereRaw('ISNULL(Trans_DeliveryOrderHD.Editable, 1) <> 0')
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('Buku_Stock')
                            ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DeliveryOrderHD.TransactionNo')
                            ->where('Buku_Stock.TransactionType', 'DELIVERY_ORDER');
                    });
            });
        } elseif ($request->get('status') === 'executed') {
            $data->where(function ($query) {
                $query->whereRaw('ISNULL(Trans_DeliveryOrderHD.Editable, 1) = 0')
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('Buku_Stock')
                            ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DeliveryOrderHD.TransactionNo')
                            ->where('Buku_Stock.TransactionType', 'DELIVERY_ORDER');
                    });
            });
        }

        return DataTables::of($data)
            ->addColumn('status', function ($row) {
                if ($row->so_closed) {
                    return '<span class="badge bg-danger">SO Closed</span>';
                }

                return $row->executed
                    ? '<span class="badge bg-success">Executed</span>'
                    : '<span class="badge bg-warning text-dark">Pending</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Show" href="' . route('do_execute.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if (!$row->executed && !$row->so_closed && Auth::user()->hasAnyPermission(['admin', 'do_execute.add'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Execute" href="' . route('do_execute.show', $row->id) . '"><i class="fa fa-fw fa-check"></i></a>';
                }

                return $btn . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function show($id)
    {
        $query = TransDeliveryOrderHD::with(['details.part', 'details.unit', 'details.unit2', 'details.division', 'details.warehouse', 'reff.customer'])
            ->where('id', $id);
        $this->applyDeliveryOrderWarehouseAccess($query);
        $do = $query->firstOrFail();

        $so = TransSalesOrderHD::where('TransactionNo', $do->ReffNumber)->first();
        $soClosed = (int) ($so->Closed ?? 0) === 1;
        // Closed becomes 1 automatically once everything on the SO has been delivered - that's
        // a normal, reversible state. ClosingReason means the SO was explicitly closed/
        // cancelled, which deleting this execution should not silently undo.
        $soClosedWithReason = $so && trim((string) ($so->ClosingReason ?? '')) !== '';
        $executed = $this->isExecuted($do->TransactionNo);
        $executedStocks = BukuStock::where('TransactionNo', $do->TransactionNo)
            ->where('TransactionType', 'DELIVERY_ORDER')
            ->get()
            ->keyBy('Sequence');
        $options = DocPrint::where('ModuleCode', 'DO')
            ->where('TypeStr', 'print')
            ->get();
        $detailExecutionWarehouses = $do->details
            ->mapWithKeys(fn ($detail) => [
                $detail->Sequence => $this->executionWarehouseForDetail($detail),
            ]);
        $detailStockMovements = $this->displayStockMovements($do, $executedStocks, $detailExecutionWarehouses);

        return view('sales.do_execute.show', compact('do', 'executed', 'executedStocks', 'options', 'soClosed', 'soClosedWithReason', 'detailExecutionWarehouses', 'detailStockMovements'));
    }

    private function displayStockMovements($do, $executedStocks, $detailExecutionWarehouses)
    {
        $unitNames = MsUnit::pluck('UnitName', 'UnitID');

        return $do->details->mapWithKeys(function ($detail) use ($executedStocks, $detailExecutionWarehouses, $unitNames) {
            $stock = $executedStocks[$detail->Sequence] ?? null;

            if ($stock) {
                return [
                    $detail->Sequence => (object) [
                        'Qty' => abs((float) $stock->Qty),
                        'UnitID' => $stock->UnitID,
                        'UnitName' => $unitNames[$stock->UnitID] ?? $stock->UnitID,
                        'Qty2' => abs((float) ($stock->Qty2 ?? $stock->Qty)),
                        'UnitID2' => $stock->UnitID2,
                        'UnitName2' => $unitNames[$stock->UnitID2] ?? $stock->UnitID2,
                    ],
                ];
            }

            $executionWarehouse = $detailExecutionWarehouses[$detail->Sequence] ?? null;
            if (!$executionWarehouse) {
                return [$detail->Sequence => null];
            }

            try {
                $movement = $this->resolveDeliveryStockMovement($detail, $executionWarehouse->WarehouseID);
            } catch (\Exception $exception) {
                return [$detail->Sequence => null];
            }

            return [
                $detail->Sequence => (object) [
                    'Qty' => abs((float) $movement['Qty']),
                    'UnitID' => $movement['UnitID'],
                    'UnitName' => $unitNames[$movement['UnitID']] ?? $movement['UnitID'],
                    'Qty2' => abs((float) $movement['Qty2']),
                    'UnitID2' => $movement['UnitID2'],
                    'UnitName2' => $unitNames[$movement['UnitID2']] ?? $movement['UnitID2'],
                ],
            ];
        });
    }

    public function execute(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'VehicleID' => 'required|string|max:255',
                'DriverID' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $transactionNo = null;

            DB::transaction(function () use ($request, $id, &$transactionNo) {
                $query = TransDeliveryOrderHD::with('details.part')
                    ->where('id', $id);
                $this->applyDeliveryOrderWarehouseAccess($query);
                $do = $query->firstOrFail();
                $transactionNo = $do->TransactionNo;

                $so = TransSalesOrderHD::where('TransactionNo', $do->ReffNumber)->first();
                if ($so && (int) ($so->Closed ?? 0) === 1) {
                    throw new \Exception('Sales Order is already closed and cannot be executed.');
                }

                if ($this->isExecuted($transactionNo)) {
                    throw new \Exception('Delivery Order already executed.');
                }

                if ($do->details->isEmpty()) {
                    throw new \Exception('Delivery Order has no detail.');
                }

                $requestedStock = [];
                $stockRows = [];

                foreach ($do->details as $detail) {
                    if ($this->isServicePart($detail)) {
                        continue;
                    }

                    $warehouseId = $this->resolveExecutionWarehouseId(
                        $detail,
                        $request->input('warehouse_id.' . $detail->Sequence)
                    );
                    $movement = $this->resolveDeliveryStockMovement($detail, $warehouseId);
                    $stockQty = abs((float) $movement['Qty']);
                    $stockKey = implode('|', [
                        $detail->PartID,
                        $warehouseId,
                        $detail->BatchNo ?? '__NULL__',
                        $detail->SerialNo ?? '__NULL__',
                        $detail->ExpDate ?? '__NULL__',
                    ]);

                    $requestedStock[$stockKey] = ($requestedStock[$stockKey] ?? 0) + $stockQty;

                    $availableStock = BukuStockHelper::calculateCurrentStock(
                        $detail->PartID,
                        $warehouseId,
                        $detail->BatchNo,
                        $detail->SerialNo,
                        $detail->ExpDate
                    );

                    if ($requestedStock[$stockKey] > $availableStock) {
                        throw new \Exception("Can't execute {$detail->PartID} because the quantity exceeds the current buku stock amount.");
                    }

                    $stockRows[] = [
                        'TransactionNo' => $do->TransactionNo,
                        'TransactionDate' => $do->TransactionDate,
                        'PartID' => $detail->PartID,
                        'WarehouseID' => $warehouseId,
                        'Sequence' => $detail->Sequence,
                        'UnitID' => $movement['UnitID'],
                        'Qty' => $movement['Qty'],
                        'Qty2' => $movement['Qty2'],
                        'UnitID2' => $movement['UnitID2'],
                        'BatchNo' => $detail->BatchNo,
                        'SerialNo' => $detail->SerialNo,
                        'ExpDate' => $detail->ExpDate,
                        'TransactionType' => 'DELIVERY_ORDER',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];
                }

                if (!empty($stockRows)) {
                    BukuStock::insert($stockRows);
                }

                $do->update([
                    'VehicleID' => $request->input('VehicleID'),
                    'DriverID' => $request->input('DriverID'),
                    'Editable' => 0,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

                $this->checkOutstanding($do->ReffNumber);

                $this->rebuildJournal($do->TransactionNo);
            });

            return response()->json(['message' => 'Delivery Order successfully executed!']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
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
                $query = TransDeliveryOrderHD::where('id', $id);
                $this->applyDeliveryOrderWarehouseAccess($query);
                $do = $query->firstOrFail();

                $so = TransSalesOrderHD::where('TransactionNo', $do->ReffNumber)->first();
                if ($so && trim((string) ($so->ClosingReason ?? '')) !== '') {
                    throw new \Exception('Sales Order is closed with a reason and this Delivery Order execution cannot be deleted.');
                }

                if (!$this->isExecuted($do->TransactionNo)) {
                    throw new \Exception('Delivery Order has not been executed.');
                }

                BukuStock::where('TransactionNo', $do->TransactionNo)
                    ->where('TransactionType', 'DELIVERY_ORDER')
                    ->delete();

                // Rebuilding with no shipped rows left naturally clears the journal too.
                $this->rebuildJournal($do->TransactionNo);

                $do->update([
                    'VehicleID' => null,
                    'DriverID' => null,
                    'Editable' => 1,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

                $this->checkOutstanding($do->ReffNumber);
            });

            return response()->json(['message' => 'Delivery Order execution successfully deleted!']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Something went wrong!',
            ], 400);
        }
    }

    private function isExecuted(string $transactionNo): bool
    {
        $hasStockMovement = BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', 'DELIVERY_ORDER')
            ->exists();

        if ($hasStockMovement) {
            return true;
        }

        return TransDeliveryOrderHD::where('TransactionNo', $transactionNo)
            ->whereRaw('ISNULL(Editable, 1) = 0')
            ->exists();
    }

    private function resolveExecutionWarehouseId($detail, ?string $warehouseId): string
    {
        $warehouseId = trim((string) $warehouseId);
        if ($warehouseId === '') {
            $executionWarehouse = $this->executionWarehouseForDetail($detail);
            if (!$executionWarehouse) {
                throw new \Exception("No available buku stock warehouse found for {$detail->PartID}.");
            }

            $warehouseId = $executionWarehouse->WarehouseID;
        }

        $warehouse = MsWarehouse::query()
            ->where('WarehouseID', $warehouseId)
            ->where('Active', 1)
            ->first();

        if (!$warehouse) {
            throw new \Exception("Selected warehouse for {$detail->PartID} does not exist or is inactive.");
        }

        $isDefaultWarehouse = $warehouse->WarehouseID === $detail->WarehouseID;
        $isChildWarehouse = $warehouse->ParentID === $detail->WarehouseID;
        if (!$isDefaultWarehouse && !$isChildWarehouse) {
            throw new \Exception("Selected warehouse for {$detail->PartID} must be {$detail->WarehouseID} or its child warehouse.");
        }

        $allowedIds = WarehouseAccessCriteria::allowedIdsWithChildren();
        if ($allowedIds !== null && !in_array($warehouse->WarehouseID, $allowedIds, true)) {
            throw new \Exception("Selected warehouse for {$detail->PartID} is not authorized.");
        }

        return $warehouse->WarehouseID;
    }

    private function executionWarehouseForDetail($detail)
    {
        $query = BukuStock::query()
            ->from('Buku_Stock as bs')
            ->leftJoin('Ms_Warehouse as wh', 'wh.WarehouseID', '=', 'bs.WarehouseID')
            ->where('bs.PartID', $detail->PartID)
            ->where('wh.Active', 1)
            ->where(function ($q) use ($detail) {
                $q->where('bs.WarehouseID', $detail->WarehouseID)
                    ->orWhere('wh.ParentID', $detail->WarehouseID);
            });

        if ($detail->BatchNo === null || $detail->BatchNo === '') {
            $query->whereNull('bs.BatchNo');
        } else {
            $query->where('bs.BatchNo', $detail->BatchNo);
        }

        if ($detail->SerialNo === null || $detail->SerialNo === '') {
            $query->whereNull('bs.SerialNo');
        } else {
            $query->where('bs.SerialNo', $detail->SerialNo);
        }

        if ($detail->ExpDate === null || $detail->ExpDate === '') {
            $query->whereNull('bs.ExpDate');
        } else {
            $query->whereDate('bs.ExpDate', $detail->ExpDate);
        }

        $allowedIds = WarehouseAccessCriteria::allowedIdsWithChildren();
        if ($allowedIds !== null) {
            if (empty($allowedIds)) {
                return null;
            }

            $query->whereIn('bs.WarehouseID', $allowedIds);
        }

        return $query
            ->select('bs.WarehouseID')
            ->selectRaw('MAX(wh.WarehouseName) as WarehouseName')
            ->selectRaw('SUM(bs.Qty) as qty_available')
            ->groupBy('bs.WarehouseID')
            ->havingRaw('SUM(bs.Qty) > 0')
            ->orderByRaw('CASE WHEN bs.WarehouseID = ? THEN 1 ELSE 0 END', [$detail->WarehouseID])
            ->orderByRaw('SUM(bs.Qty) DESC')
            ->first();
    }

    private function resolveDeliveryBaseQty($detail, ?string $warehouseId = null): float
    {
        $sourceQty = (float) ($detail->Qty2 ?? 0);
        if ($sourceQty > 0.000001) {
            return $sourceQty * $this->resolveDeliveryConversion($detail, $warehouseId);
        }

        return (float) $detail->Qty;
    }

    private function resolveDeliveryStockMovement($detail, ?string $warehouseId = null): array
    {
        $source = $this->resolveSourceStockPair($detail, $warehouseId);
        $input = $this->resolveDeliveryInputQuantity($detail);

        $sourceQty = abs((float) $source['Qty']);
        $sourceQty2 = abs((float) $source['Qty2']);
        $inputQty = abs((float) $input['Qty']);

        if ($inputQty <= 0.000001) {
            throw new \Exception("Delivery qty for part {$detail->PartID} must be greater than 0.");
        }

        if ($sourceQty <= 0.000001 || $sourceQty2 <= 0.000001) {
            throw new \Exception("Stock Qty 2 for part {$detail->PartID} is not available.");
        }

        if ($input['UnitID'] === $source['UnitID2']) {
            $qty2 = $inputQty;
            $qty = $qty2 / $sourceQty2 * $sourceQty;
        } elseif ($input['UnitID'] === $source['UnitID']) {
            $qty = $inputQty;
            $qty2 = $qty / $sourceQty * $sourceQty2;
        } else {
            throw new \Exception("Delivery unit {$input['UnitID']} for part {$detail->PartID} does not match stock units.");
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

    private function resolveSourceStockPair($detail, ?string $warehouseId): array
    {
        $query = BukuStock::where('PartID', $detail->PartID)
            ->where('WarehouseID', $warehouseId ?: $detail->WarehouseID);

        foreach (['BatchNo', 'SerialNo', 'BIN', 'LOC'] as $column) {
            $value = $detail->{$column};
            if ($value === null || $value === '') {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        $expDate = $detail->ExpDate;
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
            throw new \Exception("Stock for part {$detail->PartID} is insufficient.");
        }

        $unitPairs = $stockRows
            ->map(fn ($row) => trim((string) $row->UnitID) . '|' . trim((string) $row->UnitID2))
            ->unique()
            ->values();
        if ($unitPairs->count() > 1) {
            throw new \Exception("Stock for part {$detail->PartID} has multiple unit pairs for the selected batch.");
        }

        $stockRow = $stockRows->first();

        return [
            'UnitID' => trim((string) $stockRow->UnitID),
            'Qty' => (float) $stockRow->Qty,
            'Qty2' => (float) $stockRow->Qty2,
            'UnitID2' => trim((string) $stockRow->UnitID2),
        ];
    }

    private function resolveDeliveryConversion($detail, ?string $warehouseId = null): float
    {
        $stockConversion = $this->resolveStockConversion($detail, $warehouseId);
        if ($stockConversion > 0.000001) {
            return $stockConversion;
        }

        $unitId = $detail->UnitID2 ?? $detail->UnitID ?? null;
        if ($unitId) {
            $partUnit = MsPartUnit::where('PartID', $detail->PartID)
                ->where('UnitID2', $unitId)
                ->first();

            if ($partUnit && (float) $partUnit->Conversion > 0.000001) {
                return (float) $partUnit->Conversion;
            }
        }

        return 1;
    }

    private function resolveStockConversion($detail, ?string $warehouseId = null): float
    {
        $query = BukuStock::where('PartID', $detail->PartID);

        $warehouseId = $warehouseId ?: $detail->WarehouseID;
        if (!empty($warehouseId)) {
            $query->where('WarehouseID', $warehouseId);
        }

        if ($detail->BatchNo === null || $detail->BatchNo === '') {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $detail->BatchNo);
        }

        if ($detail->SerialNo === null || $detail->SerialNo === '') {
            $query->whereNull('SerialNo');
        } else {
            $query->where('SerialNo', $detail->SerialNo);
        }

        if ($detail->ExpDate === null || $detail->ExpDate === '') {
            $query->whereNull('ExpDate');
        } else {
            $query->whereDate('ExpDate', $detail->ExpDate);
        }

        if (!empty($detail->UnitID2)) {
            $query->where('UnitID2', $detail->UnitID2);
        }

        $rows = $query
            ->whereNotNull('Qty2')
            ->where('Qty2', '<>', 0)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $qtyBase = 0;
        $qtySource = 0;
        foreach ($rows as $row) {
            $qtyBase += abs((float) $row->Qty);
            $qtySource += abs((float) $row->Qty2);
        }

        if ($qtySource <= 0.000001) {
            return 0;
        }

        return $qtyBase / $qtySource;
    }

    private function checkOutstanding(string $soNumber): void
    {
        $so = TransSalesOrderHD::where('TransactionNo', $soNumber)->first();
        if (!$so || ($so->ClosingReason != null && $so->ClosingReason != '')) {
            return;
        }

        $outstanding = false;
        foreach ($so->details as $detail) {
            $orderedQty = (float) $detail->Qty;
            $maxQty = $orderedQty * (1 + (max((float) ($detail->ExcessTolerancePercentage ?? 0), 0) / 100));
            $executedQty2 = $this->deliveredQty2FromExecutedStock($soNumber, $detail);
            if ($this->decimalGreaterThan($maxQty, $executedQty2)) {
                $outstanding = true;
                break;
            }
        }

        $editable = !TransDeliveryOrderHD::where('ReffNumber', $soNumber)->first()
            && !DB::table('Trans_SalesInvoiceDT3')->where('ReffNumber', $soNumber)->first();

        $so->update([
            'Outstanding' => $outstanding ? 1 : 0,
            'Closed' => $outstanding ? 0 : 1,
            'Editable' => $editable,
        ]);
    }

    private function deliveredQty2FromExecutedStock(string $soNumber, TransSalesOrderDT $detail): float
    {
        $doNos = TransDeliveryOrderHD::where('ReffNumber', $soNumber)->pluck('TransactionNo');
        if ($doNos->isEmpty()) {
            return 0;
        }

        $rows = BukuStock::whereIn('TransactionNo', $doNos)
            ->where('TransactionType', 'DELIVERY_ORDER')
            ->where('PartID', $detail->PartID)
            ->where('Sequence', $detail->Sequence)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $qty2 = 0;
        foreach ($rows as $row) {
            $qty2 += abs((float) ($row->Qty2 ?? $row->Qty));
        }

        return $qty2;
    }

    private function decimalGreaterThan($left, $right): bool
    {
        return round((float) $left, 6) - round((float) $right, 6) > 0.000001;
    }

    private function rebuildJournal(string $transactionNo): void
    {
        DeliveryOrderJournalService::rebuild($transactionNo);
    }
}
