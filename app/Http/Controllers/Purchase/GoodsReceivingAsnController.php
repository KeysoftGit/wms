<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsFixedAsset;
use App\Models\MsFixedAssetCategory;
use App\Models\MsInventoryType;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\MsRevAlias;
use App\Models\TransGoodsReceivingDT;
use App\Models\TransGoodsReceivingHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPurchaseOrderDT;
use App\Models\TransPurchaseOrderHD;
use App\Models\TransQualityControlReceivingDT;
use App\Models\TransQualityControlReceivingHD;
use App\Services\GeneralService;
use App\Services\GoodsReceivingJournalService;
use App\Services\PurchaseOrderStateService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class GoodsReceivingAsnController extends Controller
{
    private GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            $modelClass::insert($chunk);
        }
    }

    private function applyDetailDutyFields(array $target, string $table, $source): array
    {
        if (Schema::connection('sqlsrv')->hasColumn($table, 'RateBeaMasuk')) {
            $target['RateBeaMasuk'] = (float) ($source->RateBeaMasuk ?? 0);
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'RateBeaAccount')) {
            $target['RateBeaAccount'] = trim((string) ($source->RateBeaAccount ?? '')) ?: null;
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'AntiDumping')) {
            $target['AntiDumping'] = (float) ($source->AntiDumping ?? 0);
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, 'AntiDumpingAccount')) {
            $target['AntiDumpingAccount'] = trim((string) ($source->AntiDumpingAccount ?? '')) ?: null;
        }

        return $target;
    }

    public function index()
    {
        $this->guardAdvancedAccess();

        return view('purchase.gr.index');
    }

    public function datatable(Request $request)
    {
        $this->guardAdvancedAccess();

        $data = TransGoodsReceivingHD::select('id', 'TransactionNo', 'TransactionDate', 'WarehouseID', 'Rate', 'Notes', 'Editable', 'ASNNumber', 'updated_at', 'LastUpdate');
        WarehouseAccessCriteria::apply($data);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        if ($request->get('outstanding')) {
            $data->where('Outstanding', 1);
        }

        return DataTables::of($data)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('gr.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'gr.edit'])) {
                        $editRoute = $row->ASNNumber ? route('gr.asn.edit', $row->id) : route('gr.edit', $row->id);
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . $editRoute . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'gr.delete'])) {
                        $deleteRoute = $row->ASNNumber ? route('gr.asn.delete', $row->id) : route('gr.delete', $row->id);
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . $deleteRoute . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        $this->guardAdvancedAccess();

        $revData = $this->getRev();
        $useAsn = true;

        return view('purchase.gr_asn.add', compact('revData', 'useAsn'));
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $gr = TransGoodsReceivingHD::where('id', $id)->first();
        $revData = $this->getRev();
        $options = DocPrint::where('ModuleCode', 'GR')
            ->where('TypeStr', 'print')
            ->get();

        return view('purchase.gr_asn.show', compact('gr', 'options', 'revData'));
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $gr = TransGoodsReceivingHD::where('id', $id)->first();
        if (!$gr || !$gr->ASNNumber) {
            return redirect()->route('gr.edit', $id);
        }

        $revData = $this->getRev();
        $useAsn = true;

        return view('purchase.gr_asn.edit', compact('gr', 'revData', 'useAsn'));
    }

    public function loadASN(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $transactions = DB::table('Trans_AdvanceShippingNoticeHD')
                ->where(function ($query) use ($request) {
                    $query->where('Outstanding', 1);
                    if ($request->filled('inv')) {
                        $query->orWhere('TransactionNo', $request->input('inv'));
                    }
                });

            if ($request->filled('warehouse_id')) {
                $transactions->where('DestinationWarehouseID', $request->input('warehouse_id'));
            }

            if ($request->filled('search')) {
                $transactions->where('TransactionNo', 'like', '%' . $request->input('search') . '%');
            }

            $data = [];
            foreach ($transactions->orderByDesc('TransactionDate')->limit(50)->get() as $transaction) {
                $data[] = [
                    'id' => $transaction->TransactionNo,
                    'text' => $transaction->TransactionNo . ' - PO ' . $transaction->PONumber,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response(['status' => 'error']);
        }
    }

    public function getASNDetail(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $asn = DB::table('Trans_AdvanceShippingNoticeHD')
                ->where('TransactionNo', $request->input('id'))
                ->first();

            if (!$asn) {
                throw new \Exception('ASN does not exist.');
            }

            $hasAsnCoilNo = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'CoilNo');
            $hasGrCoilNo = Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'CoilNo');
            $hasAsnBin = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'BIN');
            $hasAsnLoc = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'LOC');
            $hasAsnQty2 = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'Qty2');
            $hasAsnUnitID2 = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'UnitID2');
            $hasAsnRateBeaMasuk = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaMasuk');
            $hasAsnRateBeaAccount = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'RateBeaAccount');
            $hasAsnAntiDumping = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumping');
            $hasAsnAntiDumpingAccount = Schema::connection('sqlsrv')->hasColumn('Trans_AdvanceShippingNoticeDT', 'AntiDumpingAccount');
            $grAsnDetailColumn = $this->getGoodsReceivingAsnDetailColumn();

            $lineKey = function ($partId, $sequence, $serialNo, $batchNo, $coilNo, $expDate) {
                return strtolower(trim((string) $partId))
                    . '|' . $sequence
                    . '|' . strtolower(trim((string) $serialNo))
                    . '|' . strtolower(trim((string) $batchNo))
                    . '|' . strtolower(trim((string) $coilNo))
                    . '|' . ($expDate ? Carbon::parse($expDate)->format('Y-m-d') : '');
            };
            $asnDetailKey = fn ($sourceDetailKey) => 'asn|' . $sourceDetailKey;

            $asnSelect = [
                'dt.id as SourceDetailKey',
                'dt.PartID',
                'dt.Sequence',
                'dt.UnitID',
                $hasAsnUnitID2 ? 'dt.UnitID2' : DB::raw('NULL as UnitID2'),
                'dt.SerialNo',
                'dt.BatchNo',
                'dt.ExpDate',
                'part.PartName',
                'part.WithSerialNo',
                DB::raw('SUM(dt.Qty) as Qty'),
                $hasAsnQty2 ? DB::raw('SUM(dt.Qty2) as Qty2') : DB::raw('SUM(dt.Qty) as Qty2'),
            ];
            $asnSelect[] = $hasAsnCoilNo ? 'dt.CoilNo' : DB::raw('NULL as CoilNo');
            for ($i = 1; $i <= 15; $i++) {
                $asnSelect[] = 'dt.ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT);
            }
            $asnSelect[] = $hasAsnBin ? 'dt.BIN' : DB::raw('NULL as BIN');
            $asnSelect[] = $hasAsnLoc ? 'dt.LOC' : DB::raw('NULL as LOC');
            $asnSelect[] = $hasAsnRateBeaMasuk ? DB::raw('MAX(dt.RateBeaMasuk) as RateBeaMasuk') : DB::raw('NULL as RateBeaMasuk');
            $asnSelect[] = $hasAsnRateBeaAccount ? 'dt.RateBeaAccount' : DB::raw('NULL as RateBeaAccount');
            $asnSelect[] = $hasAsnAntiDumping ? DB::raw('MAX(dt.AntiDumping) as AntiDumping') : DB::raw('NULL as AntiDumping');
            $asnSelect[] = $hasAsnAntiDumpingAccount ? 'dt.AntiDumpingAccount' : DB::raw('NULL as AntiDumpingAccount');

            $asnGroup = [
                'dt.id',
                'dt.PartID',
                'dt.Sequence',
                'dt.UnitID',
                'dt.SerialNo',
                'dt.BatchNo',
                'dt.ExpDate',
                'part.PartName',
                'part.WithSerialNo',
            ];
            if ($hasAsnUnitID2) {
                $asnGroup[] = 'dt.UnitID2';
            }
            if ($hasAsnCoilNo) {
                $asnGroup[] = 'dt.CoilNo';
            }
            for ($i = 1; $i <= 15; $i++) {
                $asnGroup[] = 'dt.ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT);
            }
            if ($hasAsnBin) {
                $asnGroup[] = 'dt.BIN';
            }
            if ($hasAsnLoc) {
                $asnGroup[] = 'dt.LOC';
            }
            if ($hasAsnRateBeaAccount) {
                $asnGroup[] = 'dt.RateBeaAccount';
            }
            if ($hasAsnAntiDumpingAccount) {
                $asnGroup[] = 'dt.AntiDumpingAccount';
            }

            $details = DB::table('Trans_AdvanceShippingNoticeDT as dt')
                ->join('Ms_Part as part', 'part.PartID', '=', 'dt.PartID')
                ->where('dt.TransactionNo', $asn->TransactionNo)
                ->select($asnSelect)
                ->groupBy($asnGroup)
                ->get();

            $receivedSelect = [
                'grdt.PartID',
                'grdt.Sequence',
                'grdt.SerialNo',
                'grdt.BatchNo',
                'grdt.ExpDate',
                DB::raw('SUM(grdt.Qty) as Qty'),
            ];
            $receivedSelect[] = $hasGrCoilNo ? 'grdt.CoilNo' : DB::raw('NULL as CoilNo');
            if ($grAsnDetailColumn) {
                $receivedSelect[] = "grdt.{$grAsnDetailColumn} as SourceDetailKey";
            }

            $receivedGroup = [
                'grdt.PartID',
                'grdt.Sequence',
                'grdt.SerialNo',
                'grdt.BatchNo',
                'grdt.ExpDate',
            ];
            if ($hasGrCoilNo) {
                $receivedGroup[] = 'grdt.CoilNo';
            }
            if ($grAsnDetailColumn) {
                $receivedGroup[] = "grdt.{$grAsnDetailColumn}";
            }

            $receivedRows = DB::table('Trans_GoodsReceivingDT as grdt')
                ->join('Trans_GoodsReceivingHD as grhd', 'grhd.TransactionNo', '=', 'grdt.TransactionNo')
                ->where('grhd.ASNNumber', $asn->TransactionNo)
                ->when($request->input('grID'), fn ($q) => $q->where('grdt.TransactionNo', '<>', $request->input('grID')))
                ->select($receivedSelect)
                ->groupBy($receivedGroup)
                ->get();
            $receivedQtyByAsnDetail = $grAsnDetailColumn
                ? $receivedRows->filter(fn ($row) => !empty($row->SourceDetailKey))->keyBy(fn ($row) => $asnDetailKey($row->SourceDetailKey))
                : collect();
            $receivedQtyByAttributes = $receivedRows->keyBy(fn ($row) => $lineKey($row->PartID, $row->Sequence, $row->SerialNo, $row->BatchNo, $row->CoilNo ?? null, $row->ExpDate));

            $existingDetailsByAsnDetail = collect();
            $existingDetailsByAttributes = collect();
            if ($request->input('grID')) {
                $existingRows = TransGoodsReceivingDT::where('TransactionNo', $request->input('grID'))->get();
                $existingDetailsByAsnDetail = $grAsnDetailColumn
                    ? $existingRows->filter(fn ($row) => !empty($row->{$grAsnDetailColumn}))->keyBy(fn ($row) => $asnDetailKey($row->{$grAsnDetailColumn}))
                    : collect();
                $existingDetailsByAttributes = $existingRows->keyBy(fn ($row) => $lineKey($row->PartID, $row->Sequence, $row->SerialNo, $row->BatchNo, $row->CoilNo ?? null, $row->ExpDate));
            }

            $data = [];
            $selectedData = [];
            foreach ($details as $detail) {
                $detailAsnKey = $grAsnDetailColumn
                    ? 'asn|' . $detail->SourceDetailKey
                    : $lineKey($detail->PartID, $detail->Sequence, $detail->SerialNo, $detail->BatchNo, $detail->CoilNo, $detail->ExpDate);
                $fallbackKey = $lineKey($detail->PartID, $detail->Sequence, $detail->SerialNo, $detail->BatchNo, $detail->CoilNo, $detail->ExpDate);
                $existingDetail = $existingDetailsByAsnDetail->get($detailAsnKey) ?: $existingDetailsByAttributes->get($fallbackKey);
                $receivedDetail = $receivedQtyByAsnDetail->get($detailAsnKey) ?: $receivedQtyByAttributes->get($fallbackKey);
                $qtyRemaining = (float) $detail->Qty - (float) ($receivedDetail->Qty ?? 0);

                if ($qtyRemaining <= 0 && !$existingDetail) {
                    continue;
                }

                $poDetail = TransPurchaseOrderDT::where('TransactionNo', $asn->PONumber)
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->first();

                $asnUnitID2 = $detail->UnitID2 ?? $detail->UnitID;
                $unit2 = MsPartUnit::where('PartID', $detail->PartID)
                    ->where('UnitID2', $asnUnitID2)
                    ->first();

                $row = [
                    'SourceDetailKey' => (string) $detail->SourceDetailKey,
                    'ASNDetailID' => (string) $detail->SourceDetailKey,
                    'PartID' => $detail->PartID,
                    'PartName' => $detail->PartName,
                    'WithSerialNo' => (int) ($detail->WithSerialNo ?? 0),
                    'UnitID' => $poDetail->UnitID ?? $detail->UnitID,
                    'UnitID1' => $unit2->UnitID1 ?? $detail->UnitID,
                    'UnitID2' => $asnUnitID2,
                    'Unit' => $poDetail->UnitID ?? $detail->UnitID,
                    'UnitPrice' => $poDetail ? (($poDetail->UnitPrice - $poDetail->Discount) / $poDetail->Conversion) : 0,
                    'Qty' => $detail->Qty,
                    'Qty2' => (float) ($detail->Qty2 ?? $detail->Qty ?? 0),
                    'Conversion' => (float) ($poDetail->Conversion ?? $detail->Conversion ?? 0),
                    'QtyRemaining' => $qtyRemaining,
                    'Sequence' => $detail->Sequence,
                    'QtyReceive' => 0,
                    'BatchNo' => $detail->BatchNo,
                    'CoilNo' => $detail->CoilNo ?: ($detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null),
                    'SerialNo' => $detail->SerialNo,
                    'ExpDate' => $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                    'BIN' => $detail->BIN,
                    'LOC' => $detail->LOC,
                    'RateBeaMasuk' => $detail->RateBeaMasuk ?? $poDetail->RateBeaMasuk ?? 0,
                    'RateBeaAccount' => $detail->RateBeaAccount ?? $poDetail->RateBeaAccount ?? null,
                    'AntiDumping' => $detail->AntiDumping ?? $poDetail->AntiDumping ?? 0,
                    'AntiDumpingAccount' => $detail->AntiDumpingAccount ?? $poDetail->AntiDumpingAccount ?? null,
                    'rev' => $this->getRevisionValues((int) $asn->RevCount, $existingDetail ?: $detail),
                ];

                $data[] = $row;

                if ($existingDetail) {
                    $selectedRow = $row;
                    $selectedRow['QtyReceive'] = $existingDetail->Qty;
                    $selectedRow['BatchNo'] = $existingDetail->BatchNo;
                    $selectedRow['CoilNo'] = $existingDetail->CoilNo
                        ?: ($existingDetail->BatchNo ? CoilNoHelper::get($existingDetail->PartID, $existingDetail->BatchNo) : null);
                    $selectedRow['SerialNo'] = $existingDetail->SerialNo;
                    $selectedRow['ExpDate'] = $existingDetail->ExpDate ? Carbon::parse($existingDetail->ExpDate)->format('Y-m-d') : null;
                    $selectedRow['BIN'] = $existingDetail->BIN;
                    $selectedRow['LOC'] = $existingDetail->LOC;
                    $selectedRow['RateBeaMasuk'] = $existingDetail->RateBeaMasuk ?? $detail->RateBeaMasuk ?? $poDetail->RateBeaMasuk ?? 0;
                    $selectedRow['RateBeaAccount'] = $existingDetail->RateBeaAccount ?? $detail->RateBeaAccount ?? $poDetail->RateBeaAccount ?? null;
                    $selectedRow['AntiDumping'] = $existingDetail->AntiDumping ?? $detail->AntiDumping ?? $poDetail->AntiDumping ?? 0;
                    $selectedRow['AntiDumpingAccount'] = $existingDetail->AntiDumpingAccount ?? $detail->AntiDumpingAccount ?? $poDetail->AntiDumpingAccount ?? null;
                    $selectedRow['rev'] = $this->getRevisionValues((int) $asn->RevCount, $existingDetail);
                    $selectedData[] = $selectedRow;
                }
            }

            return response([
                'status' => 'success',
                'rev' => $asn->RevCount,
                'data' => $data,
                'selected_data' => $selectedData,
                'currency' => $asn->CurrencyID,
                'currencyName' => $asn->CurrencyID,
                'rate' => $asn->Rate,
                'po' => $asn->PONumber,
                'warehouse' => $asn->DestinationWarehouseID,
                'warehouseName' => $asn->DestinationWarehouseID,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response(['status' => 'error']);
        }
    }

    public function getPODetail(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $warehouseID = $request->input('warehouse_id');
            if (!$warehouseID && $request->filled('grID')) {
                $warehouseID = TransGoodsReceivingHD::where('TransactionNo', $request->input('grID'))->value('WarehouseID');
            }

            $po = TransPurchaseOrderHD::where('TransactionNo', $request->input('id'))
                ->whereHas('details', function ($detailQuery) use ($warehouseID) {
                    $detailQuery->where('WarehouseID', $warehouseID);
                })
                ->firstOrFail();

            $details = TransPurchaseOrderDT::where('TransactionNo', $request->input('id'))
                ->where('WarehouseID', $warehouseID)
                ->get();

            $data = [];
            $selectedData = [];
            $revCount = $po->RevCount;
            $existingGr = null;
            if ($request->input('grID') && $request->input('id') == $request->input('prevPO')) {
                $existingGr = TransGoodsReceivingHD::where('TransactionNo', $request->input('grID'))->first();
                if ($existingGr) {
                    $revCount = $existingGr->RevCount;
                }
            }

            foreach ($details as $detail) {
                $unit2 = MsPartUnit::where('PartID', $detail->PartID)
                    ->where('UnitID2', $detail->UnitID)
                    ->first();

                $qtyConverted = $detail->Qty * $detail->Conversion;
                $rev = $this->getRevisionValues($revCount, $detail);
                $existingDetails = collect();

                if ($existingGr) {
                    $existingDetails = TransGoodsReceivingDT::where('TransactionNo', $request->input('grID'))
                        ->where('PartID', $detail->PartID)
                        ->where('Sequence', $detail->Sequence)
                        ->get();
                }

                $price = ($detail->UnitPrice - $detail->Discount) / $detail->Conversion;
                if ($detail->parent->VAT == 'I') {
                    $vat = $detail->part->VAT2;
                    $temp = $price / (1 + ($vat / 100));
                    $tax = $temp * $vat / 100;
                    $price = $price - $tax;
                }

                $existingQty = $existingDetails->sum('Qty');
                $qtyRemain = $qtyConverted - $detail->QtyReceived + $existingQty;
                if ($qtyRemain > 0) {
                    $baseRow = [
                        'PartID' => $detail->PartID,
                        'PartName' => $detail->part->PartName,
                        'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                        'UnitID' => $detail->UnitID,
                        'UnitID1' => $unit2->UnitID1,
                        'Unit' => $detail->UnitID,
                        'UnitPrice' => $price,
                        'Qty' => $qtyConverted,
                        'QtyRemaining' => $qtyRemain,
                        'Sequence' => $detail->Sequence,
                        'QtyReceive' => 0,
                        'BatchNo' => null,
                        'CoilNo' => null,
                        'SerialNo' => null,
                        'ExpDate' => null,
                        'BIN' => null,
                        'LOC' => null,
                        'rev' => $rev,
                    ];

                    $data[] = $baseRow;

                    foreach ($existingDetails as $existingDetail) {
                        $selectedRow = $baseRow;
                        $selectedRow['QtyReceive'] = $existingDetail->Qty;
                        $selectedRow['BatchNo'] = $existingDetail->BatchNo;
                        $selectedRow['CoilNo'] = $existingDetail->CoilNo
                            ?: ($existingDetail->BatchNo ? CoilNoHelper::get($existingDetail->PartID, $existingDetail->BatchNo) : null);
                        $selectedRow['SerialNo'] = $existingDetail->SerialNo;
                        $selectedRow['ExpDate'] = $existingDetail->ExpDate ? Carbon::parse($existingDetail->ExpDate)->format('Y-m-d') : null;
                        $selectedRow['BIN'] = $existingDetail->BIN;
                        $selectedRow['LOC'] = $existingDetail->LOC;
                        $selectedRow['rev'] = $this->getRevisionValues($revCount, $existingDetail);
                        $selectedData[] = $selectedRow;
                    }
                }
            }

            return response([
                'status' => 'success',
                'rev' => $revCount,
                'data' => $data,
                'selected_data' => $selectedData,
                'currency' => $po->CurrencyID,
                'currencyName' => $po->CurrencyID . ($po->currency->CurrencyName ? ' - ' . $po->currency->CurrencyName : ''),
                'rate' => $po->Rate,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        $this->guardAdvancedAccess();

        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_GoodsReceivingHD,TransactionNo',
            'TransactionDate' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            'ASNNumber' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $asn = $this->resolveAsnForRequest($request);
            $po = TransPurchaseOrderHD::where('TransactionNo', $request->input('PONumber'))->first();
            $this->validateDateAndQty($request, $po);
            $this->validateUniqueSubmittedSerialNos($request, null, $asn ? [$asn->TransactionNo] : []);
            $id = $this->resolveTransactionNo($request);
            $qcId = $this->createQualityControl($request);

            $grHeader = [
                'TransactionNo' => $id,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'QCNumber' => $qcId,
                'ASNNumber' => $asn?->TransactionNo,
                'WarehouseID' => $request->input('WarehouseID'),
                'CurrencyID' => $request->input('CurrencyID'),
                'Rate' => $request->input('Rate') ?? 1,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'RevCount' => $request->input('rev') ?? 0,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $request->input('_last_digit'),
            ];

            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingHD', 'FiscalRate')) {
                $grHeader['FiscalRate'] = $asn?->FiscalRate ?? $request->input('FiscalRate') ?? 1;
            }

            TransGoodsReceivingHD::create($grHeader);

            [$grDetails, $bsDetails] = $this->buildReceivingRows($request, $id, !$asn, $asn);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grDetails);
            $this->insertInChunks(BukuStock::class, $bsDetails);
            $this->checkOutstanding($request->input('PONumber'));
            $this->checkOutstandingASN($asn->TransactionNo);
            $this->rebuildJournal($id);

            DB::commit();
            clear_form_preservation('purchase_gr_asn_add');

            return redirect()->route('gr')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Goods Receiving successfully added!',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors([$e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $this->guardAdvancedAccess();

        $this->validate($request, [
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            'ASNNumber' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $gr = TransGoodsReceivingHD::where('TransactionNo', $request->input('id'))->first();
            if (!$gr) {
                throw new \Exception('Goods Receiving does not exist.');
            }

            $asn = DB::table('Trans_AdvanceShippingNoticeHD')->where('TransactionNo', $gr->ASNNumber)->first();
            if (!$asn) {
                throw new \Exception('ASN does not exist.');
            }

            $request->merge([
                'ASNNumber' => $asn->TransactionNo,
                'PONumber' => $asn->PONumber,
                'WarehouseID' => $asn->DestinationWarehouseID,
            ]);

            $po = TransPurchaseOrderHD::where('TransactionNo', $request->input('PONumber'))->first();
            $this->validateDateAndQty($request, $po);

            $oldPoNumber = $gr->qc?->PONumber;
            if (!$oldPoNumber) {
                throw new \Exception('Goods Receiving QC reference does not exist.');
            }

            $this->validateAndReverseExistingRows($gr, $oldPoNumber);
            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            $this->validateUniqueSubmittedSerialNos($request, $gr->TransactionNo, $asn ? [$asn->TransactionNo] : []);

            $qc = TransQualityControlReceivingHD::where('TransactionNo', $gr->QCNumber)->first();
            if (!$qc) {
                throw new \Exception('Goods Receiving QC reference does not exist.');
            }

            $qc->update([
                'PONumber' => $request->input('PONumber'),
                'WarehouseID' => $request->input('WarehouseID'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
            TransQualityControlReceivingDT::where('TransactionNo', $qc->TransactionNo)->delete();
            $this->insertInChunks(TransQualityControlReceivingDT::class, $this->buildQcRows($request, $qc->TransactionNo));

            $grUpdate = [
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'WarehouseID' => $request->input('WarehouseID'),
                'CurrencyID' => $request->input('CurrencyID'),
                'Rate' => $request->input('Rate') ?? 0,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'RevCount' => $request->input('rev') ?? 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ];

            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingHD', 'FiscalRate')) {
                $grUpdate['FiscalRate'] = $asn->FiscalRate ?? $request->input('FiscalRate') ?? $gr->FiscalRate ?? 1;
            }

            $gr->update($grUpdate);

            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::connection('sqlsrv')->hasTable('Ms_FixedAsset')) {
                // Rebuilt below from the (possibly changed) detail lines, same as GR's own
                // detail rows and Buku Stock are rebuilt on every edit.
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }
            [$grDetails, $bsDetails] = $this->buildReceivingRows($request, $gr->TransactionNo, !$asn, $asn);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grDetails);
            $this->insertInChunks(BukuStock::class, $bsDetails);
            $this->checkOutstanding($request->input('PONumber'));
            $this->checkOutstandingASN($asn->TransactionNo);
            if ($oldPoNumber !== $request->input('PONumber')) {
                $this->checkOutstanding($oldPoNumber);
            }
            $this->rebuildJournal($gr->TransactionNo);

            DB::commit();

            return redirect()->route('gr')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Goods Receiving successfully updated!',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors([$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $this->guardAdvancedAccess();

        DB::beginTransaction();
        try {
            $gr = TransGoodsReceivingHD::where('id', $id)->first();
            if (!$gr) {
                throw new \Exception('Goods Receiving does not exist.');
            }

            $poNumber = $gr->qc->PONumber;
            $asnNumber = $gr->ASNNumber;
            $this->validateAndReverseExistingRows($gr, $poNumber);

            TransJournalDT::where('TransactionNo', $gr->TransactionNo)->delete();
            TransJournalHD::where('TransactionNo', $gr->TransactionNo)->delete();
            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::connection('sqlsrv')->hasTable('Ms_FixedAsset')) {
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }

            $qcNumber = $gr->QCNumber;
            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            $gr->delete();
            TransQualityControlReceivingDT::where('TransactionNo', $qcNumber)->delete();
            TransQualityControlReceivingHD::where('TransactionNo', $qcNumber)->delete();
            $this->checkOutstanding($poNumber);
            if ($asnNumber) {
                $this->checkOutstandingASN($asnNumber);
            }

            DB::commit();
            return response(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();

            return response(['status' => 'failed']);
        }
    }

    public function loadPO(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $transactions = TransPurchaseOrderHD::where('Closed', 0);

            if ($request->boolean('require_warehouse') && !$request->filled('warehouse_id')) {
                $transactions->whereRaw('1 = 0');
            } elseif ($request->filled('warehouse_id')) {
                $transactions->whereHas('details', function ($detailQuery) use ($request) {
                    $detailQuery->where('WarehouseID', $request->input('warehouse_id'));
                });
            }

            if ($request->filled('search')) {
                $transactions->where('TransactionNo', 'like', '%' . $request->input('search') . '%');
            }

            $data = [];
            foreach ($transactions->get() as $transaction) {
                $data[] = [
                    'id' => $transaction->TransactionNo,
                    'text' => $transaction->TransactionNo,
                ];
            }

            if ($request->get('inv')) {
                $po = TransPurchaseOrderHD::where('TransactionNo', $request->get('inv'))
                    ->when($request->filled('warehouse_id'), function ($query) use ($request) {
                        $query->whereHas('details', function ($detailQuery) use ($request) {
                            $detailQuery->where('WarehouseID', $request->input('warehouse_id'));
                        });
                    })
                    ->first();

                if ($po && $po->Closed == 1) {
                    $data[] = [
                        'id' => $po->TransactionNo,
                        'text' => $po->TransactionNo,
                    ];
                }
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    private function resolveAsnForRequest(Request $request)
    {
        if (!$this->useAsnEnabled()) {
            return null;
        }

        if (!$request->input('ASNNumber')) {
            throw new \Exception('ASN Number is required when ASN flow is enabled.');
        }

        $asn = DB::table('Trans_AdvanceShippingNoticeHD')
            ->where('TransactionNo', $request->input('ASNNumber'))
            ->first();

        if (!$asn) {
            throw new \Exception('ASN does not exist.');
        }

        $request->merge([
            'PONumber' => $asn->PONumber,
            'WarehouseID' => $asn->DestinationWarehouseID,
        ]);

        return $asn;
    }

    private function getSelectedPO(Request $request): TransPurchaseOrderHD
    {
        $po = TransPurchaseOrderHD::where('TransactionNo', $request->input('PONumber'))
            ->whereHas('details', function ($detailQuery) use ($request) {
                $detailQuery->where('WarehouseID', $request->input('WarehouseID'));
            })
            ->first();

        if (!$po) {
            throw new \Exception('The selected Purchase Order has no detail for the selected warehouse.');
        }

        return $po;
    }

    protected function useAsnEnabled(): bool
    {
        return true;
    }

    protected static function shouldUseAsnFlow(): bool
    {
        if (ControlPanel::isEnabled('use_asn')) {
            return true;
        }

        $value = strtolower(trim((string) ControlPanel::getValue('use_asn', '0')));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
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

    private function resolveStockDualUnitValues(?TransPurchaseOrderDT $detail, float $qty): array
    {
        $conversion = (float) ($detail?->Conversion ?? 0);
        $qty2 = $conversion > 0 ? $qty / $conversion : null;

        return [
            'Qty2' => $qty2 !== null && abs($qty2) > 0.000001 ? $qty2 : null,
            'UnitID2' => $detail?->UnitID,
        ];
    }

    private function mergeDetailsJsonIntoRequest(Request $request): void
    {
        if (!$request->filled('DetailsJson')) {
            return;
        }

        $details = json_decode($request->input('DetailsJson'), true);
        if (!is_array($details)) {
            throw new \Exception('Invalid detail payload.');
        }

        $revCount = (int) ($request->input('rev') ?? 0);
        $merged = [
            'Sequence' => [],
            'SourceDetailKey' => [],
            'PartID' => [],
            'PartName' => [],
            'UnitID' => [],
            'UnitID2' => [],
            'Qty' => [],
            'Qty2' => [],
            'Conversion' => [],
            'QtyRemaining' => [],
            'QtyReceive' => [],
            'BatchNo' => [],
            'CoilNo' => [],
            'SerialNo' => [],
            'ExpDate' => [],
            'BIN' => [],
            'LOC' => [],
            'RateBeaMasuk' => [],
            'RateBeaAccount' => [],
            'AntiDumping' => [],
            'AntiDumpingAccount' => [],
        ];

        for ($i = 1; $i <= $revCount; $i++) {
            $merged['rev' . $i] = [];
        }

        foreach ($details as $detail) {
            $qtyReceive = (float) ($detail['QtyReceive'] ?? 0);
            if ($qtyReceive <= 0) {
                continue;
            }

            $sourceDetailKey = $detail['SourceDetailKey'] ?? $detail['ASNDetailID'] ?? $detail['id'] ?? null;
            $merged['Sequence'][] = $detail['Sequence'] ?? null;
            $merged['SourceDetailKey'][] = $sourceDetailKey;
            $merged['PartID'][] = $detail['PartID'] ?? null;
            $merged['PartName'][] = $detail['PartName'] ?? null;
            $merged['UnitID'][] = $detail['UnitID1'] ?? ($detail['UnitID'] ?? null);
            $merged['UnitID2'][] = $detail['UnitID2'] ?? ($detail['UnitID'] ?? null);
            $merged['Qty'][] = $detail['Qty'] ?? 0;
            $merged['Qty2'][] = $detail['Qty2'] ?? 0;
            $merged['Conversion'][] = $detail['Conversion'] ?? 0;
            $merged['QtyRemaining'][] = $detail['QtyRemaining'] ?? 0;
            $merged['QtyReceive'][] = $qtyReceive;
            $merged['BatchNo'][] = $detail['BatchNo'] ?? null;
            $merged['CoilNo'][] = $detail['CoilNo'] ?? null;
            $merged['SerialNo'][] = $detail['SerialNo'] ?? null;
            $merged['ExpDate'][] = $detail['ExpDate'] ?? null;
            $merged['BIN'][] = $detail['BIN'] ?? null;
            $merged['LOC'][] = $detail['LOC'] ?? null;
            $merged['RateBeaMasuk'][] = $detail['RateBeaMasuk'] ?? 0;
            $merged['RateBeaAccount'][] = $detail['RateBeaAccount'] ?? null;
            $merged['AntiDumping'][] = $detail['AntiDumping'] ?? 0;
            $merged['AntiDumpingAccount'][] = $detail['AntiDumpingAccount'] ?? null;

            $rev = $detail['rev'] ?? [];
            for ($i = 1; $i <= $revCount; $i++) {
                $merged['rev' . $i][] = $rev[$i - 1] ?? null;
            }
        }

        $request->merge($merged);
    }

    private function validateDateAndQty(Request $request, TransPurchaseOrderHD $po): void
    {
        $date = Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'));
        $poDate = Carbon::parse($po->TransactionDate);
        if ($date->lessThan($poDate)) {
            throw new \Exception("Transaction Date must not be earlier than Purchase Order's date!");
        }

        if (array_sum($request->input('Qty', [])) == 0) {
            throw new \Exception('You need to receive at least one item!');
        }

        $receivedByDetail = [];
        $remainingByDetail = [];
        foreach ($request->input('QtyReceive', []) as $i => $qtyReceive) {
            $key = $this->generalService->nullableDetailValue($request->input('SourceDetailKey')[$i] ?? null);
            if ($key === null) {
                throw new \Exception('ASN detail ID is required.');
            }

            $receivedByDetail[$key] = ($receivedByDetail[$key] ?? 0) + (float) $qtyReceive;
            $remainingByDetail[$key] = (float) ($request->input('QtyRemaining')[$i] ?? 0);

            if ($qtyReceive > $request->input('QtyRemaining')[$i]) {
                throw new \Exception('Receive amount must not exceed Remaining amount!');
            }
        }

        foreach ($receivedByDetail as $key => $qtyReceive) {
            if ($qtyReceive > ($remainingByDetail[$key] ?? 0)) {
                throw new \Exception('Total receive amount must not exceed Remaining amount!');
            }
        }
    }
    private function validateUniqueSubmittedSerialNos(Request $request, ?string $currentTransactionNo = null, array $allowedTransactionNos = []): void
    {
    }

    private function getWithSerialNoByPart(Request $request): array
    {
        $partIds = collect($request->input('PartID', []))
            ->filter()
            ->unique()
            ->values();

        $result = [];
        foreach ($partIds->chunk(1000) as $chunk) {
            MsPart::whereIn('PartID', $chunk->all())
                ->get(['PartID', 'WithSerialNo'])
                ->each(function ($part) use (&$result) {
                    $result[$part->PartID] = (int) ($part->WithSerialNo ?? 0);
                });
        }

        return $result;
    }

    private function resolveTransactionNo(Request $request): string
    {
        if (!$request->input('automatic')) {
            $request->merge(['_last_digit' => null]);
            return trim($request->input('TransactionNo'));
        }

        $masterAuto = MsAutoNumber::find('1');
        $checkLast = TransGoodsReceivingHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
            ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;
        $id = $masterAuto->Purchase11 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
            . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
            . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

        if (TransGoodsReceivingHD::where('TransactionNo', $id)->first()) {
            throw new \Exception('Transaction No has already been taken!');
        }

        $request->merge(['_last_digit' => $digit]);
        return $id;
    }

    private function createQualityControl(Request $request): string
    {
        $qcId = generateRandomString(50);
        TransQualityControlReceivingHD::create([
            'TransactionNo' => $qcId,
            'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
            'QCNo' => $qcId,
            'PONumber' => $request->input('PONumber'),
            'WarehouseID' => $request->input('WarehouseID'),
            'RevCount' => $request->input('rev') ?? 0,
            'CreatedBy' => Auth::user()->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
            'LastUpdateBy' => Auth::user()->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
        ]);

        $this->insertInChunks(TransQualityControlReceivingDT::class, $this->buildQcRows($request, $qcId));

        return $qcId;
    }

    private function buildQcRows(Request $request, string $qcId): array
    {
        $rows = [];
        foreach ($request->input('PartID', []) as $i => $part) {
            if ($request->input('QtyReceive')[$i] != null && $request->input('QtyReceive')[$i] > 0) {
                $stockAttributes = $this->getSubmittedStockAttributes($request, $i);

                $rows[] = [
                    'TransactionNo' => $qcId,
                    'PartID' => $part,
                    'Sequence' => $request->input('Sequence')[$i],
                    'UnitID' => $request->input('UnitID')[$i],
                    'Qty' => $request->input('QtyReceive')[$i],
                    'Passed' => 1,
                    'BatchNo' => $stockAttributes['BatchNo'],
                    'SerialNo' => $stockAttributes['SerialNo'],
                    'ExpDate' => $stockAttributes['ExpDate'],
                    'BIN' => $stockAttributes['BIN'],
                    'LOC' => $stockAttributes['LOC'],
                ];
            }
        }

        return $rows;
    }

    private function buildReceivingRows(Request $request, string $transactionNo, bool $filterWarehouse = true, $asn = null): array
    {
        $grRows = [];
        $bsRows = [];

        foreach ($request->input('PartID', []) as $i => $part) {
            $qtyReceive = $request->input('QtyReceive')[$i] ?? null;
            if ($qtyReceive == null || $qtyReceive <= 0) {
                continue;
            }

            $sequence = $request->input('Sequence')[$i];
            $detailQuery = TransPurchaseOrderDT::where('TransactionNo', $request->input('PONumber'))
                ->where('PartID', $part)
                ->where('Sequence', $sequence);

            if ($filterWarehouse) {
                $detailQuery->where('WarehouseID', $request->input('WarehouseID'));
            }

            $detail = $detailQuery->first();

            if (!$detail) {
                throw new \Exception('Purchase Order detail does not belong to the selected warehouse.');
            }
            $qty2Inputs = $request->input('Qty2', []);
            $unitID2Inputs = $request->input('UnitID2', []);
            $submittedQty2 = $qty2Inputs[$i] ?? null;
            $submittedUnitID2 = $unitID2Inputs[$i] ?? null;
            $dualUnit = (
                $submittedQty2 !== null
                || $submittedUnitID2 !== null
            ) ? [
                'Qty2' => $submittedQty2 !== null && $submittedQty2 !== '' ? (float) $submittedQty2 : null,
                'UnitID2' => $submittedUnitID2 !== null && $submittedUnitID2 !== '' ? $submittedUnitID2 : null,
            ] : $this->resolveStockDualUnitValues($detail, (float) $qtyReceive);

            $newReceive = $detail->QtyReceived + $qtyReceive;
            $updateQuery = TransPurchaseOrderDT::where('TransactionNo', $request->input('PONumber'))
                ->where('PartID', $part)
                ->where('Sequence', $sequence);

            if ($filterWarehouse) {
                $updateQuery->where('WarehouseID', $request->input('WarehouseID'));
            }

            $updateQuery->update([
                'QtyReceived' => $newReceive,
            ]);

            $stockAttributes = $this->getSubmittedStockAttributes($request, $i, (int) ($detail->part->WithSerialNo ?? 0) === 1, $part);

            $grRow = [
                'TransactionNo' => $transactionNo,
                'PartID' => $part,
                'Sequence' => $sequence,
                'UnitID' => $request->input('UnitID')[$i],
                'Qty' => $qtyReceive,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
            ];
            if (Schema::connection('sqlsrv')->hasColumn('Trans_GoodsReceivingDT', 'CoilNo')) {
                $grRow['CoilNo'] = $stockAttributes['CoilNo'];
            }
            if ($asn && $asnDetailColumn = $this->getGoodsReceivingAsnDetailColumn()) {
                $grRow[$asnDetailColumn] = $request->input('SourceDetailKey')[$i] ?? null;
            }
            $dutySource = (object) [
                'RateBeaMasuk' => $request->input('RateBeaMasuk')[$i] ?? $detail->RateBeaMasuk ?? 0,
                'RateBeaAccount' => $request->input('RateBeaAccount')[$i] ?? $detail->RateBeaAccount ?? null,
                'AntiDumping' => $request->input('AntiDumping')[$i] ?? $detail->AntiDumping ?? 0,
                'AntiDumpingAccount' => $request->input('AntiDumpingAccount')[$i] ?? $detail->AntiDumpingAccount ?? null,
            ];
            $grRow = $this->applyDetailDutyFields($grRow, 'Trans_GoodsReceivingDT', $dutySource);

            for ($j = 1; $j <= $request->input('rev'); $j++) {
                $grRow['ItemRevDT' . str_pad($j, 2, '0', STR_PAD_LEFT)] = $request->input('rev' . $j)[$i];
            }

            $grRows[] = $grRow;

            $this->autoCreateFixedAssetsForReceipt(
                $part,
                (float) $qtyReceive,
                $detail->DivisionID ?? null,
                $this->generalService->formatDate($request->input('TransactionDate')),
                $request->input('CurrencyID'),
                (float) ($request->input('Rate') ?? 1),
                (float) ($detail->UnitPrice ?? 0),
                $transactionNo,
                $stockAttributes['BatchNo']
            );

            $bsRows[] = [
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'PartID' => $part,
                'WarehouseID' => $request->input('WarehouseID'),
                'Sequence' => $sequence,
                'UnitID' => $request->input('UnitID')[$i],
                'Qty' => $qtyReceive,
                'Qty2' => $dualUnit['Qty2'],
                'UnitID2' => $dualUnit['UnitID2'],
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'TransactionType' => 'GOODS_RECEIVING',
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ];

            if ($asn) {
                $asnStockAttributes = $stockAttributes;
                $asnStockAttributes['BIN'] = null;
                $asnStockAttributes['LOC'] = null;

                $availableStock = BukuStockHelper::calculateCurrentStock(
                    $part,
                    $asn->WarehouseID,
                    $asnStockAttributes['BatchNo'],
                    $asnStockAttributes['SerialNo'],
                    $asnStockAttributes['ExpDate']
                );

                if ($availableStock < $qtyReceive) {
                    throw new \Exception('Virtual ASN stock is not enough for selected stock details. ' . $this->stockNotAvailableMessage(
                        $part,
                        $asn->WarehouseID,
                        $asnStockAttributes
                    ));
                }

                $bsRows[] = [
                    'TransactionNo' => $transactionNo,
                    'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                    'PartID' => $part,
                    'WarehouseID' => $asn->WarehouseID,
                    'Sequence' => $sequence,
                    'UnitID' => $request->input('UnitID')[$i],
                    'Qty' => -1 * (float) $qtyReceive,
                    'Qty2' => $dualUnit['Qty2'] !== null ? -1 * (float) $dualUnit['Qty2'] : null,
                    'UnitID2' => $dualUnit['UnitID2'],
                    'BatchNo' => $stockAttributes['BatchNo'],
                    'SerialNo' => $stockAttributes['SerialNo'],
                    'ExpDate' => $stockAttributes['ExpDate'],
                    'BIN' => $stockAttributes['BIN'],
                    'LOC' => $stockAttributes['LOC'],
                    'TransactionType' => 'GOODS_RECEIVING',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ];
            }
        }

        return [$grRows, $bsRows];
    }

    private function validateAndReverseExistingRows(TransGoodsReceivingHD $gr, string $poNumber): void
    {
        foreach ($gr->details as $detail) {
            $availableStock = BukuStockHelper::calculateCurrentStock(
                $detail->PartID,
                $gr->WarehouseID,
                $detail->BatchNo,
                $detail->SerialNo,
                $detail->ExpDate,
                $detail->BIN,
                $detail->LOC
            );

            if (($availableStock - $detail->Qty) < 0) {
                throw new \Exception($this->stockNotAvailableMessage(
                    $detail->PartID,
                    $gr->WarehouseID,
                    $this->stockAttributesFromDetail($detail)
                ));
            }

            $poDetail = TransPurchaseOrderDT::where('TransactionNo', $poNumber)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->where('WarehouseID', $gr->WarehouseID)
                ->first();

            if ($poDetail) {
                $newReceive = $poDetail->QtyReceived - $detail->Qty;
                TransPurchaseOrderDT::where('TransactionNo', $poNumber)
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->where('WarehouseID', $gr->WarehouseID)
                    ->update([
                        'QtyReceived' => $newReceive,
                    ]);
            }
        }
    }

    private function getSubmittedStockAttributes(Request $request, int $index, bool $withSerialNo = true, ?string $partId = null): array
    {
        $batchNo = $this->generalService->nullableDetailValue($request->input('BatchNo')[$index] ?? null);
        $coilNo = $this->generalService->nullableDetailValue($request->input('CoilNo')[$index] ?? null);

        if (!$coilNo && $partId && $batchNo) {
            $coilNo = CoilNoHelper::get($partId, $batchNo);
        }

        return [
            'BatchNo' => $batchNo,
            'CoilNo' => $coilNo,
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ];
    }

    private function stockAttributesFromDetail(TransGoodsReceivingDT $detail): array
    {
        $batchNo = $detail->BatchNo;

        return [
            'BatchNo' => $batchNo,
            'CoilNo' => $detail->CoilNo ?: ($batchNo ? CoilNoHelper::get($detail->PartID, $batchNo) : null),
            'SerialNo' => $detail->SerialNo,
            'ExpDate' => $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
            'BIN' => $detail->BIN,
            'LOC' => $detail->LOC,
        ];
    }

    private function stockNotAvailableMessage(string $partId, string $warehouseId, array $stockAttributes): string
    {
        $details = [
            'Part' => $partId,
            'Warehouse' => $warehouseId,
            'Batch No' => $stockAttributes['BatchNo'] ?? null,
            'Coil No' => $stockAttributes['CoilNo'] ?? null,
        ];

        $detailText = collect($details)
            ->map(fn ($value, $label) => $label . ': ' . ($value === null || $value === '' ? '(Empty)' : $value))
            ->implode(', ');

        return 'Stock is not available for selected stock details. ' . $detailText . '.';
    }

    private function nullableSubmittedDate(?string $value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    /**
     * A part with PartType 'F' (Fixed Asset) gets one Ms_FixedAsset row per unit received,
     * instead of the usual stock movement. Every field used here already exists on Ms_FixedAsset
     * (PartID, ReceivingNumber, BatchNo, etc. - no schema change needed). Ms_FixedAsset's
     * CategoryID comes from the Part's Inventory Type (Ms_InventoryType, managed at
     * /inventory/type) - specifically that Inventory Type's own Notes field, which the user sets
     * to match an existing Ms_FixedAssetCategory.CategoryID. Every asset lands in the fixed
     * LocationID 'JAKARTA'. FixedAssetCode is PartID plus a running per-part sequence (e.g.
     * FA00001-0001), since there's no dedicated auto-number slot for it. Mirrors
     * GoodsReceivingController::autoCreateFixedAssetsForReceipt() (non-ASN flow) and
     * kawaguci-nla's GoodsReceivingController::autoCreateFixedAssetsForReceipt().
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
            throw new \Exception("Part {$partId}'s Inventory Type ({$part->InventoryTypeID}) has no Fixed Asset Category set in its Notes.");
        }
        if (!MsFixedAssetCategory::find($categoryId)) {
            throw new \Exception("Inventory Type {$part->InventoryTypeID}'s Notes ('{$categoryId}') does not match any existing Fixed Asset Category ID.");
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

    private function checkOutstanding(string $poNumber): void
    {
        PurchaseOrderStateService::recalculate($poNumber, Auth::user()->UserID);
    }

    private function checkOutstandingASN(string $asnNumber): void
    {
        $asn = DB::table('Trans_AdvanceShippingNoticeHD')
            ->where('TransactionNo', $asnNumber)
            ->first();

        if (!$asn) {
            return;
        }

        $asnQty = DB::table('Trans_AdvanceShippingNoticeDT')
            ->where('TransactionNo', $asnNumber)
            ->select('PartID', 'Sequence', DB::raw('SUM(Qty) as Qty'))
            ->groupBy('PartID', 'Sequence')
            ->get()
            ->keyBy(fn ($row) => strtolower(trim((string) $row->PartID)) . '|' . $row->Sequence);

        $receivedQty = DB::table('Trans_GoodsReceivingDT as grdt')
            ->join('Trans_GoodsReceivingHD as grhd', 'grhd.TransactionNo', '=', 'grdt.TransactionNo')
            ->where('grhd.ASNNumber', $asnNumber)
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
            ->where('TransactionNo', $asnNumber)
            ->update([
                'Outstanding' => $outstanding ? 1 : 0,
                'Editable' => $outstanding ? 1 : 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
    }

    private function rebuildJournal(string $transactionNo): void
    {
        GoodsReceivingJournalService::rebuild($transactionNo);
    }

    private function getRevisionValues(int $revCount, $detail): array
    {
        $values = [];
        for ($i = 1; $i <= $revCount; $i++) {
            $values[] = $detail->{'ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '';
        }

        return $values;
    }

    protected function getRev(): array
    {
        return $this->buildRevisionAliases('PURCHASING');
    }

    private function buildRevisionAliases(string $transactionType): array
    {
        $revs = MsRevAlias::where('TransactionType', $transactionType)->first();

        if (!$revs) {
            return [];
        }

        $revData = [];
        for ($i = 1; $i <= 22; $i++) {
            $field = 'ItemRevDT' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $revData[] = [
                'name' => $revs->{$field},
                'type' => $revs->{$field . 'Type'},
            ];
        }

        return $revData;
    }

    protected function guardAdvancedAccess(): void
    {
    }
}
