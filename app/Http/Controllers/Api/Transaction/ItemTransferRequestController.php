<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransferRequest\DeleteTransactionRequest;
use App\Http\Requests\Transaction\TransferRequest\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\TransferRequest\GetTransactionRequest;
use App\Http\Requests\Transaction\TransferRequest\StoreTransactionRequest;
use App\Http\Requests\Transaction\TransferRequest\UpdateTransactionRequest;
use App\Models\ControlPanel;
use App\Models\MsAutoNumber;
use App\Models\TransItemTransferRequestDT;
use App\Models\TransItemTransferRequestHD;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemTransferRequestController extends Controller
{
    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $term = $request->term;
            $warehouseIdFrom = $request->warehouse_id_from;
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $query = TransItemTransferRequestHD::query()
                ->when($warehouseIdFrom, function ($query, $warehouseIdFrom) {
                    $query->where('WarehouseIDFrom', $warehouseIdFrom);
                })
                ->when($request->boolean('filter_remaining'), function ($query) {
                    $query->whereHas('details', function ($detailQuery) {
                        $detailQuery->where('Qty', '>', function ($subQuery) {
                            $subQuery->selectRaw('COALESCE(SUM(Trans_ItemTransferExecuteDT.Qty), 0)')
                                ->from('Trans_ItemTransferExecuteDT')
                                ->join(
                                    'Trans_ItemTransferExecuteHD',
                                    'Trans_ItemTransferExecuteDT.TransactionNo',
                                    '=',
                                    'Trans_ItemTransferExecuteHD.TransactionNo'
                                )
                                ->whereColumn('Trans_ItemTransferExecuteHD.RequestNo', 'Trans_ItemTransferRequestDT.TransactionNo')
                                ->whereColumn('Trans_ItemTransferExecuteDT.PartID', 'Trans_ItemTransferRequestDT.PartID')
                                ->whereColumn('Trans_ItemTransferExecuteDT.Sequence', 'Trans_ItemTransferRequestDT.Sequence');
                        });
                    });
                })
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

            if (!$warehouseIdFrom) {
                $this->restrictToAllowedWarehouseIds($query);
            }

            $paginator = $query
                ->latest()
                ->paginate($perPage, [
                    'TransactionNo',
                    'TransactionDate',
                    'WarehouseIDFrom',
                    'WarehouseIDTo',
                ], 'page', $page);

            $transactions = collect($paginator->items())->map(function ($transaction) {
                $transaction->Editable = $transaction->is_editable ? 1 : 0;

                return $transaction;
            });

            $result = [
                'transactions' => $transactions,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Transfer Request fetched successfully')->toResponse();
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

            $transaction = TransItemTransferRequestHD::query()
                ->with([
                    'warehouseFrom:WarehouseID,WarehouseName,DivisionID,Active',
                    'warehouseTo:WarehouseID,WarehouseName,DivisionID,Active',
                    'staffFrom:EmployeeID,FirstName,LastName,Active',
                    'staffTo:EmployeeID,FirstName,LastName,Active',
                ])
                ->find($request->transaction_no);

            if ($transaction) {
                $transaction->Editable = $transaction->is_editable ? 1 : 0;

                $detailsQuery = TransItemTransferRequestDT::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->select(['TransactionNo', 'PartID', 'UnitID', 'Qty', 'Sequence'])
                    ->with([
                        'part:PartID,PartName,Active',
                        'part.units:PartID,UnitID1,UnitID2,Conversion',
                        'part.units.unit1:UnitID,UnitName',
                        'part.units.unit2:UnitID,UnitName',
                        'unit:UnitID,UnitName',
                    ]);

                if ($request->boolean('filter_remaining')) {
                    $detailsQuery->addSelect([
                        'qty_remaining' => DB::table('Trans_ItemTransferExecuteDT')
                            ->selectRaw('Trans_ItemTransferRequestDT.Qty - COALESCE(SUM(Trans_ItemTransferExecuteDT.Qty), 0)')
                            ->join(
                                'Trans_ItemTransferExecuteHD',
                                'Trans_ItemTransferExecuteHD.TransactionNo',
                                '=',
                                'Trans_ItemTransferExecuteDT.TransactionNo'
                            )
                            ->whereColumn('Trans_ItemTransferExecuteHD.RequestNo', 'Trans_ItemTransferRequestDT.TransactionNo')
                            ->whereColumn('Trans_ItemTransferExecuteDT.PartID', 'Trans_ItemTransferRequestDT.PartID')
                            ->whereColumn('Trans_ItemTransferExecuteDT.Sequence', 'Trans_ItemTransferRequestDT.Sequence'),
                    ]);
                }

                $detailsPaginator = $detailsQuery
                    ->orderBy('Sequence')
                    ->paginate($perPage, ['*'], 'page', $page);

                $details = $detailsPaginator->items();
                $pagination = [
                    'current_page' => $detailsPaginator->currentPage(),
                    'per_page' => $detailsPaginator->perPage(),
                    'total' => $detailsPaginator->total(),
                    'last_page' => $detailsPaginator->lastPage(),
                    'has_more' => $detailsPaginator->hasMorePages(),
                ];
            } else {
                // keyone-wms's handler never 404s here — a missing transaction_no
                // still returns success with a null transaction.
                $details = [];
                $pagination = [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                    'has_more' => false,
                ];
            }

            $result = [
                'transaction' => $transaction,
                'details' => $details,
                'pagination' => $pagination,
            ];

            return ResponseFormatter::success($result, 'Transfer Request details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function storeTransaction(StoreTransactionRequest $request): JsonResponse
    {
        $transactionStarted = false;

        try {
            $transactionNo = $request->transaction_no;
            $transactionDate = Carbon::parse($request->transaction_date);
            $expiredDate = $request->expired_date ? Carbon::parse($request->expired_date) : null;
            $warehouseIdFrom = $request->warehouse_id_from;
            $warehouseIdTo = $request->warehouse_id_to;

            if ($warehouseIdFrom == $warehouseIdTo) {
                throw new ValidationException('Target warehouse must not be the same as source warehouse!');
            }

            DB::beginTransaction();
            $transactionStarted = true;

            $masterAuto = MsAutoNumber::find('1');
            $digit = null;
            $id = $transactionNo;

            if (!$id) {
                $count = TransItemTransferRequestHD::whereMonth('TransactionDate', $transactionDate->month)
                    ->whereYear('TransactionDate', $transactionDate->year)
                    ->count();
                $digit = $count + 1;

                do {
                    $id = $masterAuto->Inventory01 . '/' . $transactionDate->format('Y')
                        . '/' . $transactionDate->format('m')
                        . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

                    $checkExist = TransItemTransferRequestHD::where('TransactionNo', $id)->first();

                    if ($checkExist) {
                        $digit++;
                    }
                } while ($checkExist);
            } else {
                $id = trim($id);
            }

            TransItemTransferRequestHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => $transactionDate->format('Y-m-d'),
                'ExpiredDate' => $expiredDate?->format('Y-m-d'),
                'NeedFor' => $request->need_for,
                'WarehouseIDFrom' => $warehouseIdFrom,
                'WarehouseIDTo' => $warehouseIdTo,
                'StaffInChargeIDFrom' => $request->staff_id_from,
                'StaffInChargeIDTo' => $request->staff_id_to,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => Carbon::now('Asia/Jakarta'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => Carbon::now('Asia/Jakarta'),
            ]);

            foreach (array_chunk($this->buildDetails($id, $request->details), 10) as $chunk) {
                TransItemTransferRequestDT::insert($chunk);
            }

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Item Transfer Request successfully created!',
                'transaction_no' => $id,
            ], 'Item Transfer Request successfully created!')->toResponse();
        } catch (ValidationException $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function updateTransaction(UpdateTransactionRequest $request): JsonResponse
    {
        $transactionStarted = false;

        try {
            $warehouseIdFrom = $request->warehouse_id_from;
            $warehouseIdTo = $request->warehouse_id_to;

            if ($warehouseIdFrom == $warehouseIdTo) {
                throw new ValidationException('Target warehouse must not be the same as source warehouse!');
            }

            DB::beginTransaction();
            $transactionStarted = true;

            $transaction = TransItemTransferRequestHD::where('TransactionNo', $request->transaction_no)->firstOrFail();

            if (!$transaction->is_editable) {
                throw new ValidationException('This request has already been executed and cannot be updated.');
            }

            $transactionDate = Carbon::parse($request->transaction_date);
            $expiredDate = $request->expired_date ? Carbon::parse($request->expired_date) : null;

            $transaction->update([
                'TransactionDate' => $transactionDate->format('Y-m-d'),
                'ExpiredDate' => $expiredDate?->format('Y-m-d'),
                'NeedFor' => $request->need_for,
                'WarehouseIDFrom' => $warehouseIdFrom,
                'WarehouseIDTo' => $warehouseIdTo,
                'StaffInChargeIDFrom' => $request->staff_id_from,
                'StaffInChargeIDTo' => $request->staff_id_to,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => Carbon::now('Asia/Jakarta'),
            ]);

            TransItemTransferRequestDT::where('TransactionNo', $request->transaction_no)->delete();

            foreach (array_chunk($this->buildDetails($request->transaction_no, $request->details), 10) as $chunk) {
                TransItemTransferRequestDT::insert($chunk);
            }

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Item Transfer Request successfully updated!',
                'transaction_no' => $request->transaction_no,
            ], 'Item Transfer Request successfully updated!')->toResponse();
        } catch (ValidationException $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function deleteTransaction(DeleteTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $transaction = TransItemTransferRequestHD::where('TransactionNo', $request->transaction_no)->firstOrFail();

            if (!$transaction->is_editable) {
                throw new ValidationException('This request has already been executed and cannot be deleted.');
            }

            TransItemTransferRequestDT::where('TransactionNo', $request->transaction_no)->delete();
            $transaction->delete();

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Item Transfer Request successfully deleted!',
            ], 'Item Transfer Request successfully deleted!')->toResponse();
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

    /**
     * Ported from keyone-wms's tested implement_user_warehouse_mapping logic
     * (Trans_UserWarehouseHD/DT lookup), restricting by destination warehouse.
     */
    private function restrictToAllowedWarehouseIds($query): void
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

        $query->whereIn('WarehouseIDTo', $allowedWarehouseIds);
    }

    private function buildDetails(string $transactionNo, array $detailsData): array
    {
        $details = [];

        foreach ($detailsData as $i => $item) {
            $details[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $item['part_id'],
                'UnitID' => $item['unit_id'],
                'Qty' => $item['qty'],
                'Sequence' => $i,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ];
        }

        return $details;
    }
}
