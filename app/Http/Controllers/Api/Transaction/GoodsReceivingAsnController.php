<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Enums\StatusCodeEnum;
use App\Exceptions\ValidationException;
use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\GoodsReceivingAsn\DeleteTransactionRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\GetAsnDetailsRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\GetAsnOptionsRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\GetTransactionDetailsRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\GetTransactionRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\StoreTransactionRequest;
use App\Http\Requests\Transaction\GoodsReceivingAsn\UpdateTransactionRequest;
use App\Models\BukuStock;
use App\Models\MsAutoNumber;
use App\Models\MsFixedAsset;
use App\Models\MsFixedAssetCategory;
use App\Models\MsInventoryType;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\MsWarehouse;
use App\Models\TransGoodsReceivingDT;
use App\Models\TransGoodsReceivingHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPurchaseOrderDT;
use App\Models\TransPurchaseOrderHD;
use App\Models\TransQualityControlReceivingDT;
use App\Models\TransQualityControlReceivingHD;
use App\Services\GoodsReceivingJournalService;
use App\Services\PurchaseOrderStateService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoodsReceivingAsnController extends Controller
{
    private const GR_TRANSACTION_TYPE = 'GOODS_RECEIVING';
    private const ASN_GR_TRANSACTION_TYPE = 'GOODS_RECEIVING';

    private function detailValue($source, string $key, $default = null)
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        return $source->{$key} ?? $default;
    }

    private function applyDetailDutyFields(array $target, string $table, $source): array
    {
        if (Schema::connection('sqlsrv')->hasColumn($table, 'RateBeaMasuk')) {
            $target['RateBeaMasuk'] = (float) ($this->detailValue($source, 'RateBeaMasuk', 0) ?? 0);
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'RateBeaAccount')) {
            $target['RateBeaAccount'] = trim((string) ($this->detailValue($source, 'RateBeaAccount', '') ?? '')) ?: null;
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'AntiDumping')) {
            $target['AntiDumping'] = (float) ($this->detailValue($source, 'AntiDumping', 0) ?? 0);
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'AntiDumpingAccount')) {
            $target['AntiDumpingAccount'] = trim((string) ($this->detailValue($source, 'AntiDumpingAccount', '') ?? '')) ?: null;
        }

        return $target;
    }

    public function getTransaction(GetTransactionRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $query = TransGoodsReceivingHD::query()
                ->whereNotNull('ASNNumber')
                ->with([
                    'warehouse:WarehouseID,WarehouseName',
                    'currency:CurrencyID,CurrencyName',
                    'qc:TransactionNo,PONumber',
                ])
                ->when($request->term, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('TransactionNo', 'like', "%{$term}%")
                            ->orWhere('ASNNumber', 'like', "%{$term}%")
                            ->orWhereHas('qc', fn ($qcQuery) => $qcQuery->where('PONumber', 'like', "%{$term}%"));
                    });
                })
                ->when($request->warehouse_id, fn ($query, $warehouseId) => $query->where('WarehouseID', $warehouseId))
                ->when($request->date_from, fn ($query, $date) => $query->whereDate('TransactionDate', '>=', Carbon::parse($date)->format('Y-m-d')))
                ->when($request->date_to, fn ($query, $date) => $query->whereDate('TransactionDate', '<=', Carbon::parse($date)->format('Y-m-d')))
                ->when($request->has('outstanding'), fn ($query) => $query->where('Outstanding', $request->boolean('outstanding') ? 1 : 0));

            WarehouseAccessCriteria::applyWithChildren($query);

            $paginator = $query
                ->orderBy('TransactionDate', 'desc')
                ->orderBy('TransactionNo', 'desc')
                ->paginate($perPage, [
                    'TransactionNo',
                    'TransactionDate',
                    'QCNumber',
                    'ASNNumber',
                    'WarehouseID',
                    'CurrencyID',
                    'Rate',
                    'Notes',
                    'RevCount',
                    'Outstanding',
                    'Editable',
                ], 'page', $page);

            return ResponseFormatter::success([
                'transactions' => $paginator->items(),
                'pagination' => $this->pagination($paginator),
            ], 'Goods Receiving ASN fetched successfully')->toResponse();
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

            $transaction = TransGoodsReceivingHD::query()
                ->where('TransactionNo', $request->transaction_no)
                ->whereNotNull('ASNNumber')
                ->with([
                    'warehouse:WarehouseID,WarehouseName',
                    'currency:CurrencyID,CurrencyName',
                    'qc:TransactionNo,PONumber',
                ]);

            WarehouseAccessCriteria::applyWithChildren($transaction);
            $transaction = $transaction->first();

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
                ], 'Goods Receiving ASN details fetched successfully')->toResponse();
            }

            $hasDetailIdColumn = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'id');
            $columns = [
                'TransactionNo',
                'PartID',
                'Sequence',
                'UnitID',
                'Qty',
                'BatchNo',
            ];
            if ($hasDetailIdColumn) {
                array_unshift($columns, 'id');
            }
            if ($asnDetailColumn = $this->getGoodsReceivingAsnDetailColumn()) {
                $columns[] = $asnDetailColumn;
            }
            foreach (['RateBeaMasuk', 'RateBeaAccount', 'AntiDumping', 'AntiDumpingAccount'] as $dutyColumn) {
                if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', $dutyColumn)) {
                    $columns[] = $dutyColumn;
                }
            }
            for ($i = 1; $i <= (int) $transaction->RevCount; $i++) {
                $columns[] = 'ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT);
            }

            $detailsQuery = TransGoodsReceivingDT::query()
                ->where('TransactionNo', $transaction->TransactionNo)
                ->with([
                    'part:PartID,PartName,Active,WithSerialNo',
                    'unit:UnitID,UnitName',
                ])
                ->orderBy('Sequence');

            if ($hasDetailIdColumn) {
                $detailsQuery->orderBy('id');
            } else {
                $detailsQuery
                    ->orderBy('PartID')
                    ->orderBy('BatchNo')
                    ->orderBy('UnitID');
            }

            $detailsPaginator = $detailsQuery->paginate($perPage, $columns, 'page', $page);

            $asnDetailIds = $asnDetailColumn
                ? collect($detailsPaginator->items())->pluck($asnDetailColumn)->filter()
                : collect();
            $asnDetailInfoById = $this->asnDetailInfoById($transaction->ASNNumber, $asnDetailIds);

            $coilLookupRows = collect($detailsPaginator->items())->map(fn ($d) => [
                'part_id' => $d->PartID,
                'batch_no' => $d->BatchNo ?? null,
            ]);
            $coilMap = CoilNoHelper::lookupByPartBatch($coilLookupRows);

            $details = collect($detailsPaginator->items())->map(function ($detail) use ($transaction, $asnDetailColumn, $asnDetailInfoById, $coilMap) {
                $sourceKeyCandidate = $asnDetailColumn && !empty($detail->{$asnDetailColumn}) ? (string) $detail->{$asnDetailColumn} : null;
                $detail->source_detail_key = $sourceKeyCandidate ?? implode('|', [$detail->PartID, $detail->Sequence, $detail->BatchNo ?? '']);
                $asnDetailInfo = $sourceKeyCandidate !== null ? ($asnDetailInfoById[$sourceKeyCandidate] ?? null) : null;
                $detail->QtyReceive = $detail->Qty;
                $detail->WarehouseID = $transaction->WarehouseID;
                $detail->ASNNumber = $transaction->ASNNumber;
                $detail->PONumber = $transaction->qc->PONumber ?? null;

                $batchNo = $detail->BatchNo ?? null;
                $qty2 = $asnDetailInfo && $asnDetailInfo->Qty2 !== null ? (float) $asnDetailInfo->Qty2 : null;
                $unitId2 = $asnDetailInfo->UnitID2 ?? null;

                if ($qty2 === null && $batchNo !== null && trim((string) $batchNo) !== '') {
                    $secondary = DualQuantityHelper::currentStockSecondaryQuantity($detail->PartID, $transaction->WarehouseID, $batchNo, null, null, null, null, false);
                    $currentQty1 = abs((float) ($secondary['qty1'] ?? 0));
                    $currentQty2 = abs((float) ($secondary['qty2'] ?? 0));
                    if ($currentQty1 > 0.000001 && $currentQty2 > 0.000001) {
                        $qty2 = (float) $detail->Qty / $currentQty1 * $currentQty2;
                    }
                    if ($unitId2 === null) {
                        $unitId2 = $secondary['unit_id2'];
                    }
                }

                $coilNo = $asnDetailInfo->CoilNo ?? null;
                if ($coilNo === null && $batchNo !== null && trim((string) $batchNo) !== '') {
                    $coilNo = $coilMap->get(CoilNoHelper::key($detail->PartID, $batchNo))
                        ?? $coilMap->get(CoilNoHelper::batchKey($batchNo));
                }

                $detail->CoilNo = $coilNo;
                $detail->coil_no = $coilNo;
                $detail->Qty2 = $qty2;
                $detail->qty2 = $qty2;
                $detail->UnitID2 = $unitId2;
                $detail->unit_id2 = $unitId2;
                $detail->GrossWeight = $asnDetailInfo && $asnDetailInfo->GrossWeight !== null ? (float) $asnDetailInfo->GrossWeight : null;
                $detail->RateBeaMasuk = $detail->RateBeaMasuk ?? $asnDetailInfo->RateBeaMasuk ?? 0;
                $detail->RateBeaAccount = $detail->RateBeaAccount ?? $asnDetailInfo->RateBeaAccount ?? null;
                $detail->AntiDumping = $detail->AntiDumping ?? $asnDetailInfo->AntiDumping ?? 0;
                $detail->AntiDumpingAccount = $detail->AntiDumpingAccount ?? $asnDetailInfo->AntiDumpingAccount ?? null;
                $detail->weight_per_piece = DualQuantityHelper::weightPerPiece((float) $detail->Qty, $qty2);
                $detail->SerialNo = null;
                $detail->ExpDate = null;
                $detail->BIN = null;
                $detail->LOC = null;

                return $detail;
            })->values();

            return ResponseFormatter::success([
                'transaction' => $transaction,
                'details' => $details,
                'pagination' => $this->pagination($detailsPaginator),
            ], 'Goods Receiving ASN details fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getAsnOptions(GetAsnOptionsRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 20);

            $query = DB::table('Trans_AdvanceShippingNoticeHD')
                ->where(function ($q) use ($request) {
                    $q->where('Outstanding', 1);
                    if ($request->filled('include_asn_no')) {
                        $q->orWhere('TransactionNo', $request->include_asn_no);
                    }
                })
                ->when($request->search, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('TransactionNo', 'like', "%{$term}%")
                            ->orWhere('PONumber', 'like', "%{$term}%");
                    });
                });

            $this->applyAsnWarehouseAuthorization($query);

            $paginator = $query
                ->orderByDesc('TransactionDate')
                ->orderByDesc('TransactionNo')
                ->paginate($perPage, [
                    'TransactionNo',
                    'TransactionDate',
                    'PONumber',
                    'WarehouseID',
                    'DestinationWarehouseID',
                    'CurrencyID',
                    'Rate',
                    'RevCount',
                    'Outstanding',
                ], 'page', $page);

            $asns = collect($paginator->items())->map(fn ($asn) => [
                'asn_no' => $asn->TransactionNo,
                'po_no' => $asn->PONumber,
                'transaction_date' => $asn->TransactionDate,
                'warehouse_id' => $asn->DestinationWarehouseID,
                'virtual_warehouse_id' => $asn->WarehouseID,
                'currency_id' => $asn->CurrencyID,
                'rate' => (float) $asn->Rate,
                'rev_count' => (int) ($asn->RevCount ?? 0),
                'outstanding' => (int) ($asn->Outstanding ?? 0),
                'text' => $asn->TransactionNo . ' - PO ' . $asn->PONumber,
            ])->values();

            return ResponseFormatter::success([
                'asns' => $asns,
                'pagination' => $this->pagination($paginator),
            ], 'ASN options fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getAsnDetails(GetAsnDetailsRequest $request): JsonResponse
    {
        try {
            $includeClosed = $request->filled('gr_no') || $request->boolean('include_closed', false);
            $isEditContext = $request->filled('gr_no');

            if ($isEditContext) {
                $gr = $this->findAuthorizedGoodsReceiving($request->gr_no);
                if (!$gr) {
                    throw new ValidationException('Goods Receiving does not exist or is not authorized.');
                }
                $asn = DB::table('Trans_AdvanceShippingNoticeHD')
                    ->where('TransactionNo', $request->asn_no)
                    ->first();
            } else {
                $asn = $this->findAuthorizedAsn($request->asn_no, $includeClosed);
            }

            if (!$asn) {
                throw new ValidationException('ASN does not exist or is not authorized.');
            }

            $details = $this->buildAsnDetailRows($asn, $request->gr_no);

            return ResponseFormatter::success([
                'asn' => [
                    'asn_no' => $asn->TransactionNo,
                    'po_no' => $asn->PONumber,
                    'warehouse_id' => $asn->DestinationWarehouseID,
                    'virtual_warehouse_id' => $asn->WarehouseID,
                    'currency_id' => $asn->CurrencyID,
                    'rate' => (float) $asn->Rate,
                    'rev_count' => (int) ($asn->RevCount ?? 0),
                    'outstanding' => (int) ($asn->Outstanding ?? 0),
                    'receiving_warehouses' => $this->receivingWarehouses($asn->DestinationWarehouseID),
                ],
                'details' => $details,
            ], 'ASN details fetched successfully')->toResponse();
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
            $asn = $this->findAuthorizedAsn($request->asn_no);
            if (!$asn) {
                throw new ValidationException('ASN does not exist or is not authorized.');
            }
            if ((int) ($asn->Outstanding ?? 0) !== 1) {
                throw new ValidationException('ASN is not outstanding.');
            }

            $po = $this->findPo($asn->PONumber);
            $this->validateTransactionDate($request->transaction_date, $po);
            $receivingWarehouseId = $this->resolveReceivingWarehouseId($asn, $request->warehouse_id);
            $validatedDetails = $this->validateDetails($asn, $request->details, null, $request->transaction_date);
            $numbering = $this->resolveTransactionNo($request->transaction_date, $request->transaction_no, $request->boolean('is_auto', true));
            $qcNumber = $this->createQualityControl($request->transaction_date, $asn, $validatedDetails, $receivingWarehouseId);

            $grHeader = [
                'TransactionNo' => $numbering['transaction_no'],
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'QCNumber' => $qcNumber,
                'ASNNumber' => $asn->TransactionNo,
                'WarehouseID' => $receivingWarehouseId,
                'CurrencyID' => $asn->CurrencyID,
                'Rate' => $asn->Rate ?? 1,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'RevCount' => $asn->RevCount ?? 0,
                'IsAuto' => $numbering['last_digit'] === null ? 0 : 1,
                'LastDigit' => $numbering['last_digit'],
            ];

            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingHD', 'FiscalRate')) {
                $grHeader['FiscalRate'] = $asn->FiscalRate ?? 1;
            }

            TransGoodsReceivingHD::create($grHeader);

            [$grRows, $stockRows] = $this->buildReceivingRows($request->transaction_date, $numbering['transaction_no'], $asn, $validatedDetails, $receivingWarehouseId);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grRows);
            $this->insertInChunks(BukuStock::class, $stockRows);
            $this->recalculateOutstanding($asn);
            $this->rebuildJournal($numbering['transaction_no']);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Goods Receiving ASN successfully created!',
                'transaction_no' => $numbering['transaction_no'],
            ], 'Goods Receiving ASN successfully created!')->toResponse();
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
            $gr = $this->findAuthorizedGoodsReceiving($request->transaction_no);
            if (!$gr) {
                throw new ValidationException('Goods Receiving ASN does not exist or is not authorized.');
            }
            if ((int) ($gr->Editable ?? 1) === 0) {
                throw new ValidationException('This transaction cannot be updated.');
            }

            $asn = $this->findAuthorizedAsn($gr->ASNNumber, true) ?? DB::table('Trans_AdvanceShippingNoticeHD')->where('TransactionNo', $gr->ASNNumber)->first();
            if (!$asn) {
                throw new ValidationException('ASN does not exist or is not authorized.');
            }

            $po = $this->findPo($asn->PONumber);
            $this->validateTransactionDate($request->transaction_date, $po);
            $receivingWarehouseId = $this->resolveReceivingWarehouseId($asn, $request->warehouse_id);
            $this->reverseExistingRows($gr, $asn);

            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::connection('sqlsrv')->hasTable('Ms_FixedAsset')) {
                // Rebuilt below from the (possibly changed) detail lines, same as GR's own
                // detail rows and Buku Stock are rebuilt on every edit.
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }
            TransQualityControlReceivingDT::where('TransactionNo', $gr->QCNumber)->delete();

            $validatedDetails = $this->validateDetails($asn, $request->details, $gr->TransactionNo, $request->transaction_date);
            $this->insertInChunks(TransQualityControlReceivingDT::class, $this->buildQcRows($gr->QCNumber, $validatedDetails));

            $grUpdate = [
                'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                'WarehouseID' => $receivingWarehouseId,
                'CurrencyID' => $asn->CurrencyID,
                'Rate' => $asn->Rate ?? 1,
                'Notes' => $request->notes ? trim($request->notes) : null,
                'RevCount' => $asn->RevCount ?? 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ];

            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingHD', 'FiscalRate')) {
                $grUpdate['FiscalRate'] = $asn->FiscalRate ?? $gr->FiscalRate ?? 1;
            }

            $gr->update($grUpdate);

            TransQualityControlReceivingHD::where('TransactionNo', $gr->QCNumber)
                ->update([
                    'TransactionDate' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
                    'WarehouseID' => $receivingWarehouseId,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

            [$grRows, $stockRows] = $this->buildReceivingRows($request->transaction_date, $gr->TransactionNo, $asn, $validatedDetails, $receivingWarehouseId);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grRows);
            $this->insertInChunks(BukuStock::class, $stockRows);
            $this->recalculateOutstanding($asn);
            $this->rebuildJournal($gr->TransactionNo);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Goods Receiving ASN successfully updated!',
                'transaction_no' => $gr->TransactionNo,
            ], 'Goods Receiving ASN successfully updated!')->toResponse();
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('GR ASN update validation failed: ' . $e->getMessage(), [
                'transaction_no' => $request->transaction_no,
                'user_id' => Auth::user()->UserID ?? null,
                'payload' => $request->all(),
            ]);
            return ResponseFormatter::error($e->getMessage(), StatusCodeEnum::BAD_REQUEST)->toResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GR ASN update failed: ' . $e->getMessage(), [
                'transaction_no' => $request->transaction_no,
                'user_id' => Auth::user()->UserID ?? null,
                'payload' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function deleteTransaction(DeleteTransactionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $gr = $this->findAuthorizedGoodsReceiving($request->transaction_no);
            if (!$gr) {
                throw new ValidationException('Goods Receiving ASN does not exist or is not authorized.');
            }
            if ((int) ($gr->Editable ?? 1) === 0) {
                throw new ValidationException('This transaction cannot be deleted.');
            }

            $asn = $this->findAuthorizedAsn($gr->ASNNumber, true) ?? DB::table('Trans_AdvanceShippingNoticeHD')->where('TransactionNo', $gr->ASNNumber)->first();
            if (!$asn) {
                throw new ValidationException('ASN does not exist or is not authorized.');
            }

            $qcNumber = $gr->QCNumber;
            $this->reverseExistingRows($gr, $asn);

            TransJournalDT::where('TransactionNo', $gr->TransactionNo)->delete();
            TransJournalHD::where('TransactionNo', $gr->TransactionNo)->delete();
            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::connection('sqlsrv')->hasTable('Ms_FixedAsset')) {
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }
            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            $gr->delete();
            TransQualityControlReceivingDT::where('TransactionNo', $qcNumber)->delete();
            TransQualityControlReceivingHD::where('TransactionNo', $qcNumber)->delete();
            $this->recalculateOutstanding($asn);
            $this->markAsnReferenceNotEditable($asn->TransactionNo);

            DB::commit();

            return ResponseFormatter::success([
                'message' => 'Goods Receiving ASN successfully deleted!',
            ], 'Goods Receiving ASN successfully deleted!')->toResponse();
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

    private function findAuthorizedGoodsReceiving(string $transactionNo): ?TransGoodsReceivingHD
    {
        $query = TransGoodsReceivingHD::query()
            ->where('TransactionNo', $transactionNo)
            ->whereNotNull('ASNNumber')
            ->with(['details', 'qc']);

        WarehouseAccessCriteria::applyWithChildren($query);

        return $query->first();
    }

    private function findAuthorizedAsn(string $asnNo, bool $includeClosed = false)
    {
        $query = DB::table('Trans_AdvanceShippingNoticeHD')
            ->where('TransactionNo', $asnNo)
            ->when(!$includeClosed, fn ($q) => $q->where('Outstanding', 1));

        $this->applyAsnWarehouseAuthorization($query);

        return $query->first();
    }

    private function applyAsnWarehouseAuthorization($query): void
    {
        $allowedIds = WarehouseAccessCriteria::allowedIdsWithChildren();
        if ($allowedIds === null) {
            return;
        }

        if (empty($allowedIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('DestinationWarehouseID', $allowedIds);
    }

    private function receivingWarehouses(string $destinationWarehouseId): Collection
    {
        $query = MsWarehouse::query()
            ->where('Active', 1)
            ->where(function ($q) use ($destinationWarehouseId) {
                $q->where('WarehouseID', $destinationWarehouseId)
                    ->orWhere('ParentID', $destinationWarehouseId);
            })
            ->orderByRaw("CASE WHEN WarehouseID = ? THEN 0 ELSE 1 END", [$destinationWarehouseId])
            ->orderBy('WarehouseID');

        WarehouseAccessCriteria::applyWithChildren($query);

        return $query->get([
            'WarehouseID',
            'WarehouseName',
            'ParentID',
            'DivisionID',
            'Active',
            'StaffInChargeID',
        ]);
    }

    private function resolveReceivingWarehouseId($asn, string $warehouseId): string
    {
        $warehouseId = trim($warehouseId);
        $warehouse = MsWarehouse::query()
            ->where('WarehouseID', $warehouseId)
            ->where('Active', 1)
            ->first();

        if (!$warehouse) {
            throw new ValidationException('Selected receiving warehouse does not exist or is inactive.');
        }

        $isDestination = $warehouse->WarehouseID === $asn->DestinationWarehouseID;
        $isChild = $warehouse->ParentID === $asn->DestinationWarehouseID;
        if (!$isDestination && !$isChild) {
            throw new ValidationException('Selected receiving warehouse must be the ASN destination warehouse or its child warehouse.');
        }

        $allowedIds = WarehouseAccessCriteria::allowedIdsWithChildren();
        if ($allowedIds !== null && !in_array($warehouse->WarehouseID, $allowedIds, true)) {
            throw new ValidationException('Selected receiving warehouse is not authorized.');
        }

        return $warehouse->WarehouseID;
    }

    private function buildAsnDetailRows($asn, ?string $currentGrNo = null): Collection
    {
        $hasAsnCoilNo = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'CoilNo');
        $hasAsnQty2 = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'Qty2');
        $hasAsnUnitId2 = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'UnitID2');
        $hasAsnGrossWeight = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'GrossWeight');
        $hasAsnRateBeaMasuk = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaMasuk');
        $hasAsnRateBeaAccount = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaAccount');
        $hasAsnAntiDumping = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumping');
        $hasAsnAntiDumpingAccount = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumpingAccount');
        $select = [
            'dt.id as SourceDetailKey',
            'dt.PartID',
            'dt.Sequence',
            'dt.UnitID',
            'dt.BatchNo',
            'part.PartName',
            'part.WithSerialNo',
            DB::raw('SUM(dt.Qty) as Qty'),
        ];
        $groupBy = [
            'dt.id',
            'dt.PartID',
            'dt.Sequence',
            'dt.UnitID',
            'dt.BatchNo',
            'part.PartName',
            'part.WithSerialNo',
        ];
        if ($hasAsnCoilNo) {
            $select[] = 'dt.CoilNo';
            $groupBy[] = 'dt.CoilNo';
        } else {
            $select[] = DB::raw('NULL as CoilNo');
        }
        if ($hasAsnQty2) {
            $select[] = DB::raw('SUM(dt.Qty2) as Qty2');
        } else {
            $select[] = DB::raw('NULL as Qty2');
        }
        if ($hasAsnUnitId2) {
            $select[] = 'dt.UnitID2';
            $groupBy[] = 'dt.UnitID2';
        } else {
            $select[] = DB::raw('NULL as UnitID2');
        }
        if ($hasAsnGrossWeight) {
            $select[] = DB::raw('SUM(dt.GrossWeight) as GrossWeight');
        } else {
            $select[] = DB::raw('NULL as GrossWeight');
        }
        $select[] = $hasAsnRateBeaMasuk ? DB::raw('MAX(dt.RateBeaMasuk) as RateBeaMasuk') : DB::raw('NULL as RateBeaMasuk');
        if ($hasAsnRateBeaAccount) {
            $select[] = 'dt.RateBeaAccount';
            $groupBy[] = 'dt.RateBeaAccount';
        } else {
            $select[] = DB::raw('NULL as RateBeaAccount');
        }
        $select[] = $hasAsnAntiDumping ? DB::raw('MAX(dt.AntiDumping) as AntiDumping') : DB::raw('NULL as AntiDumping');
        if ($hasAsnAntiDumpingAccount) {
            $select[] = 'dt.AntiDumpingAccount';
            $groupBy[] = 'dt.AntiDumpingAccount';
        } else {
            $select[] = DB::raw('NULL as AntiDumpingAccount');
        }

        $asnDetails = DB::table('Trans_AdvanceShippingNoticeDT as dt')
            ->join('Ms_Part as part', 'part.PartID', '=', 'dt.PartID')
            ->where('dt.TransactionNo', $asn->TransactionNo)
            ->select($select)
            ->groupBy($groupBy)
            ->get();

        $asnDetailColumn = $this->getGoodsReceivingAsnDetailColumn();
        $receivedRows = $this->receivedQtyByAsnDetail($asn->TransactionNo, $currentGrNo, $asnDetailColumn);
        $existingRows = collect();
        if ($currentGrNo) {
            $existingRows = TransGoodsReceivingDT::where('TransactionNo', $currentGrNo)->get();
            if ($asnDetailColumn) {
                $existingRows = $existingRows->keyBy(fn ($row) => (string) $row->{$asnDetailColumn});
            } else {
                $existingRows = $existingRows->keyBy(fn ($row) => $this->stockLineKey($row->PartID, $row->Sequence, $row->BatchNo));
            }
        }

        $coilLookupRows = $asnDetails->map(function ($detail) use ($existingRows, $asnDetailColumn) {
            $sourceKey = !empty($detail->SourceDetailKey) ? (string) $detail->SourceDetailKey : $this->stockLineKey($detail->PartID, $detail->Sequence, $detail->BatchNo);
            $existingDetail = $existingRows[$asnDetailColumn ? $sourceKey : $this->stockLineKey($detail->PartID, $detail->Sequence, $detail->BatchNo)] ?? null;
            return [
                'part_id' => $detail->PartID,
                'batch_no' => $existingDetail ? $existingDetail->BatchNo : $detail->BatchNo,
            ];
        });
        $coilMap = CoilNoHelper::lookupByPartBatch($coilLookupRows);

        return $asnDetails->map(function ($detail) use ($asn, $receivedRows, $existingRows, $asnDetailColumn, $coilMap) {
            $fallbackKey = $this->stockLineKey($detail->PartID, $detail->Sequence, $detail->BatchNo);
            $sourceDetailKey = !empty($detail->SourceDetailKey) ? (string) $detail->SourceDetailKey : $fallbackKey;
            $receivedKey = $asnDetailColumn ? $sourceDetailKey : $fallbackKey;
            $receivedQty = (float) ($receivedRows[$receivedKey]->Qty ?? 0);
            $qtyRemaining = (float) $detail->Qty - $receivedQty;
            $existingDetail = $existingRows[$asnDetailColumn ? $sourceDetailKey : $fallbackKey] ?? null;

            if ($qtyRemaining <= 0 && !$existingDetail) {
                return null;
            }

            $poDetail = TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->first();

            $unit = $poDetail->UnitID ?? $detail->UnitID;
            $unit2 = MsPartUnit::where('PartID', $detail->PartID)
                ->where('UnitID2', $unit)
                ->first();

            $qty2 = $detail->Qty2 !== null ? (float) $detail->Qty2 : null;
            $unitId2 = $detail->UnitID2 ?? null;
            $batchNo = $existingDetail ? $existingDetail->BatchNo : $detail->BatchNo;
            $coilNo = $detail->CoilNo ?? null;

            if ($qty2 === null && $batchNo !== null && trim((string) $batchNo) !== '') {
                $secondary = DualQuantityHelper::currentStockSecondaryQuantity($detail->PartID, $asn->DestinationWarehouseID ?? $asn->WarehouseID, $batchNo, null, null, null, null, false);
                $currentQty1 = abs((float) ($secondary['qty1'] ?? 0));
                $currentQty2 = abs((float) ($secondary['qty2'] ?? 0));
                if ($currentQty1 > 0.000001 && $currentQty2 > 0.000001) {
                    $qty2 = (float) $detail->Qty / $currentQty1 * $currentQty2;
                }
                if ($unitId2 === null) {
                    $unitId2 = $secondary['unit_id2'];
                }
            }

            if ($coilNo === null && $batchNo !== null && trim((string) $batchNo) !== '') {
                $coilNo = $coilMap->get(CoilNoHelper::key($detail->PartID, $batchNo))
                    ?? $coilMap->get(CoilNoHelper::batchKey($batchNo));
            }

            return [
                'source_detail_key' => $sourceDetailKey,
                'asn_detail_id' => $sourceDetailKey,
                'part_id' => $detail->PartID,
                'part_name' => $detail->PartName,
                'with_serial_no' => (int) ($detail->WithSerialNo ?? 0),
                'sequence' => (int) $detail->Sequence,
                'unit_id' => $unit,
                'unit_id_base' => $unit2->UnitID1 ?? $unit,
                'qty_asn' => (float) $detail->Qty,
                'qty2_asn' => $qty2,
                'unit_id2' => $unitId2,
                'gross_weight' => $detail->GrossWeight !== null ? (float) $detail->GrossWeight : null,
                'RateBeaMasuk' => $detail->RateBeaMasuk ?? $poDetail->RateBeaMasuk ?? 0,
                'RateBeaAccount' => $detail->RateBeaAccount ?? $poDetail->RateBeaAccount ?? null,
                'AntiDumping' => $detail->AntiDumping ?? $poDetail->AntiDumping ?? 0,
                'AntiDumpingAccount' => $detail->AntiDumpingAccount ?? $poDetail->AntiDumpingAccount ?? null,
                'weight_per_piece' => DualQuantityHelper::weightPerPiece((float) $detail->Qty, $qty2),
                'qty_remaining' => $existingDetail ? $qtyRemaining + (float) $existingDetail->Qty : $qtyRemaining,
                'qty_receive' => $existingDetail ? (float) $existingDetail->Qty : 0,
                'batch_no' => $batchNo,
                'coil_no' => $coilNo,
                'rev' => $this->revisionValues((int) ($asn->RevCount ?? 0), $existingDetail ?: $detail),
            ];
        })->filter()->values();
    }

    private function asnDetailInfoById(?string $asnNo, Collection $detailIds): Collection
    {
        $detailIds = $detailIds->filter(fn ($id) => $id !== null && $id !== '')->unique()->values();

        if (!$asnNo || $detailIds->isEmpty()) {
            return collect();
        }

        $select = ['id'];
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'CoilNo')
            ? 'CoilNo'
            : DB::raw('NULL as CoilNo');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'Qty2')
            ? 'Qty2'
            : DB::raw('NULL as Qty2');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'UnitID2')
            ? 'UnitID2'
            : DB::raw('NULL as UnitID2');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'GrossWeight')
            ? 'GrossWeight'
            : DB::raw('NULL as GrossWeight');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaMasuk')
            ? 'RateBeaMasuk'
            : DB::raw('NULL as RateBeaMasuk');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaAccount')
            ? 'RateBeaAccount'
            : DB::raw('NULL as RateBeaAccount');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumping')
            ? 'AntiDumping'
            : DB::raw('NULL as AntiDumping');
        $select[] = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumpingAccount')
            ? 'AntiDumpingAccount'
            : DB::raw('NULL as AntiDumpingAccount');

        return DB::table('Trans_AdvanceShippingNoticeDT')
            ->where('TransactionNo', $asnNo)
            ->whereIn('id', $detailIds->all())
            ->get($select)
            ->keyBy(fn ($row) => (string) $row->id);
    }

    private function validateDetails($asn, array $submittedDetails, ?string $currentGrNo = null, ?string $transactionDate = null): array
    {
        $asnDetails = $this->buildAsnDetailRows($asn, $currentGrNo)->keyBy('source_detail_key');
        $receivedByDetail = [];
        $stockTotals = [];
        $validated = [];
        $formattedTransactionDate = $transactionDate ? Carbon::parse($transactionDate)->format('Y-m-d') : null;

        foreach ($submittedDetails as $detail) {
            $sourceDetailKey = (string) ($detail['source_detail_key'] ?? '');
            $asnDetail = $asnDetails[$sourceDetailKey]
                ?? $asnDetails->firstWhere(fn ($a) => (string) $a['part_id'] === (string) $detail['part_id'] && (int) $a['sequence'] === (int) $detail['sequence'])
                ?? null;

            if (!$asnDetail) {
                throw new ValidationException('ASN detail does not exist or has no remaining qty.');
            }

            $sourceDetailKey = (string) $asnDetail['source_detail_key'];
            $qtyReceive = (float) $detail['qty_receive'];
            $receivedByDetail[$sourceDetailKey] = ($receivedByDetail[$sourceDetailKey] ?? 0) + $qtyReceive;
            if ($receivedByDetail[$sourceDetailKey] > ((float) $asnDetail['qty_remaining'] + 0.000001)) {
                throw new ValidationException('Total receive amount must not exceed Remaining amount.');
            }

            if ($detail['part_id'] !== $asnDetail['part_id'] || (int) $detail['sequence'] !== (int) $asnDetail['sequence']) {
                throw new ValidationException('Submitted detail does not match ASN detail.');
            }

            $batchNo = $this->nullableStockValue($detail['batch_no'] ?? null);
            $stockKey = strtolower(trim((string) $detail['part_id'])) . '|' . strtolower(trim((string) $batchNo));
            $stockTotals[$stockKey] = $stockTotals[$stockKey] ?? [
                'part_id' => $detail['part_id'],
                'batch_no' => $batchNo,
                'qty' => 0.0,
            ];
            $stockTotals[$stockKey]['qty'] += $qtyReceive;

            $validated[] = [
                'source_detail_key' => $sourceDetailKey,
                'part_id' => $detail['part_id'],
                'sequence' => (int) $detail['sequence'],
                'unit_id' => $asnDetail['unit_id_base'],
                'qty_receive' => $qtyReceive,
                'qty2' => $asnDetail['qty2_asn'] !== null && (float) $asnDetail['qty_asn'] > 0
                    ? $qtyReceive / (float) $asnDetail['qty_asn'] * (float) $asnDetail['qty2_asn']
                    : null,
                'unit_id2' => $asnDetail['unit_id2'],
                'coil_no' => $asnDetail['coil_no'] ?? ($batchNo ? CoilNoHelper::get($detail['part_id'], $batchNo) : null),
                'batch_no' => $batchNo,
                'RateBeaMasuk' => $asnDetail['RateBeaMasuk'] ?? 0,
                'RateBeaAccount' => $asnDetail['RateBeaAccount'] ?? null,
                'AntiDumping' => $asnDetail['AntiDumping'] ?? 0,
                'AntiDumpingAccount' => $asnDetail['AntiDumpingAccount'] ?? null,
                'rev' => $detail['rev'] ?? [],
            ];
        }

        if (empty($validated)) {
            throw new ValidationException('You need to receive at least one item.');
        }

        foreach ($stockTotals as $stockTotal) {
            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $stockTotal['part_id'],
                $asn->WarehouseID,
                $stockTotal['batch_no'],
                $formattedTransactionDate
            );
            if ($availableStock < $stockTotal['qty']) {
                throw new ValidationException($this->stockNotAvailableMessage($stockTotal['part_id'], $asn->WarehouseID, $stockTotal['batch_no']));
            }
        }

        return $validated;
    }

    private function createQualityControl(string $transactionDate, $asn, array $details, string $receivingWarehouseId): string
    {
        $qcNumber = generateRandomString(50);

        TransQualityControlReceivingHD::create([
            'TransactionNo' => $qcNumber,
            'TransactionDate' => Carbon::parse($transactionDate)->format('Y-m-d'),
            'QCNo' => $qcNumber,
            'PONumber' => $asn->PONumber,
            'WarehouseID' => $receivingWarehouseId,
            'CreatedBy' => Auth::user()->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
            'LastUpdateBy' => Auth::user()->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
        ]);

        $this->insertInChunks(TransQualityControlReceivingDT::class, $this->buildQcRows($qcNumber, $details));

        return $qcNumber;
    }

    private function buildQcRows(string $qcNumber, array $details): array
    {
        return array_map(fn ($detail) => [
            'TransactionNo' => $qcNumber,
            'PartID' => $detail['part_id'],
            'Sequence' => $detail['sequence'],
            'UnitID' => $detail['unit_id'],
            'Qty' => $detail['qty_receive'],
            'Passed' => 1,
            'BatchNo' => $detail['batch_no'],
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ], $details);
    }

    private function buildReceivingRows(string $transactionDate, string $transactionNo, $asn, array $details, string $receivingWarehouseId): array
    {
        $grRows = [];
        $stockRows = [];
        $transactionDate = Carbon::parse($transactionDate)->format('Y-m-d');
        $asnDetailColumn = $this->getGoodsReceivingAsnDetailColumn();

        foreach ($details as $detail) {
            $poDetail = TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                ->where('PartID', $detail['part_id'])
                ->where('Sequence', $detail['sequence'])
                ->first();

            if (!$poDetail) {
                throw new ValidationException('Purchase Order detail does not exist.');
            }

            TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                ->where('PartID', $detail['part_id'])
                ->where('Sequence', $detail['sequence'])
                ->update([
                    'QtyReceived' => (float) $poDetail->QtyReceived + (float) $detail['qty_receive'],
                ]);

            $grRow = [
                'TransactionNo' => $transactionNo,
                'PartID' => $detail['part_id'],
                'Sequence' => $detail['sequence'],
                'UnitID' => $detail['unit_id'],
                'Qty' => $detail['qty_receive'],
                'BatchNo' => $detail['batch_no'],
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
            ];
            if ($asnDetailColumn) {
                $grRow[$asnDetailColumn] = $detail['source_detail_key'];
            }
            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'CoilNo')) {
                $grRow['CoilNo'] = $detail['coil_no'] ?? null;
            }
            $grRow = $this->applyDetailDutyFields($grRow, 'Trans_GoodsReceivingDT', $detail);

            for ($i = 1; $i <= (int) ($asn->RevCount ?? 0); $i++) {
                $grRow['ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT)] = $detail['rev'][$i - 1] ?? null;
            }

            $grRows[] = $grRow;

            $this->autoCreateFixedAssetsForReceipt(
                $detail['part_id'],
                (float) $detail['qty_receive'],
                $poDetail->DivisionID ?? null,
                $transactionDate,
                $asn->CurrencyID,
                (float) ($asn->Rate ?? 1),
                (float) ($poDetail->UnitPrice ?? 0),
                $transactionNo,
                $detail['batch_no'] ?? null
            );

            $dualUnit = [
                'Qty2' => $detail['qty2'] ?? null,
                'UnitID2' => $detail['unit_id2'] ?? null,
            ];
            $stockRows[] = $this->stockRow($transactionNo, $transactionDate, $detail, $receivingWarehouseId, $detail['qty_receive'], self::GR_TRANSACTION_TYPE, $dualUnit);
            $stockRows[] = $this->stockRow($transactionNo, $transactionDate, $detail, $asn->WarehouseID, -1 * (float) $detail['qty_receive'], self::ASN_GR_TRANSACTION_TYPE, $dualUnit);
        }

        return [$grRows, $stockRows];
    }

    private function reverseExistingRows(TransGoodsReceivingHD $gr, $asn): void
    {
        $checkDate = max(Carbon::today()->format('Y-m-d'), Carbon::parse($gr->TransactionDate)->format('Y-m-d'));
        foreach ($gr->details as $detail) {
            $availableStock = BukuStockHelper::calculateCurrentStockByBatchNo(
                $detail->PartID,
                $gr->WarehouseID,
                $detail->BatchNo,
                $checkDate
            );

            $hasOwnStock = BukuStock::where('TransactionNo', $gr->TransactionNo)
                ->where('PartID', $detail->PartID)
                ->where('WarehouseID', $gr->WarehouseID)
                ->exists();

            if (!$hasOwnStock && ($availableStock - (float) $detail->Qty) < -0.000001) {
                throw new ValidationException($this->stockNotAvailableMessage($detail->PartID, $gr->WarehouseID, $detail->BatchNo));
            }

            $poDetail = TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->first();

            if ($poDetail) {
                TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->update([
                        'QtyReceived' => max((float) $poDetail->QtyReceived - (float) $detail->Qty, 0),
                    ]);
            }
        }
    }

    private function stockRow(string $transactionNo, string $transactionDate, array $detail, string $warehouseId, float $qty, string $transactionType, array $dualUnit = []): array
    {
        return [
            'TransactionNo' => $transactionNo,
            'TransactionDate' => $transactionDate,
            'PartID' => $detail['part_id'],
            'WarehouseID' => $warehouseId,
            'Sequence' => $detail['sequence'],
            'UnitID' => $detail['unit_id'],
            'Qty' => $qty,
            'Qty2' => $dualUnit['Qty2'] !== null
                ? ($qty < 0 ? -1 : 1) * abs((float) $dualUnit['Qty2'])
                : null,
            'UnitID2' => $dualUnit['UnitID2'] ?? null,
            'BatchNo' => $detail['batch_no'],
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
            'TransactionType' => $transactionType,
            'CreatedBy' => Auth::user()->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
        ];
    }

    private function receivedQtyByAsnDetail(string $asnNo, ?string $currentGrNo, ?string $asnDetailColumn): Collection
    {
        if (!$asnDetailColumn) {
            return DB::table('Trans_GoodsReceivingDT as grdt')
                ->join('Trans_GoodsReceivingHD as grhd', 'grhd.TransactionNo', '=', 'grdt.TransactionNo')
                ->where('grhd.ASNNumber', $asnNo)
                ->when($currentGrNo, fn ($query) => $query->where('grdt.TransactionNo', '<>', $currentGrNo))
                ->select('grdt.PartID', 'grdt.Sequence', 'grdt.BatchNo', DB::raw('SUM(grdt.Qty) as Qty'))
                ->groupBy('grdt.PartID', 'grdt.Sequence', 'grdt.BatchNo')
                ->get()
                ->keyBy(fn ($row) => $this->stockLineKey($row->PartID, $row->Sequence, $row->BatchNo));
        }

        return DB::table('Trans_GoodsReceivingDT as grdt')
            ->join('Trans_GoodsReceivingHD as grhd', 'grhd.TransactionNo', '=', 'grdt.TransactionNo')
            ->where('grhd.ASNNumber', $asnNo)
            ->when($currentGrNo, fn ($query) => $query->where('grdt.TransactionNo', '<>', $currentGrNo))
            ->whereNotNull("grdt.{$asnDetailColumn}")
            ->selectRaw("grdt.{$asnDetailColumn} as SourceDetailKey, SUM(grdt.Qty) as Qty")
            ->groupBy("grdt.{$asnDetailColumn}")
            ->get()
            ->keyBy(fn ($row) => (string) $row->SourceDetailKey);
    }

    private function stockLineKey(string $partId, int|string $sequence, ?string $batchNo): string
    {
        return strtolower(trim((string) $partId)) . '|'
            . $sequence . '|'
            . strtolower(trim((string) $batchNo));
    }

    private function resolveTransactionNo(string $transactionDate, ?string $manualTransactionNo, bool $isAuto): array
    {
        if (!$isAuto) {
            if (!$manualTransactionNo) {
                throw new ValidationException('Transaction No is required when automatic is disabled.');
            }

            return [
                'transaction_no' => trim($manualTransactionNo),
                'last_digit' => null,
            ];
        }

        $masterAuto = MsAutoNumber::find('1');
        if (!$masterAuto || empty($masterAuto->Purchase11)) {
            throw new ValidationException('Goods Receiving auto number prefix is not configured.');
        }

        $date = Carbon::parse($transactionDate);
        $last = TransGoodsReceivingHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', $date->month)
            ->whereYear('TransactionDate', $date->year)
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $last ? ((int) $last->LastDigit + 1) : 1;

        do {
            $id = $masterAuto->Purchase11 . '/' . $date->format('Y')
                . '/' . $date->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);
            $exists = TransGoodsReceivingHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        return [
            'transaction_no' => $id,
            'last_digit' => $digit,
        ];
    }

    private function findPo(string $poNo): TransPurchaseOrderHD
    {
        $po = TransPurchaseOrderHD::where('TransactionNo', $poNo)->first();
        if (!$po) {
            throw new ValidationException('Purchase Order does not exist.');
        }

        return $po;
    }

    private function validateTransactionDate(string $transactionDate, TransPurchaseOrderHD $po): void
    {
        if (Carbon::parse($transactionDate)->lt(Carbon::parse($po->TransactionDate))) {
            throw new ValidationException("Transaction Date must not be earlier than Purchase Order's date.");
        }
    }

    private function recalculateOutstanding($asn): void
    {
        PurchaseOrderStateService::recalculate($asn->PONumber, Auth::user()->UserID);
        $this->checkOutstandingAsn($asn->TransactionNo);
    }

    /**
     * A part with PartType 'F' (Fixed Asset) gets one Ms_FixedAsset row per unit received,
     * instead of the usual stock movement. Every field used here already exists on Ms_FixedAsset
     * (PartID, ReceivingNumber, BatchNo, etc. - no schema change needed). Ms_FixedAsset's
     * CategoryID comes from the Part's Inventory Type (Ms_InventoryType, managed at
     * /inventory/type) - specifically that Inventory Type's own Notes field, which the user sets
     * to match an existing Ms_FixedAssetCategory.CategoryID. Every asset lands in the fixed
     * LocationID 'JAKARTA'. FixedAssetCode is PartID plus a running per-part sequence (e.g.
     * FA00001-0001), since there's no dedicated auto-number slot for it. Mirrors the web
     * GoodsReceivingAsnController's autoCreateFixedAssetsForReceipt() and kawaguci-nla's
     * GoodsReceivingController::autoCreateFixedAssetsForReceipt().
     */
    private function autoCreateFixedAssetsForReceipt(
        string $partId,
        float $qty,
        ?string $divisionId,
        string $transactionDateYmd,
        string $currencyId,
        float $rate,
        float $unitCost,
        string $grTransactionNo,
        ?string $batchNo
    ): void {
        if (!Schema::connection('sqlsrv')->hasTable('Ms_FixedAsset')) {
            return;
        }

        $part = MsPart::find($partId);
        if (!$part || strtoupper(trim((string) $part->PartType)) !== 'F') {
            return;
        }

        $unitsToCreate = (int) round($qty);
        if ($unitsToCreate <= 0) {
            return;
        }

        $inventoryType = MsInventoryType::find($part->InventoryTypeID);
        $categoryId = trim((string) ($inventoryType->Notes ?? ''));
        if ($categoryId === '') {
            throw new ValidationException("Part {$partId}'s Inventory Type ({$part->InventoryTypeID}) has no Fixed Asset Category set in its Notes.");
        }
        if (!MsFixedAssetCategory::find($categoryId)) {
            throw new ValidationException("Inventory Type {$part->InventoryTypeID}'s Notes ('{$categoryId}') does not match any existing Fixed Asset Category ID.");
        }

        $nextNumber = MsFixedAsset::where('PartID', $partId)->count();
        $timestamp = date('Y-m-d H:i:s');

        for ($i = 0; $i < $unitsToCreate; $i++) {
            $nextNumber++;
            $code = $partId . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            MsFixedAsset::create([
                'FixedAssetCode' => $code,
                'FixedAssetName' => $part->PartName,
                'ProcurementDate' => $transactionDateYmd,
                'DivisionID' => $divisionId,
                'OriginalLocationID' => 'JAKARTA',
                'CurrentLocationID' => 'JAKARTA',
                'CategoryID' => $categoryId,
                'EffectiveDate' => $transactionDateYmd,
                'OriginalCostValue' => $unitCost,
                'OriginalCurrency' => $currencyId,
                'OriginalRate' => $rate,
                'MinimumResidualPercentage' => 0,
                'Status' => 'ACTIVE',
                'Editable' => 1,
                'PartID' => $partId,
                'ReceivingNumber' => $grTransactionNo,
                'BatchNo' => $batchNo !== '' ? $batchNo : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => $timestamp,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => $timestamp,
            ]);
        }
    }

    private function markAsnReferenceNotEditable(string $asnNo): void
    {
        DB::table('Trans_AdvanceShippingNoticeHD')
            ->where('TransactionNo', $asnNo)
            ->update([
                'Editable' => 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
    }

    private function checkOutstandingAsn(string $asnNo): void
    {
        $asnQty = DB::table('Trans_AdvanceShippingNoticeDT')
            ->where('TransactionNo', $asnNo)
            ->select('PartID', 'Sequence', DB::raw('SUM(Qty) as Qty'))
            ->groupBy('PartID', 'Sequence')
            ->get()
            ->keyBy(fn ($row) => strtolower(trim((string) $row->PartID)) . '|' . $row->Sequence);

        $receivedQty = DB::table('Trans_GoodsReceivingDT as grdt')
            ->join('Trans_GoodsReceivingHD as grhd', 'grhd.TransactionNo', '=', 'grdt.TransactionNo')
            ->where('grhd.ASNNumber', $asnNo)
            ->select('grdt.PartID', 'grdt.Sequence', DB::raw('SUM(grdt.Qty) as Qty'))
            ->groupBy('grdt.PartID', 'grdt.Sequence')
            ->get()
            ->keyBy(fn ($row) => strtolower(trim((string) $row->PartID)) . '|' . $row->Sequence);

        $outstanding = false;
        foreach ($asnQty as $key => $row) {
            if ((float) ($receivedQty[$key]->Qty ?? 0) < (float) $row->Qty) {
                $outstanding = true;
                break;
            }
        }

        DB::table('Trans_AdvanceShippingNoticeHD')
            ->where('TransactionNo', $asnNo)
            ->update([
                'Outstanding' => $outstanding ? 1 : 0,
                'Editable' => $outstanding ? 1 : 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
    }

    private function getGoodsReceivingAsnDetailColumn(): ?string
    {
        static $column = false;

        if ($column !== false) {
            return $column;
        }

        foreach (['ASNDetailID', 'SourceDetailKey', 'ASNDTID', 'AdvanceShippingNoticeDTID'] as $candidate) {
            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', $candidate)) {
                return $column = $candidate;
            }
        }

        return $column = null;
    }

    private function revisionValues(int $revCount, $detail): array
    {
        $values = [];
        for ($i = 1; $i <= $revCount; $i++) {
            $values[] = $detail->{'ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '';
        }

        return $values;
    }

    private function nullableStockValue($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);
        if ($trimmed === '' || $trimmed === '__NULL__') {
            return null;
        }

        return $trimmed;
    }

    private function stockNotAvailableMessage(string $partId, string $warehouseId, ?string $batchNo): string
    {
        return 'Stock is not available for selected stock details. Part: '
            . $partId . ', Warehouse: ' . $warehouseId . ', Batch No: '
            . ($batchNo === null || $batchNo === '' ? '(Empty)' : $batchNo) . '.';
    }

    private function rebuildJournal(string $transactionNo): void
    {
        GoodsReceivingJournalService::rebuild($transactionNo);
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            if (count($chunk) > 0) {
                $modelClass::insert($chunk);
            }
        }
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
