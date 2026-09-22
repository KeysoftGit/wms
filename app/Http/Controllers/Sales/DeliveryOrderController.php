<?php

namespace App\Http\Controllers\Sales;

use App\Helpers\BukuStockHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsCustomer;
use App\Models\MsCustomerShipment;
use App\Models\MsWarehouse;
use App\Models\MsPartUnit;
use App\Models\MsRevAlias;
use App\Models\TransDeliveryOrderAmount;
use App\Models\TransDeliveryOrderDT;
use App\Models\TransDeliveryOrderHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransSalesOrderDT;
use App\Models\TransSalesOrderHD;
use App\Services\GeneralService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DeliveryOrderController extends Controller
{
    private GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    public function index()
    {
        $this->guardAdvancedAccess();

        return view('sales.do.index');
    }

    public function datatable(Request $request)
    {
        $this->guardAdvancedAccess();

        $data = TransDeliveryOrderHD::select('id', 'TransactionNo', 'TransactionDate', 'VehicleID', 'DriverID', 'Notes', 'Editable');
        WarehouseAccessCriteria::applyDetails($data);

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
                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('do.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'do.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('do.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'do.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('do.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
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

        return view('sales.do.add', compact('revData'));
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $do = TransDeliveryOrderHD::where('id', $id)->first();
        if (!$do) {
            return redirect()->route('do')->withErrors(['Delivery Order does not exist.']);
        }
        if ($this->isExecuted($do->TransactionNo)) {
            return redirect()->route('do.show', $do->id)->withErrors(['Delivery Order has already been executed and cannot be edited.']);
        }

        $so = TransSalesOrderHD::where('TransactionNo', $do->ReffNumber)->first();
        $details = $this->buildViewDetails($do, $so);
        $revData = $this->getRev();
        $options = DocPrint::where('ModuleCode', 'DO')
            ->where('TypeStr', 'print')
            ->get();

        return view('sales.do.show', compact('do', 'so', 'details', 'options', 'revData'));
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $do = TransDeliveryOrderHD::where('id', $id)->first();
        if (!$do) {
            return redirect()->route('do')->withErrors(['Delivery Order does not exist.']);
        }

        $so = TransSalesOrderHD::where('TransactionNo', $do->ReffNumber)->first();
        $customer = $so->CustomerID . ($so->customer->CustomerName ? ' - ' . $so->customer->CustomerName : '');
        $customerId = $so->customer->id;
        $reffId = $so->id;
        $details = $this->buildViewDetails($do, $so);
        $revData = $this->getRev();

        return view('sales.do.edit', compact('do', 'reffId', 'customer', 'customerId', 'details', 'revData'));
    }

    public function loadSO(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $customer = MsCustomer::where('id', $request->get('customer'))->first();

            $transactions = TransSalesOrderHD::where('Closed', 0)
                ->where('CustomerID', $customer->CustomerID);

            if ($request->get('search')) {
                $transactions->where('TransactionNo', 'like', '%' . $request->query('search') . '%');
            }

            $data = [];
            foreach ($transactions->get() as $transaction) {
                $data[] = [
                    'id' => $transaction->id,
                    'text' => $transaction->TransactionNo,
                ];
            }

            if ($request->get('inv')) {
                $so = TransSalesOrderHD::where('id', $request->get('inv'))->first();

                if ($so->Closed == 1 && $so->customer->id == $request->get('customer')) {
                    $data[] = [
                        'id' => $so->id,
                        'text' => $so->TransactionNo,
                    ];
                }
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getAddress(Request $request)
    {
        $this->guardAdvancedAccess();

        if ($request->filled('shipment')) {
            $shipment = MsCustomerShipment::where('CustomerID', $request->get('id'))
                ->where('Shipment', $request->get('shipment'))
                ->first();

            return response([
                'address' => $shipment?->Address,
            ]);
        }

        $so = TransSalesOrderHD::where('id', $request->get('id'))->first();

        return response([
            'address' => $so?->ShipmentAddress,
        ]);
    }

    public function getSODetail(Request $request)
    {
        $this->guardAdvancedAccess();

        try {
            $so = TransSalesOrderHD::where('id', $request->get('id'))->firstOrFail();
            $existingDo = null;
            $revCount = $so->RevCount;

            if ($request->input('doID') && $request->input('id') == $request->input('prevSO')) {
                $existingDo = TransDeliveryOrderHD::where('TransactionNo', $request->input('doID'))->first();
                if ($existingDo) {
                    $revCount = $existingDo->RevCount;
                }
            }

            $data = [];
            $selectedData = [];

            foreach ($so->details as $detail) {
                $unit2 = MsPartUnit::where('PartID', $detail->PartID)
                    ->where('UnitID2', $detail->UnitID)
                    ->first();

                $existingDetails = collect();
                if ($existingDo) {
                    $existingDetails = TransDeliveryOrderDT::where('TransactionNo', $existingDo->TransactionNo)
                        ->where('PartID', $detail->PartID)
                        ->where('Sequence', $detail->Sequence)
                        ->get();
                }

                $qtyConverted = (float) $detail->Qty;
                $existingQty = $existingDetails->sum('Qty');
                $qtyRemaining = $qtyConverted - $detail->QtySent + $existingQty;

                if ($qtyRemaining <= 0) {
                    continue;
                }

                $baseRow = [
                    'id' => $detail->PartID . '|' . $detail->Sequence,
                    'PartID' => $detail->PartID,
                    'PartName' => $detail->part->PartName,
                    'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                    'Sequence' => $detail->Sequence,
                    'UnitID' => $detail->UnitID,
                    'UnitID1' => $unit2->UnitID1 ?? $detail->UnitID,
                    'Price' => $this->getSalesDetailPrice($detail),
                    'Qty' => $qtyConverted,
                    'QtyRemaining' => $qtyRemaining,
                    'QtyDeliver' => 0,
                    'division' => $detail->DivisionID,
                    'warehouse' => null,
                    'BatchNo' => null,
                    'SerialNo' => null,
                    'ExpDate' => null,
                    'BIN' => null,
                    'LOC' => null,
                    'Stock' => 0,
                    'rev' => $this->getRevisionValues($so->RevCount, $detail),
                ];

                $data[] = $baseRow;

                foreach ($existingDetails as $existingDetail) {
                    $selectedRow = $baseRow;
                    $selectedRow['QtyDeliver'] = $existingDetail->Qty;
                    $selectedRow['warehouse'] = $existingDetail->WarehouseID;
                    $selectedRow['BatchNo'] = $this->stockValueForPayload($existingDetail->BatchNo);
                    $selectedRow['SerialNo'] = $this->stockValueForPayload($existingDetail->SerialNo);
                    $selectedRow['ExpDate'] = $this->stockValueForPayload($existingDetail->ExpDate ? Carbon::parse($existingDetail->ExpDate)->format('Y-m-d') : null);
                    $selectedRow['BIN'] = $this->stockValueForPayload($existingDetail->BIN);
                    $selectedRow['LOC'] = $this->stockValueForPayload($existingDetail->LOC);
                    $selectedRow['rev'] = $this->getRevisionValues($revCount, $existingDetail);
                    $selectedData[] = $selectedRow;
                }
            }

            return response([
                'status' => 'success',
                'rev' => $revCount,
                'data' => $data,
                'selected_data' => $selectedData,
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
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_DeliveryOrderHD,TransactionNo',
            'TransactionDate' => 'required',
            'ExpiredDate' => 'required',
            'ETA' => 'required',
            'CustomerID' => 'required',
            'ReffNumber' => 'required',
            'VehicleID' => 'nullable|string',
            'DriverID' => 'nullable|string',
            'ShipmentAddress' => 'required',
            'Notes' => 'nullable|string',
        ], [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $so = TransSalesOrderHD::where('id', $request->input('ReffNumber'))->first();
            if (!$so) {
                throw new \Exception('Sales Order does not exist.');
            }

            $this->validateDateAndQty($request, $so);
            $id = $this->resolveTransactionNo($request);

            TransDeliveryOrderHD::create($this->buildHeaderPayload($request, $so, $id));
            TransDeliveryOrderAmount::create([
                'TransactionNo' => $id,
                'Amount' => $request->input('SubTotal') ?? 0,
            ]);

            $this->insertDeliveryRows($request, $so, $id);
            $this->checkOutstanding($so->TransactionNo);

            DB::commit();
            clear_form_preservation('sales_do_advanced_add');

            return redirect()->route('do')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Delivery Order successfully added!',
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
            'TransactionDate' => 'required',
            'ExpiredDate' => 'required',
            'ETA' => 'required',
            'CustomerID' => 'required',
            'ReffNumber' => 'required',
            'VehicleID' => 'nullable|string',
            'DriverID' => 'nullable|string',
            'ShipmentAddress' => 'required',
            'Notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $this->mergeDetailsJsonIntoRequest($request);
            $do = TransDeliveryOrderHD::where('TransactionNo', $request->input('id'))->first();
            if (!$do) {
                throw new \Exception('Delivery Order does not exist.');
            }
            if ($this->isExecuted($do->TransactionNo)) {
                throw new \Exception('Delivery Order has already been executed and cannot be edited.');
            }

            $so = TransSalesOrderHD::where('id', $request->input('ReffNumber'))->first();
            if (!$so) {
                throw new \Exception('Sales Order does not exist.');
            }

            $this->validateDateAndQty($request, $so);
            $oldSoNumber = $do->ReffNumber;
            $this->reverseExistingRows($do);

            $do->update($this->buildHeaderPayload($request, $so, $do->TransactionNo, false));

            TransDeliveryOrderDT::where('TransactionNo', $do->TransactionNo)->delete();
            TransDeliveryOrderAmount::where('TransactionNo', $do->TransactionNo)->delete();
            TransDeliveryOrderAmount::create([
                'TransactionNo' => $do->TransactionNo,
                'Amount' => $request->input('SubTotal') ?? 0,
            ]);

            $this->insertDeliveryRows($request, $so, $do->TransactionNo);
            if ($oldSoNumber !== $so->TransactionNo) {
                $this->checkOutstanding($oldSoNumber);
            }
            $this->checkOutstanding($so->TransactionNo);

            DB::commit();

            return redirect()->route('do')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Delivery Order successfully updated!',
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
            $do = TransDeliveryOrderHD::where('id', $id)->first();
            if (!$do) {
                throw new \Exception('Delivery Order does not exist.');
            }
            if ($this->isExecuted($do->TransactionNo)) {
                throw new \Exception('Delivery Order has already been executed and cannot be deleted.');
            }

            $soNumber = $do->ReffNumber;
            $this->reverseExistingRows($do);

            TransJournalDT::where('TransactionNo', $do->TransactionNo)->delete();
            TransJournalHD::where('TransactionNo', $do->TransactionNo)->delete();
            TransDeliveryOrderAmount::where('TransactionNo', $do->TransactionNo)->delete();
            TransDeliveryOrderDT::where('TransactionNo', $do->TransactionNo)->delete();
            $do->delete();

            $this->checkOutstanding($soNumber);

            DB::commit();
            return response(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();

            return response(['status' => 'failed']);
        }
    }

    private function buildViewDetails(TransDeliveryOrderHD $do, ?TransSalesOrderHD $so): array
    {
        if (!$so) {
            return [];
        }

        $details = [];
        foreach ($do->details as $detail) {
            $soDetail = TransSalesOrderDT::where('TransactionNo', $so->TransactionNo)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->first();

            if (!$soDetail) {
                continue;
            }

            $unit2 = MsPartUnit::where('PartID', $soDetail->PartID)
                ->where('UnitID2', $soDetail->UnitID)
                ->first();

            $qtyConverted = (float) $soDetail->Qty;
            $qtyRemaining = $qtyConverted - $soDetail->QtySent + $detail->Qty;

            $details[] = [
                'id' => $detail->PartID . '|' . $detail->Sequence,
                'PartID' => $detail->PartID,
                'PartName' => $detail->part->PartName,
                'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                'Sequence' => $detail->Sequence,
                'UnitID' => $soDetail->UnitID,
                'UnitID1' => $unit2->UnitID1 ?? $soDetail->UnitID,
                'Price' => $this->getSalesDetailPrice($soDetail),
                'Qty' => $qtyConverted,
                'QtyRemaining' => $qtyRemaining,
                'QtyDeliver' => $detail->Qty,
                'QtyOriginal' => $detail->Qty,
                'division' => $detail->DivisionID,
                'warehouse' => $detail->WarehouseID,
                'BatchNo' => $this->stockValueForPayload($detail->BatchNo),
                'SerialNo' => $this->stockValueForPayload($detail->SerialNo),
                'ExpDate' => $this->stockValueForPayload($detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null),
                'BIN' => $this->stockValueForPayload($detail->BIN),
                'LOC' => $this->stockValueForPayload($detail->LOC),
                'Stock' => 0,
                'rev' => $this->getRevisionValues($do->RevCount, $detail),
            ];
        }

        return $details;
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
            'QtyDeliver' => [],
            'warehouse' => [],
            'division' => [],
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
            $qtyDeliver = (float) ($detail['QtyDeliver'] ?? 0);
            if ($qtyDeliver <= 0) {
                continue;
            }

            $merged['Sequence'][] = $detail['Sequence'] ?? null;
            $merged['PartID'][] = $detail['PartID'] ?? null;
            $merged['PartName'][] = $detail['PartName'] ?? null;
            $merged['UnitID'][] = $detail['UnitID1'] ?? ($detail['UnitID'] ?? null);
            $merged['Qty'][] = $detail['Qty'] ?? 0;
            $merged['QtyRemaining'][] = $detail['QtyRemaining'] ?? 0;
            $merged['QtyDeliver'][] = $qtyDeliver;
            $merged['warehouse'][] = $detail['warehouse'] ?? null;
            $merged['division'][] = $detail['division'] ?? null;
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

    private function validateDateAndQty(Request $request, TransSalesOrderHD $so): void
    {
        $date = Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'));
        $soDate = Carbon::parse($so->TransactionDate);
        if ($date->lessThan($soDate)) {
            throw new \Exception("Transaction Date must not be earlier than Sales Order's date!");
        }

        if (array_sum($request->input('QtyDeliver', [])) == 0) {
            throw new \Exception('You need to deliver at least one item!');
        }

        $deliveredByDetail = [];
        $remainingByDetail = [];

        foreach ($request->input('QtyDeliver', []) as $i => $qtyDeliver) {
            $key = ($request->input('PartID')[$i] ?? '') . '|' . ($request->input('Sequence')[$i] ?? '');
            $deliveredByDetail[$key] = ($deliveredByDetail[$key] ?? 0) + (float) $qtyDeliver;
            $remainingByDetail[$key] = (float) ($request->input('QtyRemaining')[$i] ?? 0);

            if ($qtyDeliver > ($request->input('QtyRemaining')[$i] ?? 0)) {
                throw new \Exception('Delivery amount must not exceed Remaining amount!');
            }
        }

        foreach ($deliveredByDetail as $key => $qtyDeliver) {
            if ($qtyDeliver > ($remainingByDetail[$key] ?? 0)) {
                throw new \Exception('Total delivery amount must not exceed Remaining amount!');
            }
        }
    }

    private function buildHeaderPayload(Request $request, TransSalesOrderHD $so, string $transactionNo, bool $isCreate = true): array
    {
        $payload = [
            'TransactionNo' => $transactionNo,
            'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
            'ExpiredDate' => $this->generalService->formatDate($request->input('ExpiredDate')),
            'ReffSource' => 'SO',
            'ReffNumber' => $so->TransactionNo,
            'ETA' => $this->generalService->formatDate($request->input('ETA')),
            'VehicleID' => $this->generalService->nullableDetailValue($request->input('VehicleID')),
            'DriverID' => $this->generalService->nullableDetailValue($request->input('DriverID')),
            'ShipmentAddress' => $request->input('ShipmentAddress'),
            'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
            'LastUpdateBy' => Auth::user()->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
            'RevCount' => $request->input('rev') ?? 0,
        ];

        if ($isCreate) {
            $payload['CreatedBy'] = Auth::user()->UserID;
            $payload['EntryTime'] = date('Y-m-d H:i:s');
            $payload['IsAuto'] = $request->input('automatic') ?? 0;
            $payload['LastDigit'] = $request->input('_last_digit');
        }

        return $payload;
    }

    private function insertDeliveryRows(Request $request, TransSalesOrderHD $so, string $transactionNo): void
    {
        $requestedStock = [];

        foreach ($request->input('PartID', []) as $i => $part) {
            $qtyDeliver = $request->input('QtyDeliver')[$i] ?? null;
            if ($qtyDeliver == null || $qtyDeliver <= 0) {
                continue;
            }

            $sequence = $request->input('Sequence')[$i];
            $detail = TransSalesOrderDT::where('TransactionNo', $so->TransactionNo)
                ->where('PartID', $part)
                ->where('Sequence', $sequence)
                ->first();

            if (!$detail) {
                throw new \Exception('Sales Order detail does not exist.');
            }

            $withSerialNo = (int) ($detail->part->WithSerialNo ?? 0) === 1;
            $stockFilters = $this->getSubmittedStockFilters($request, $i, $withSerialNo);
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);

            $warehouseId = $this->generalService->nullableDetailValue($request->input('warehouse')[$i] ?? null);
            if ($warehouseId === null) {
                throw new \Exception("Warehouse is required for Part {$part}.");
            }

            $stockKey = implode('|', [
                $part,
                $warehouseId,
                $stockAttributes['BatchNo'] ?? '__NULL__',
            ]);
            $requestedStock[$stockKey] = ($requestedStock[$stockKey] ?? 0) + (float) $qtyDeliver;

            $availableStock = $this->calculateInstructionWarehouseStock(
                $part,
                $warehouseId,
                $stockAttributes['BatchNo'] ?? null
            );

            if ($requestedStock[$stockKey] > $availableStock) {
                throw new \Exception($this->stockNotEnoughMessage($part, $warehouseId, $stockAttributes));
            }

            TransSalesOrderDT::where('TransactionNo', $so->TransactionNo)
                ->where('PartID', $part)
                ->where('Sequence', $sequence)
                ->update([
                    'QtySent' => $detail->QtySent + $qtyDeliver,
                ]);

            $doRow = [
                'TransactionNo' => $transactionNo,
                'PartID' => $part,
                'Sequence' => $sequence,
                'UnitID' => $request->input('UnitID')[$i],
                'Qty' => $qtyDeliver,
                'Qty2' => $qtyDeliver,
                'UnitID2' => $request->input('UnitID')[$i],
                'WarehouseID' => $warehouseId,
                'DivisionID' => $detail->DivisionID,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
            ];

            for ($j = 1; $j <= $request->input('rev'); $j++) {
                $doRow['ItemRevDT' . str_pad($j, 2, '0', STR_PAD_LEFT)] = $request->input('rev' . $j)[$i];
            }

            TransDeliveryOrderDT::insert($doRow);
        }
    }

    private function reverseExistingRows(TransDeliveryOrderHD $do): void
    {
        foreach ($do->details as $detail) {
            $soDetail = TransSalesOrderDT::where('TransactionNo', $do->ReffNumber)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->first();

            if ($soDetail) {
                TransSalesOrderDT::where('TransactionNo', $do->ReffNumber)
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->update([
                        'QtySent' => $soDetail->QtySent - $detail->Qty,
                    ]);
            }
        }
    }

    private function serialStockDeltasFromExistingDetails(TransDeliveryOrderHD $do): array
    {
        $serialStockDeltas = [];
        foreach ($do->details as $detail) {
            $serialNo = $this->generalService->nullableDetailValue($detail->SerialNo);
            if ($serialNo === null) {
                continue;
            }

            $serialStockDeltas[] = [
                'warehouse_id' => $detail->WarehouseID,
                'part_id' => $detail->PartID,
                'serial_no' => $serialNo,
                'delta' => 0,
            ];
        }

        return $serialStockDeltas;
    }

    private function getSubmittedStockFilters(Request $request, int $index, bool $withSerialNo = true): array
    {
        return [
            'BatchNo' => $this->submittedStockFilterValue($request->input('BatchNo')[$index] ?? null),
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ];
    }

    private function submittedStockFilterValue(?string $value): ?string
    {
        return $this->generalService->nullableDetailValue($value);
    }

    private function submittedDateFilterValue(?string $value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);
        if ($value === null || $value === '__NULL__') {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    private function stockFiltersForInsert(array $stockFilters): array
    {
        $attributes = [];

        foreach ($stockFilters as $key => $value) {
            $attributes[$key] = $value === '__NULL__' ? null : $value;
        }

        return $attributes;
    }

    private function calculateCurrentStockForSubmittedAttributes(string $partId, string $warehouseId, array $stockFilters): float
    {
        return BukuStockHelper::calculateCurrentStock(
            $partId,
            $warehouseId,
            $stockFilters['BatchNo'] ?? null,
            $stockFilters['SerialNo'] ?? null,
            $stockFilters['ExpDate'] ?? null,
            $stockFilters['BIN'] ?? null,
            $stockFilters['LOC'] ?? null
        );
    }

    private function calculateInstructionWarehouseStock(string $partId, string $warehouseId, ?string $batchNo): float
    {
        $warehouseIds = $this->warehouseIdsWithDescendants($warehouseId);

        $query = BukuStock::where('PartID', $partId)
            ->whereIn('WarehouseID', $warehouseIds);

        if ($batchNo === null || $batchNo === '' || $batchNo === '__NULL__') {
            $query->whereNull('BatchNo');
        } else {
            $query->where('BatchNo', $batchNo);
        }

        return (float) ($query->sum('Qty') ?? 0);
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

    private function stockNotEnoughMessage(string $partId, string $warehouseId, array $stockAttributes): string
    {
        $details = [
            'Part' => $partId,
            'Warehouse' => $warehouseId,
            'Batch No' => $stockAttributes['BatchNo'] ?? null,
            'Serial No' => $stockAttributes['SerialNo'] ?? null,
            'Exp Date' => $stockAttributes['ExpDate'] ?? null,
            'BIN' => $stockAttributes['BIN'] ?? null,
            'LOC' => $stockAttributes['LOC'] ?? null,
        ];

        $detailText = collect($details)
            ->map(fn ($value, $label) => $label . ': ' . ($value === null || $value === '' ? '(Empty)' : $value))
            ->implode(', ');

        return 'Stock is not enough for selected stock details. ' . $detailText . '.';
    }

    private function stockValueForPayload($value): ?string
    {
        return $value === null || $value === '' ? '__NULL__' : (string) $value;
    }

    private function nullableSubmittedDate(?string $value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);
        if ($value === null || $value === '__NULL__') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    private function resolveTransactionNo(Request $request): string
    {
        if (!$request->input('automatic')) {
            $request->merge(['_last_digit' => null]);
            return trim($request->input('TransactionNo'));
        }

        $masterAuto = MsAutoNumber::find('1');
        $checkLast = TransDeliveryOrderHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
            ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
            ->orderBy('LastDigit', 'desc')
            ->first();

        $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;
        $id = $masterAuto->Sales16 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
            . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
            . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

        if (TransDeliveryOrderHD::where('TransactionNo', $id)->first()) {
            throw new \Exception('Transaction No has already been taken!');
        }

        $request->merge(['_last_digit' => $digit]);
        return $id;
    }

    private function getSalesDetailPrice(TransSalesOrderDT $detail): float
    {
        $price = ($detail->UnitPrice - $detail->Discount) / $detail->Conversion;
        if ($detail->parent->VAT == 'I') {
            $vat = $detail->part->VAT2;
            $temp = $price / (1 + ($vat / 100));
            $tax = $temp * $vat / 100;
            $price = $price - $tax;
        }

        return (float) $price;
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
        return $this->buildRevisionAliases('SALES');
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

    private function checkOutstanding(string $soNumber): void
    {
        $so = TransSalesOrderHD::where('TransactionNo', $soNumber)->first();
        if (!$so || ($so->ClosingReason != null && $so->ClosingReason != '')) {
            return;
        }

        $outstanding = false;
        foreach ($so->details as $detail) {
            if ($this->decimalGreaterThan((float) $detail->Qty, (float) $detail->QtySent)) {
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

    private function decimalGreaterThan($left, $right): bool
    {
        return round((float) $left, 6) - round((float) $right, 6) > 0.000001;
    }

    private function isExecuted(string $transactionNo): bool
    {
        return BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', 'DELIVERY_ORDER')
            ->exists();
    }

    private function rebuildJournal(string $transactionNo): void
    {
        try {
            TransJournalDT::where('TransactionNo', $transactionNo)->delete();
            TransJournalHD::where('TransactionNo', $transactionNo)->delete();
            DB::select("exec sp_jurnal_delivery_order @Transactionno ='" . $transactionNo . "'");
        } catch (\Exception $exception) {
            Log::channel('stored_procedures')->error($exception);
        }
    }

    private function guardAdvancedAccess(): void
    {
    }
}
