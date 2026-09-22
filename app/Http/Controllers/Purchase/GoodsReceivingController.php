<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\BukuStockHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\ControlPanel;
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

class GoodsReceivingController extends Controller
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

        $data = TransGoodsReceivingHD::select('id', 'TransactionNo', 'TransactionDate', 'WarehouseID', 'Rate', 'Notes', 'Editable', 'ASNNumber', 'updated_at', 'LastUpdate')
            ->with('warehouse');
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
            ->editColumn('WarehouseID', function ($row) {
                return optional($row->warehouse)->WarehouseName ?? $row->WarehouseID;
            })
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

        if ($this->shouldUseAsnFlow()) {
            return redirect()->route('gr.asn.add');
        }

        $revData = $this->getRev();

        return view('purchase.gr.add', compact('revData'));
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $gr = TransGoodsReceivingHD::where('id', $id)->first();
        if ($gr && $gr->ASNNumber) {
            return redirect()->route('gr.asn.show', $id);
        }

        $revData = $this->getRev();
        $options = DocPrint::where('ModuleCode', 'GR')
            ->where('TypeStr', 'print')
            ->get();

        return view('purchase.gr.show', compact('gr', 'options', 'revData'));
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $gr = TransGoodsReceivingHD::where('id', $id)->first();
        if ($gr && $gr->ASNNumber) {
            return redirect()->route('gr.asn.edit', $id);
        }

        $revData = $this->getRev();

        return view('purchase.gr.edit', compact('gr', 'revData'));
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
                        'RateBeaMasuk' => $detail->RateBeaMasuk ?? 0,
                        'RateBeaAccount' => $detail->RateBeaAccount ?? null,
                        'AntiDumping' => $detail->AntiDumping ?? 0,
                        'AntiDumpingAccount' => $detail->AntiDumpingAccount ?? null,
                        'rev' => $rev,
                    ];

                    $data[] = $baseRow;

                    foreach ($existingDetails as $existingDetail) {
                        $selectedRow = $baseRow;
                        $selectedRow['QtyReceive'] = $existingDetail->Qty;
                        $selectedRow['BatchNo'] = $existingDetail->BatchNo;
                        $selectedRow['CoilNo'] = $existingDetail->CoilNo ?? null;
                        $selectedRow['SerialNo'] = $existingDetail->SerialNo;
                        $selectedRow['ExpDate'] = $existingDetail->ExpDate ? Carbon::parse($existingDetail->ExpDate)->format('Y-m-d') : null;
                        $selectedRow['BIN'] = $existingDetail->BIN;
                        $selectedRow['LOC'] = $existingDetail->LOC;
                        $selectedRow['RateBeaMasuk'] = $existingDetail->RateBeaMasuk ?? $detail->RateBeaMasuk ?? 0;
                        $selectedRow['RateBeaAccount'] = $existingDetail->RateBeaAccount ?? $detail->RateBeaAccount ?? null;
                        $selectedRow['AntiDumping'] = $existingDetail->AntiDumping ?? $detail->AntiDumping ?? 0;
                        $selectedRow['AntiDumpingAccount'] = $existingDetail->AntiDumpingAccount ?? $detail->AntiDumpingAccount ?? null;
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
            'WarehouseID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            'PONumber' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $po = TransPurchaseOrderHD::where('TransactionNo', $request->input('PONumber'))->first();
            $this->validateDateAndQty($request, $po);
            $this->validateUniqueSubmittedSerialNos($request);
            $id = $this->resolveTransactionNo($request);
            $qcId = $this->createQualityControl($request);

            TransGoodsReceivingHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'QCNumber' => $qcId,
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
            ]);

            [$grDetails, $bsDetails] = $this->buildReceivingRows($request, $id);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grDetails);
            $this->insertInChunks(BukuStock::class, $bsDetails);
            $this->checkOutstanding($request->input('PONumber'));
            $this->rebuildJournal($id);

            DB::commit();
            clear_form_preservation('purchase_gr_advanced_add');

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
            'WarehouseID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            'PONumber' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $gr = TransGoodsReceivingHD::where('TransactionNo', $request->input('id'))->first();
            if (!$gr) {
                throw new \Exception('Goods Receiving does not exist.');
            }

            $po = $this->getSelectedPO($request);
            $this->validateDateAndQty($request, $po);

            $oldPoNumber = $gr->qc?->PONumber;
            if (!$oldPoNumber) {
                throw new \Exception('Goods Receiving QC reference does not exist.');
            }

            $this->validateAndReverseExistingRows($gr, $oldPoNumber);
            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            $this->validateUniqueSubmittedSerialNos($request, $gr->TransactionNo);

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

            $gr->update([
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'WarehouseID' => $request->input('WarehouseID'),
                'CurrencyID' => $request->input('CurrencyID'),
                'Rate' => $request->input('Rate') ?? 0,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'RevCount' => $request->input('rev') ?? 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);

            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::hasTable('Ms_FixedAsset')) {
                // Rebuilt below from the (possibly changed) detail lines, same as GR's own
                // detail rows and Buku Stock are rebuilt on every edit.
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }
            [$grDetails, $bsDetails] = $this->buildReceivingRows($request, $gr->TransactionNo);
            $this->insertInChunks(TransGoodsReceivingDT::class, $grDetails);
            $this->insertInChunks(BukuStock::class, $bsDetails);
            $this->checkOutstanding($request->input('PONumber'));
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
            $this->validateAndReverseExistingRows($gr, $poNumber);

            TransJournalDT::where('TransactionNo', $gr->TransactionNo)->delete();
            TransJournalHD::where('TransactionNo', $gr->TransactionNo)->delete();
            BukuStock::where('TransactionNo', $gr->TransactionNo)->delete();
            if (Schema::hasTable('Ms_FixedAsset')) {
                MsFixedAsset::where('ReceivingNumber', $gr->TransactionNo)->delete();
            }

            $qcNumber = $gr->QCNumber;
            TransGoodsReceivingDT::where('TransactionNo', $gr->TransactionNo)->delete();
            $gr->delete();
            TransQualityControlReceivingDT::where('TransactionNo', $qcNumber)->delete();
            TransQualityControlReceivingHD::where('TransactionNo', $qcNumber)->delete();
            $this->checkOutstanding($poNumber);

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
            'PartID' => [],
            'PartName' => [],
            'UnitID' => [],
            'Qty' => [],
            'QtyRemaining' => [],
            'QtyReceive' => [],
            'BatchNo' => [],
            'SerialNo' => [],
            'ExpDate' => [],
            'BIN' => [],
            'LOC' => [],
        ];

        for ($i = 1; $i <= $revCount; $i++) {
            $merged['rev' . $i] = [];
        }

        foreach ($details as $detail) {
            $qtyReceive = (float) ($detail['QtyReceive'] ?? 0);
            if ($qtyReceive <= 0) {
                continue;
            }

            $merged['Sequence'][] = $detail['Sequence'] ?? null;
            $merged['PartID'][] = $detail['PartID'] ?? null;
            $merged['PartName'][] = $detail['PartName'] ?? null;
            $merged['UnitID'][] = $detail['UnitID1'] ?? ($detail['UnitID'] ?? null);
            $merged['Qty'][] = $detail['Qty'] ?? 0;
            $merged['QtyRemaining'][] = $detail['QtyRemaining'] ?? 0;
            $merged['QtyReceive'][] = $qtyReceive;
            $merged['BatchNo'][] = $detail['BatchNo'] ?? null;
            $merged['SerialNo'][] = $detail['SerialNo'] ?? null;
            $merged['ExpDate'][] = $detail['ExpDate'] ?? null;
            $merged['BIN'][] = $detail['BIN'] ?? null;
            $merged['LOC'][] = $detail['LOC'] ?? null;

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
            $key = ($request->input('PartID')[$i] ?? '') . '|' . ($request->input('Sequence')[$i] ?? '');
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

    private function buildReceivingRows(Request $request, string $transactionNo): array
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

            $detailQuery->where('WarehouseID', $request->input('WarehouseID'));

            $detail = $detailQuery->first();

            if (!$detail) {
                throw new \Exception('Purchase Order detail does not belong to the selected warehouse.');
            }

            $newReceive = $detail->QtyReceived + $qtyReceive;
            $updateQuery = TransPurchaseOrderDT::where('TransactionNo', $request->input('PONumber'))
                ->where('PartID', $part)
                ->where('Sequence', $sequence);

            $updateQuery->where('WarehouseID', $request->input('WarehouseID'));

            $updateQuery->update([
                'QtyReceived' => $newReceive,
            ]);

            $stockAttributes = $this->getSubmittedStockAttributes($request, $i, (int) ($detail->part->WithSerialNo ?? 0) === 1);

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
            $grRow = $this->applyDetailDutyFields($grRow, 'Trans_GoodsReceivingDT', $detail);

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

    private function getSubmittedStockAttributes(Request $request, int $index, bool $withSerialNo = true): array
    {
        return [
            'BatchNo' => $this->generalService->nullableDetailValue($request->input('BatchNo')[$index] ?? null),
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ];
    }

    private function stockAttributesFromDetail(TransGoodsReceivingDT $detail): array
    {
        return [
            'BatchNo' => $detail->BatchNo,
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
     * FA00001-0001), since there's no dedicated auto-number slot for it. Mirrors kawaguci-nla's
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
        if (!Schema::hasTable('Ms_FixedAsset')) {
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

    private function getRev(): array
    {
        $revs = MsRevAlias::where('TransactionType', 'PURCHASING')->first();

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

    private function shouldUseAsnFlow(): bool
    {
        if (ControlPanel::isEnabled('use_asn')) {
            return true;
        }

        $value = strtolower(trim((string) ControlPanel::getValue('use_asn', '0')));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function guardAdvancedAccess(): void
    {
    }
}
