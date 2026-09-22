<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ItemTransfer\DeleteTransactionRequest;
use App\Http\Requests\Transaction\ItemTransfer\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\ItemTransfer\GetTransactionRequest;
use App\Http\Requests\Transaction\ItemTransfer\StoreTransactionRequest;
use App\Http\Requests\Transaction\ItemTransfer\UpdateTransactionRequest;
use App\Models\BukuStock;
use App\Models\ControlPanel;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsQR;
use App\Models\TransDirectItemTransferDT;
use App\Models\TransDirectItemTransferHD;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ItemTransferController extends Controller
{
    private const LOCATION_ADJUSTMENT_TRANSACTION_TYPE = 'LOCATION_ADJUSTMENT';

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
                });

            $this->applyWarehouseAuthorization($query);
            $this->excludeLocationAdjustmentTransactions($query);

            $paginator = $query
                ->latest()
                ->paginate($perPage, [
                    'TransactionNo',
                    'TransactionDate',
                    'WarehouseIDFrom',
                    'WarehouseIDTo',
                    'StaffInChargeIDFrom',
                    'StaffInChargeIDTo',
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

            return ResponseFormatter::success($result, 'Item Transfer fetched successfully')->toResponse();
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

            $this->applyWarehouseAuthorization($headerQuery);
            $this->excludeLocationAdjustmentTransactions($headerQuery);

            $txNo = trim($request->transaction_no);
            $transaction = $headerQuery->where(function ($q) use ($txNo) {
                $q->where('TransactionNo', $txNo)
                  ->orWhere('TransactionNo', 'like', $txNo . '%');
            })->first();

            Log::info('API getTransactionDetails called for: ' . $request->transaction_no . ' -> Found: ' . ($transaction ? $transaction->TransactionNo : 'NULL'));

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
                    ], 'page', $page);

                $details = $detailsPaginator->items();
                $stockBySequence = BukuStock::query()
                    ->where('TransactionNo', $transaction->TransactionNo)
                    ->where('WarehouseID', $transaction->WarehouseIDFrom)
                    ->where('Qty', '<', 0)
                    ->get(['Sequence', 'BatchNo', 'Qty', 'UnitID', 'Qty2', 'UnitID2'])
                    ->keyBy('Sequence');

                foreach ($details as $detail) {
                    $stockDetail = $stockBySequence[$detail->Sequence] ?? null;
                    $detail->BatchNo = $stockDetail->BatchNo ?? null;
                    $detail->QtyBase = $stockDetail && $stockDetail->Qty !== null ? abs((float) $stockDetail->Qty) : null;
                    $detail->UnitIDBase = $stockDetail->UnitID ?? null;
                    $detail->Qty2 = $stockDetail && $stockDetail->Qty2 !== null ? abs((float) $stockDetail->Qty2) : null;
                    $detail->UnitID2 = $stockDetail->UnitID2 ?? null;
                    $detail->weight_per_piece = DualQuantityHelper::weightPerPiece($detail->QtyBase, $detail->Qty2);
                }

                $coilNoByPartBatch = CoilNoHelper::lookupByPartBatch(collect($details));
                foreach ($details as $detail) {
                    $detail->CoilNo = $detail->BatchNo !== null ? $coilNoByPartBatch->get(CoilNoHelper::key($detail->PartID, $detail->BatchNo)) : null;
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

            return ResponseFormatter::success($result, 'Item Transfer details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function storeTransaction(StoreTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $warehouseIdFrom = $request->warehouse_id_from;
            $warehouseIdTo = $request->warehouse_id_to;

            $this->validateDetails($request->details, $warehouseIdFrom, $warehouseIdTo, $request->transaction_date);

            $numbering = $this->resolveTransactionNo($request);
            $id = $numbering['transaction_no'];
            $user = Auth::user();

            TransDirectItemTransferHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'WarehouseIDFrom' => $warehouseIdFrom,
                'StaffInChargeIDFrom' => $request->staff_in_charge_id_from,
                'WarehouseIDTo' => $warehouseIdTo,
                'StaffInChargeIDTo' => $request->staff_in_charge_id_to,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'CreatedBy' => $user->UserID,
                'EntryTime' => now(),
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
                'IsAuto' => $request->transaction_no ? 0 : 1,
                'LastDigit' => $numbering['last_digit'],
                'Editable' => true,
            ]);

            $generatedQrs = $this->insertTransferRows($request->details, $id, $warehouseIdFrom, $warehouseIdTo, $request->transaction_date);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Item Transfer successfully created!',
                'transaction_no' => $id,
                'generated_qrs' => $generatedQrs,
            ], 'Item Transfer successfully created!')->toResponse();
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
            $transferQuery = TransDirectItemTransferHD::with('details')->where('TransactionNo', $request->transaction_no);
            $this->excludeLocationAdjustmentTransactions($transferQuery);
            $transfer = $transferQuery->firstOrFail();

            if ($transfer->Editable == 0) {
                throw new ValidationException('This transaction cannot be updated.');
            }

            $warehouseIdFrom = $request->warehouse_id_from;
            $warehouseIdTo = $request->warehouse_id_to;
            $user = Auth::user();

            BukuStock::where('TransactionNo', $transfer->TransactionNo)->delete();
            TransDirectItemTransferDT::where('TransactionNo', $transfer->TransactionNo)->delete();
            $this->deleteGeneratedQrsForTransaction($transfer->TransactionNo);

            $this->validateDetails($request->details, $warehouseIdFrom, $warehouseIdTo, $request->transaction_date);

            $transfer->update([
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'WarehouseIDFrom' => $warehouseIdFrom,
                'StaffInChargeIDFrom' => $request->staff_in_charge_id_from,
                'WarehouseIDTo' => $warehouseIdTo,
                'StaffInChargeIDTo' => $request->staff_in_charge_id_to,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
            ]);

            $generatedQrs = $this->insertTransferRows($request->details, $transfer->TransactionNo, $warehouseIdFrom, $warehouseIdTo, $request->transaction_date);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Item Transfer successfully updated!',
                'transaction_no' => $transfer->TransactionNo,
                'generated_qrs' => $generatedQrs,
            ], 'Item Transfer successfully updated!')->toResponse();
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
                $transferQuery = TransDirectItemTransferHD::with('details')->where('TransactionNo', $request->transaction_no);
                $this->excludeLocationAdjustmentTransactions($transferQuery);
                $transfer = $transferQuery->first();
                if (!$transfer) {
                    throw new ValidationException('Item Transfer does not exist.');
                }

                if ($transfer->Editable == 0) {
                    throw new ValidationException('This transaction cannot be deleted.');
                }

                BukuStock::where('TransactionNo', $transfer->TransactionNo)->delete();
                TransDirectItemTransferDT::where('TransactionNo', $transfer->TransactionNo)->delete();
                $this->deleteGeneratedQrsForTransaction($transfer->TransactionNo);
                $transfer->delete();
            });

            return ResponseFormatter::success([
                'message' => 'Item Transfer successfully deleted!',
            ], 'Item Transfer successfully deleted!')->toResponse();
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
     * (Trans_UserWarehouseHD/DT lookup), allowing transactions where the user
     * can access either the source or target warehouse.
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
            $query->where(function ($q) use ($allowedWarehouseIds) {
                $q->whereIn('WarehouseIDFrom', $allowedWarehouseIds)
                    ->orWhereIn('WarehouseIDTo', $allowedWarehouseIds);
            });
        }
    }

    private function excludeLocationAdjustmentTransactions($query): void
    {
        $query->whereNotExists(function ($subQuery) {
            $subQuery->selectRaw('1')
                ->from('Buku_Stock')
                ->whereColumn('Buku_Stock.TransactionNo', 'Trans_DirectItemTransferHD.TransactionNo')
                ->where('Buku_Stock.TransactionType', self::LOCATION_ADJUSTMENT_TRANSACTION_TYPE);
        });
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

        $checkLast = TransDirectItemTransferHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', $transactionDate->month)
            ->whereYear('TransactionDate', $transactionDate->year)
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;

        do {
            $id = $masterAuto->Inventory10 . '/' . $transactionDate->format('Y')
                . '/' . $transactionDate->format('m')
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

    private function validateDetails(array $details, string $warehouseIdFrom, string $warehouseIdTo, string $transactionDate): void
    {
        $seen = [];
        $sourceTotals = [];
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');

        foreach ($details as $detail) {
            $partId = $detail['part_id'];
            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new ValidationException("Part {$partId} does not exist.");
            }

            $qty = (float) ($detail['qty'] ?? 0);
            $conversion = (float) ($detail['conversion'] ?? 1);
            if ($qty <= 0) {
                throw new ValidationException("Qty must be greater than 0 for Part {$partId}.");
            }

            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForMessage = $batchNo === '__NULL__' ? null : $batchNo;

            $key = implode('|', [
                $partId,
                $detail['unit_id'] ?? '',
                $batchForMessage ?? '',
            ]);

            if (isset($seen[$key])) {
                throw new ValidationException("Duplicate stock detail found for Part {$partId}.");
            }
            $seen[$key] = true;

            $sourceKey = implode('|', [$partId, $batchForMessage ?? '']);
            if (!isset($sourceTotals[$sourceKey])) {
                $sourceTotals[$sourceKey] = [
                    'part_id' => $partId,
                    'batch_no' => $batchNo,
                    'batch_for_message' => $batchForMessage,
                    'qty' => 0.0,
                ];
            }
            $sourceTotals[$sourceKey]['qty'] += $qty * $conversion;
        }

        foreach ($sourceTotals as $sourceTotal) {
            $availableStock = $this->calculateAvailableStock(
                $sourceTotal['part_id'],
                $warehouseIdFrom,
                $sourceTotal['batch_no'],
                $formattedTransactionDate
            );

            if ($sourceTotal['qty'] > $availableStock) {
                throw new ValidationException($this->stockNotEnoughMessage($sourceTotal['part_id'], $warehouseIdFrom, $sourceTotal['batch_for_message']));
            }
        }
    }

    private function insertTransferRows(array $detailsData, string $transactionNo, string $warehouseIdFrom, string $warehouseIdTo, string $transactionDate): array
    {
        $details = [];
        $bukuStockRows = [];
        $generatedQrs = [];
        $formattedTransactionDate = Carbon::parse($transactionDate)->format('Y-m-d');
        $user = Auth::user();
        $sourceStockByIdentity = $this->sourceStockByIdentity($detailsData, $warehouseIdFrom, $formattedTransactionDate);
        $transferTotalsByIdentity = $this->transferTotalsByIdentity($detailsData);

        foreach ($detailsData as $i => $detail) {
            $partId = $detail['part_id'];
            MsPart::where('PartID', $partId)->firstOrFail();

            $qty = (float) $detail['qty'];
            $conversion = (float) $detail['conversion'];
            $baseQty = $qty * $conversion;
            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForInsert = $batchNo === '__NULL__' ? null : $batchNo;

            $details[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $detail['unit_id'],
                'Qty' => $qty,
                'Conversion' => $conversion,
                'Dimension' => $detail['dimension'] ?? '',
                'CartoonNo' => $detail['cartoon_no'] ?? '',
                'Notes' => $detail['notes'] ?? '',
                'Sequence' => $i,
            ];

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

            if ($this->shouldGenerateSplitQr($partId, $batchForInsert, $baseQty, $sourceStockByIdentity, $transferTotalsByIdentity)) {
                $generatedQrs = array_merge($generatedQrs, $this->createSplitQrs([
                    'transaction_no' => $transactionNo,
                    'sequence' => $i,
                    'part_id' => $partId,
                    'source_warehouse_id' => $warehouseIdFrom,
                    'target_warehouse_id' => $warehouseIdTo,
                    'unit_id' => $detail['unit_id'],
                    'conversion' => $conversion,
                    'transfer_qty' => $baseQty,
                    'batch_no' => $batchForInsert,
                    'transfer_qty2' => $secondaryColumnsTarget['Qty2'] ?? null,
                    'unit_id2' => $secondaryColumnsTarget['UnitID2'] ?? null,
                    'source_stock' => $sourceStockByIdentity[$this->stockIdentityKey($partId, $batchForInsert)] ?? [],
                    'transfer_total' => $transferTotalsByIdentity[$this->stockIdentityKey($partId, $batchForInsert)] ?? 0,
                ]));
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
                'TransactionType' => 'DIRECT_ITEMTRANSFER',
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
                'TransactionType' => 'DIRECT_ITEMTRANSFER',
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ], $secondaryColumnsTarget);
        }

        $this->insertInChunks(TransDirectItemTransferDT::class, $details);
        $this->insertInChunks(BukuStock::class, $bukuStockRows);

        return $generatedQrs;
    }

    private function sourceStockByIdentity(array $detailsData, string $warehouseIdFrom, string $transactionDate): array
    {
        $stockByIdentity = [];

        foreach ($detailsData as $detail) {
            $partId = $detail['part_id'];
            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForInsert = $batchNo === '__NULL__' ? null : $batchNo;
            $identityKey = $this->stockIdentityKey($partId, $batchForInsert);

            if (isset($stockByIdentity[$identityKey])) {
                continue;
            }

            $secondary = DualQuantityHelper::currentStockSecondaryQuantity(
                $partId,
                $warehouseIdFrom,
                $batchForInsert
            );

            $stockByIdentity[$identityKey] = [
                'qty1' => $this->calculateAvailableStock(
                    $partId,
                    $warehouseIdFrom,
                    $batchNo,
                    $transactionDate
                ),
                'qty2' => $secondary['qty2'],
                'unit_id2' => $secondary['unit_id2'],
            ];
        }

        return $stockByIdentity;
    }

    private function transferTotalsByIdentity(array $detailsData): array
    {
        $totals = [];

        foreach ($detailsData as $detail) {
            $partId = $detail['part_id'];
            $batchNo = $this->submittedStockValue($detail['batch_no'] ?? null);
            $batchForInsert = $batchNo === '__NULL__' ? null : $batchNo;
            $identityKey = $this->stockIdentityKey($partId, $batchForInsert);

            $totals[$identityKey] = ($totals[$identityKey] ?? 0.0)
                + ((float) $detail['qty'] * (float) $detail['conversion']);
        }

        return $totals;
    }

    private function shouldGenerateSplitQr(string $partId, ?string $batchNo, float $baseQty, array $sourceStockByIdentity, array $transferTotalsByIdentity): bool
    {
        $identityKey = $this->stockIdentityKey($partId, $batchNo);
        $sourceStock = (float) ($sourceStockByIdentity[$identityKey]['qty1'] ?? 0);
        $transferTotal = (float) ($transferTotalsByIdentity[$identityKey] ?? 0);

        return $baseQty > 0
            && $sourceStock > 0
            && $transferTotal + 0.000001 < $sourceStock;
    }

    private function createSplitQrs(array $data): array
    {
        $sourceStock = $data['source_stock'] ?? [];
        $sourceQty = (float) ($sourceStock['qty1'] ?? 0);
        $sourceQty2 = ($sourceStock['qty2'] ?? null) !== null ? (float) $sourceStock['qty2'] : null;
        $transferTotal = (float) ($data['transfer_total'] ?? 0);
        $remainingQty = max($sourceQty - $transferTotal, 0);
        $remainingQty2 = null;

        if ($sourceQty > 0.000001 && $sourceQty2 !== null) {
            $remainingQty2 = round($remainingQty / $sourceQty * $sourceQty2, 2);
        }

        return [
            $this->createSplitQr([
                'transaction_no' => $data['transaction_no'],
                'sequence' => $data['sequence'],
                'label_type' => 'TRANSFER',
                'part_id' => $data['part_id'],
                'warehouse_id' => $data['target_warehouse_id'],
                'unit_id' => $data['unit_id'],
                'conversion' => $data['conversion'],
                'qty' => $data['transfer_qty'],
                'batch_no' => $data['batch_no'],
                'qty2' => $data['transfer_qty2'],
                'unit_id2' => $data['unit_id2'],
            ]),
            $this->createSplitQr([
                'transaction_no' => $data['transaction_no'],
                'sequence' => $data['sequence'],
                'label_type' => 'SISA',
                'part_id' => $data['part_id'],
                'warehouse_id' => $data['source_warehouse_id'],
                'unit_id' => $data['unit_id'],
                'conversion' => $data['conversion'],
                'qty' => $remainingQty,
                'batch_no' => $data['batch_no'],
                'qty2' => $remainingQty2,
                'unit_id2' => $data['unit_id2'] ?? $sourceStock['unit_id2'] ?? null,
            ]),
        ];
    }

    private function createSplitQr(array $data): array
    {
        $code = $this->generateQrCode();
        $sourceQr = $this->findSharedQrRecord($data['part_id'], $data['batch_no']);
        $sourcePayload = $sourceQr?->json_display['data'] ?? $sourceQr?->json_value['data'] ?? [];

        $payload = array_merge($sourcePayload, [
            'Code' => $code,
            'PartID' => $data['part_id'],
            'WarehouseID' => $data['warehouse_id'],
            'UnitID' => $data['unit_id'],
            'Conversion' => $data['conversion'],
            'Qty' => $data['qty'],
            'NettWeight' => $data['qty'],
            'BatchNo' => $data['batch_no'],
            'TransactionNo' => $data['transaction_no'],
            'Sequence' => $data['sequence'],
            'LabelType' => $data['label_type'],
        ]);

        if (!isset($payload['CoilNo']) || $payload['CoilNo'] === '') {
            $payload['CoilNo'] = CoilNoHelper::get($data['part_id'], $data['batch_no']);
        }

        if ($data['qty2'] !== null) {
            $payload['Qty2'] = $data['qty2'];
        }

        if ($data['unit_id2'] !== null) {
            $payload['UnitID2'] = $data['unit_id2'];
        }

        $showContent = $sourceQr ? (bool) $sourceQr->show_content : false;

        MsQR::create([
            'code' => $code,
            'json_value' => ['data' => $payload],
            'json_display' => ['data' => $showContent ? $payload : ['Code' => $code]],
            'show_content' => $showContent,
        ]);

        return $payload;
    }

    private function findSharedQrRecord(string $partId, ?string $batchNo): ?MsQR
    {
        $partId = trim($partId);
        $batchNo = trim((string) $batchNo);

        if ($partId === '' || $batchNo === '') {
            return null;
        }

        return MsQR::query()
            ->whereRaw("LTRIM(RTRIM(JSON_VALUE(json_value, '$.data.PartID'))) = ?", [$partId])
            ->whereRaw("LTRIM(RTRIM(JSON_VALUE(json_value, '$.data.BatchNo'))) = ?", [$batchNo])
            ->orderByDesc('id')
            ->get()
            ->first(function (MsQR $qr) use ($partId, $batchNo) {
                $payload = $qr->json_value['data'] ?? [];

                return trim((string) ($payload['PartID'] ?? '')) === $partId
                    && trim((string) ($payload['BatchNo'] ?? '')) === $batchNo;
            });
    }

    private function deleteGeneratedQrsForTransaction(string $transactionNo): void
    {
        MsQR::query()
            ->whereRaw("JSON_VALUE(json_value, '$.data.TransactionNo') = ?", [$transactionNo])
            ->delete();
    }

    private function generateQrCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (MsQR::where('code', $code)->exists());

        return $code;
    }

    private function stockIdentityKey(string $partId, ?string $batchNo): string
    {
        return implode('|', [$partId, $batchNo ?? '__NULL__']);
    }

    private function calculateAvailableStock(string $partId, string $warehouseId, ?string $batchNo, string $transactionDate): float
    {
        return BukuStockHelper::calculateCurrentStockByBatchNo($partId, $warehouseId, $batchNo, $transactionDate);
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

    private function submittedStockValue($value): ?string
    {
        $value = $value === null || trim((string) $value) === '' ? null : trim((string) $value);

        return $value === null ? '__NULL__' : $value;
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
