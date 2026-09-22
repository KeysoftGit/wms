<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\PartUsage\DeleteTransactionRequest;
use App\Http\Requests\Transaction\PartUsage\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\PartUsage\GetTransactionRequest;
use App\Http\Requests\Transaction\PartUsage\StoreTransactionRequest;
use App\Http\Requests\Transaction\PartUsage\UpdateTransactionRequest;
use App\Models\BukuStock;
use App\Models\ControlPanel;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPartUsageDT;
use App\Models\TransPartUsageHD;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use App\Services\PartUsageJournalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartUsageController extends Controller
{
    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $term = $request->term;
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $query = TransPartUsageHD::query()
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
                    'DivisionID',
                    'Editable',
                    'Notes',
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

            return ResponseFormatter::success($result, 'Part Usage fetched successfully')->toResponse();
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

            $headerQuery = TransPartUsageHD::query()
                ->select([
                    'TransactionNo',
                    'TransactionDate',
                    'ExpiredDate',
                    'DivisionID',
                    'Editable',
                    'Notes',
                ])
                ->with('division:DivisionID,DivisionName,Active');

            $this->applyWarehouseAuthorization($headerQuery);

            $transaction = $headerQuery->find($request->transaction_no);

            if ($transaction) {
                $detailsPaginator = TransPartUsageDT::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->with([
                        'part:PartID,PartName,Active',
                        'unit:UnitID,UnitName',
                        'warehouse:WarehouseID,WarehouseName,DivisionID,Active',
                    ])
                    ->paginate($perPage, [
                        'TransactionNo',
                        'PartID',
                        'UnitID',
                        'WarehouseID',
                        'Qty',
                        'BatchNo',
                        'Notes',
                    ], 'page', $page);

                $details = $detailsPaginator->items();
                $stockRows = BukuStock::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->where('TransactionType', 'PART_USAGE')
                    ->where('Qty', '<', 0)
                    ->get(['PartID', 'WarehouseID', 'BatchNo', 'Qty', 'UnitID', 'Qty2', 'UnitID2'])
                    ->keyBy(fn ($row) => $this->usageStockKey($row->PartID, $row->WarehouseID, $row->BatchNo));
                $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch(collect($details));

                foreach ($details as $detail) {
                    $stockRow = $stockRows[$this->usageStockKey($detail->PartID, $detail->WarehouseID, $detail->BatchNo)] ?? null;
                    $qtyBase = $stockRow && $stockRow->Qty !== null ? abs((float) $stockRow->Qty) : (float) $detail->Qty;

                    $detail->CoilNo = $detail->BatchNo !== null
                        ? ($coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $detail->BatchNo)) ?? $coilNoByPartBatch->get(CoilNoHelper::batchKey($detail->BatchNo)))
                        : null;
                    $detail->QtyBase = $qtyBase;
                    $detail->UnitIDBase = $stockRow->UnitID ?? $detail->UnitID;
                    $detail->Qty2 = $stockRow && $stockRow->Qty2 !== null ? abs((float) $stockRow->Qty2) : null;
                    $detail->UnitID2 = $stockRow->UnitID2 ?? null;
                    $detail->weight_per_piece = DualQuantityHelper::weightPerPiece($qtyBase, $detail->Qty2);
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

            $result = [
                'transaction' => $transaction,
                'details' => $details,
                'pagination' => $pagination,
            ];

            return ResponseFormatter::success($result, 'Part usage details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function storeTransaction(StoreTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $numbering = $this->resolveTransactionNo($request);
            $id = $numbering['transaction_no'];
            $user = Auth::user();

            TransPartUsageHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'ExpiredDate' => $request->expired_date ? Carbon::parse($request->expired_date)->format('Y-m-d') : null,
                'WONumber' => $request->wo_number,
                'DivisionID' => $request->division_id,
                'Notes' => $request->notes ?? '-',
                'CreatedBy' => $user->UserID,
                'EntryTime' => now(),
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
                'Editable' => 1,
                'IsAuto' => $request->transaction_no ? 0 : 1,
                'LastDigit' => $numbering['last_digit'],
            ]);

            $this->insertUsageRows($request->details, $id, $request->transaction_date);
            PartUsageJournalService::rebuild($id);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Part Usage successfully created!',
                'transaction_no' => $id,
            ], 'Part Usage successfully created!')->toResponse();
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
            $header = TransPartUsageHD::with('details')->where('TransactionNo', $request->transaction_no)->firstOrFail();

            if ($header->Editable == 0) {
                throw new ValidationException('This transaction cannot be updated.');
            }

            $user = Auth::user();

            $header->update([
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'ExpiredDate' => $request->expired_date ? Carbon::parse($request->expired_date)->format('Y-m-d') : null,
                'WONumber' => $request->wo_number,
                'DivisionID' => $request->division_id,
                'Notes' => $request->notes ?? '-',
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
            ]);

            BukuStock::where('TransactionNo', $header->TransactionNo)->delete();
            TransPartUsageDT::where('TransactionNo', $header->TransactionNo)->delete();
            $this->insertUsageRows($request->details, $header->TransactionNo, $request->transaction_date);
            PartUsageJournalService::rebuild($header->TransactionNo);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Part Usage successfully updated!',
                'transaction_no' => $header->TransactionNo,
            ], 'Part Usage successfully updated!')->toResponse();
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
                $usage = TransPartUsageHD::with('details')->where('TransactionNo', $request->transaction_no)->first();
                if (!$usage) {
                    throw new ValidationException('Data not found');
                }

                if ($usage->Editable == 0) {
                    throw new ValidationException('This transaction cannot be deleted.');
                }

                BukuStock::where('TransactionNo', $usage->TransactionNo)->delete();
                TransJournalDT::where('TransactionNo', $usage->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $usage->TransactionNo)->delete();
                $usage->details()->delete();
                $usage->delete();
            });

            return ResponseFormatter::success([
                'message' => 'Part Usage successfully deleted!',
            ], 'Part Usage successfully deleted!')->toResponse();
        } catch (ValidationException $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    /**
     * Ported from keyone-wms's tested implement_user_warehouse_mapping logic
     * (Trans_UserWarehouseHD/DT lookup), applied to the header's detail rows
     * (warehouse lives per-line here, same shape as Purchase Request).
     */
    private function applyWarehouseAuthorization($query): void
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
            $query->whereDoesntHave('details', function ($detailQuery) use ($allowedWarehouseIds) {
                $detailQuery->where(function ($q) use ($allowedWarehouseIds) {
                    $q->whereNotIn('WarehouseID', $allowedWarehouseIds)
                        ->orWhereNull('WarehouseID');
                });
            });
        }
    }

    /**
     * Same LastDigit-based numbering algorithm as this app's own web
     * PartUsageController::resolveTransactionNo (matches keyone-wms's
     * Store handler too, same MsAutoNumber->PartUsage prefix).
     */
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

        $checkLast = TransPartUsageHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', $transactionDate->month)
            ->whereYear('TransactionDate', $transactionDate->year)
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;

        do {
            $id = $masterAuto->PartUsage . '/' . $transactionDate->format('Y')
                . '/' . $transactionDate->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

            $exists = TransPartUsageHD::where('TransactionNo', $id)->exists();
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
     * Ported from this app's own web PartUsageController::insertUsageRows —
     * NOT keyone-wms's version, which decrements a separate Inventory table
     * this app doesn't actually use. Stock is validated/mutated entirely
     * through the BukuStock ledger here, matching the real web behavior.
     */
    private function insertUsageRows(array $detailsData, string $transactionNo, string $transactionDate): void
    {
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');

        foreach ($detailsData as $detail) {
            $partId = $detail['part_id'];
            $warehouseId = $detail['warehouse_id'];
            $qty = (float) ($detail['qty'] ?? 0);

            if ($qty <= 0) {
                throw new ValidationException("Qty must be greater than 0 for Part {$partId}.");
            }

            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new ValidationException("Part {$partId} does not exist.");
            }

            $lowestUnit = MsPartUnit::where('PartID', $partId)
                ->where('Conversion', 1)
                ->first();

            if (!$lowestUnit) {
                throw new ValidationException("Lowest unit with conversion 1 is not configured for Part {$partId}.");
            }

            $unitId = $lowestUnit->UnitID2;
            $conversion = (float) $lowestUnit->Conversion;
            $usedQty = $qty * $conversion;

            $stockFilters = $this->submittedStockFilters($detail);
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);

            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $partId,
                $warehouseId,
                $stockFilters['BatchNo'],
                $formattedTransactionDate
            );

            if ($usedQty > $availableStock) {
                throw new ValidationException($this->stockNotEnoughMessage($partId, $warehouseId, $stockAttributes));
            }

            TransPartUsageDT::insert([
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'WarehouseID' => $warehouseId,
                'Qty' => $usedQty,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'Notes' => $detail['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $qtyDelta = -$usedQty;
            $secondaryColumns = DualQuantityHelper::bukuStockSecondaryColumns(
                $qtyDelta,
                $partId,
                $warehouseId,
                $stockAttributes['BatchNo']
            );

            BukuStock::insert(array_merge([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $formattedTransactionDate,
                'PartID' => $partId,
                'WarehouseID' => $warehouseId,
                'Sequence' => null,
                'UnitID' => $unitId,
                'Qty' => $qtyDelta,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'TransactionType' => 'PART_USAGE',
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'Notes' => $detail['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $secondaryColumns));
        }
    }

    private function submittedStockFilters(array $detail): array
    {
        return [
            'BatchNo' => $this->submittedStockValue($detail['batch_no'] ?? null),
        ];
    }

    private function usageStockKey($partId, $warehouseId, $batchNo): string
    {
        return strtolower(trim((string) $partId)) . '|'
            . strtolower(trim((string) $warehouseId)) . '|'
            . strtolower(trim((string) $batchNo));
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

    private function stockNotEnoughMessage(string $partId, string $warehouseId, array $stockAttributes): string
    {
        $details = [
            'Part' => $partId,
            'Warehouse' => $warehouseId,
            'Batch No' => $stockAttributes['BatchNo'] ?? null,
        ];

        $detailText = collect($details)
            ->map(fn ($value, $label) => $label . ': ' . ($value === null || $value === '' ? '(Empty)' : $value))
            ->implode(', ');

        return 'Stock is not enough for selected stock details. ' . $detailText . '.';
    }

    private function nullableDetailValue(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
