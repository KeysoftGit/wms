<?php

namespace App\Http\Controllers\Stock;

use App\Models\DocPrint;
use App\Services\WarehouseAccessCriteria;
use App\Models\BukuStock;
use App\Models\MsAutoNumber;
use App\Models\MsPartUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MsWarehouse;
use App\Models\TransItemTransferExecuteDT;
use App\Models\TransItemTransferExecuteHD;
use App\Models\TransItemTransferReceiveHD;
use App\Models\TransItemTransferReceiveDT;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ItemTransferReceiveController extends Controller
{
    public function index()
    {
        return view('stock.transfer_receive.index');
    }

    public function datatable(Request $request)
    {
        $data = TransItemTransferReceiveHD::select('id', 'TransactionNo', 'TransactionDate', 'ExecuteNo', 'StaffInChargeTo');
        WarehouseAccessCriteria::applyRelation($data, 'executeHD.requestHD', 'WarehouseIDTo');

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        return DataTables::of($data)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Show" href="' . route('transfer_receive.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if (Auth::user()->hasAnyPermission(['admin', 'transfer_receive.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Edit" href="' . route('transfer_receive.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'transfer_receive.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('transfer_receive.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        // Only show executes that haven't been received yet
        $executes = TransItemTransferExecuteHD::whereDoesntHave('receives')
            ->orderBy('TransactionNo', 'desc');
        WarehouseAccessCriteria::applyRelation($executes, 'requestHD', 'WarehouseIDTo');
        $executes = $executes->get();

        return view('stock.transfer_receive.add', compact('executes'));
    }

    public function getExecuteDetails(Request $request)
    {
        $executeNo = $request->get('executeNo');
        $headerQuery = TransItemTransferExecuteHD::with(['details.part', 'details.unit', 'requestHD.warehouseTo', 'requestHD.staffTo'])
            ->whereDoesntHave('receives')
            ->where('TransactionNo', $executeNo);
        WarehouseAccessCriteria::applyRelation($headerQuery, 'requestHD', 'WarehouseIDTo');
        $header = $headerQuery->first();

        if (!$header) {
            return response()->json(['message' => 'Execute transaction not found or unavailable'], 404);
        }

        // Add staff information for the frontend
        if ($header->requestHD && $header->requestHD->staffTo) {
            $header->requestHD->staff_to_name = $header->requestHD->staffTo->EmployeeID . ' - ' . $header->requestHD->staffTo->EmployeeName;
        }

        foreach ($header->details as $dt) {
            // Get conversion factor
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('UnitID2', $dt->UnitID)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;
        }

        return response()->json($header);
    }

    private function getOrCreateAnomaliWarehouse($warehouseToID)
    {
        $targetWh = MsWarehouse::where('WarehouseID', $warehouseToID)->first();
        $anomaliWhID = 'ANM-' . $warehouseToID;

        $anomaliWh = MsWarehouse::where('WarehouseID', $anomaliWhID)->first();
        if (!$anomaliWh) {
            $anomaliWh = MsWarehouse::create([
                'WarehouseID' => $anomaliWhID,
                'WarehouseName' => 'Anomali ' . ($targetWh ? $targetWh->WarehouseName : $warehouseToID),
                'ParentID' => $warehouseToID,
                'Active' => 1,
                'WarehouseType' => $targetWh ? $targetWh->WarehouseType : null,
                'DivisionID' => $targetWh ? $targetWh->DivisionID : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Editable' => 0,
            ]);
        }

        return $anomaliWh;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_ItemTransferReceiveHD,TransactionNo',
            'TransactionDate' => 'required',
            'ExecuteNo' => 'required',
            'StaffInChargeTo' => 'required',
            "part" => "required|array|min:1"
        ]);

        DB::beginTransaction();
        try {
            $executeHDQuery = TransItemTransferExecuteHD::with('requestHD')
                ->whereDoesntHave('receives')
                ->where('TransactionNo', $request->input('ExecuteNo'));
            WarehouseAccessCriteria::applyRelation($executeHDQuery, 'requestHD', 'WarehouseIDTo');
            $executeHD = $executeHDQuery->first();

            if (!$executeHD) {
                throw new Exception(
                    'The selected execute is unavailable or its target warehouse is not assigned to the logged-in user.'
                );
            }

            if ($request->input('automatic')) {
                $prefix = "ITR-REC";
                $transactionDate = \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'));
                $count = TransItemTransferReceiveHD::whereMonth('TransactionDate', $transactionDate->format('m'))
                    ->whereYear('TransactionDate', $transactionDate->format('Y'))
                    ->count();
                $digit = $count + 1;

                do {
                    $id = $prefix . '/' . $transactionDate->format('Y')
                        . '/' . $transactionDate->format('m')
                        . '/' . str_pad($digit, 4, "0", STR_PAD_LEFT);

                    $checkExist = TransItemTransferReceiveHD::where('TransactionNo', $id)->first();

                    if ($checkExist) {
                        $digit++;
                    }
                } while ($checkExist);
            } else {
                $id = trim($request->input('TransactionNo'));
            }

            $warehouseToID = $executeHD->requestHD->WarehouseIDTo;
            $anomaliWh = $this->getOrCreateAnomaliWarehouse($warehouseToID);

            $transDate = \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d');

            TransItemTransferReceiveHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => $transDate,
                'ExecuteNo' => $request->input('ExecuteNo'),
                'StaffInChargeTo' => $request->input('StaffInChargeTo'),
                'WarehouseIDTo' => $warehouseToID,
                'CreatedBy' => Auth::user()->UserID,
                'LastUpdateBy' => Auth::user()->UserID,
            ]);

            $sequence_req = $request->input('sequence');
            $part = $request->input('part');
            $unit = $request->input('unit');
            $qty = $request->input('qty');
            $dimension = $request->input('dimension');
            $cartoon = $request->input('cartoon');

            foreach ($part as $i => $partId) {
                $sequence = $sequence_req[$i];
                // Get original execute qty
                $executeDT = TransItemTransferExecuteDT::where('TransactionNo', $request->input('ExecuteNo'))
                    ->where('Sequence', $sequence)
                    ->first();

                $executeQty = $executeDT ? $executeDT->Qty : 0;

                TransItemTransferReceiveDT::create([
                    'TransactionNo' => $id,
                    'PartID' => $partId,
                    'UnitID' => $unit[$i],
                    'Qty' => $qty[$i],
                    'Dimension' => $dimension[$i] ?? null,
                    'CartoonNo' => $cartoon[$i] ?? null,
                    'Sequence' => $sequence,
                ]);

                // Get conversion factor
                $partUnit = MsPartUnit::where('PartID', $partId)->where('UnitID2', $unit[$i])->first();
                if (!$partUnit) {
                    throw new Exception('Unit ID for this part not found');
                }
                $conversion = $partUnit->Conversion;
                $lowestUnitId = $partUnit->UnitID1;

                $totalExecuteQty = $executeQty * $conversion;
                $totalReceiveQty = $qty[$i] * $conversion;

                $qtyToWarehouse = 0;
                $qtyToAnomali = 0;
                $notes = '';

                if ($totalReceiveQty > $totalExecuteQty) {
                    // SURPLUS Case
                    $qtyToWarehouse = $totalExecuteQty; // Only receive what was recommended
                    $qtyToAnomali = $totalReceiveQty - $totalExecuteQty; // Excess goes to Anomali
                    $notes = 'Surplus Receive (Execute: ' . $totalExecuteQty . ', Receive: ' . $totalReceiveQty . ')';
                } else {
                    // SHORTAGE or EQUAL Case
                    $qtyToWarehouse = $totalReceiveQty; // Receive what is actually there
                    $qtyToAnomali = $totalExecuteQty - $totalReceiveQty; // Difference stays in Anomali
                    $notes = ($totalReceiveQty < $totalExecuteQty) ? 'Shortage Receive (Execute: ' . $totalExecuteQty . ', Receive: ' . $totalReceiveQty . ')' : '';
                }

                // 1. Stock OUT from Transit (ExecuteNo) - Always take out the FULL execute qty
                BukuStock::create([
                    'TransactionNo' => $id,
                    'TransactionDate' => $transDate,
                    'PartID' => $partId,
                    'WarehouseID' => $request->input('ExecuteNo'),
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalExecuteQty * -1,
                    'TransactionType' => 'ITEMTRANSFER_RECEIVE',
                    'Notes' => $notes,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // 2. Stock IN to WarehouseTo
                BukuStock::create([
                    'TransactionNo' => $id,
                    'TransactionDate' => $transDate,
                    'PartID' => $partId,
                    'WarehouseID' => $warehouseToID,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $qtyToWarehouse,
                    'TransactionType' => 'ITEMTRANSFER_RECEIVE',
                    'Notes' => $notes,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // 3. Stock IN to Anomali Warehouse (Always positive now)
                if ($qtyToAnomali != 0) {
                    BukuStock::create([
                        'TransactionNo' => $id,
                        'TransactionDate' => $transDate,
                        'PartID' => $partId,
                        'WarehouseID' => $anomaliWh->WarehouseID,
                        'Sequence' => $sequence,
                        'UnitID' => $lowestUnitId,
                        'Qty' => $qtyToAnomali,
                        'TransactionType' => 'ITEMTRANSFER_RECEIVE_ANOMALI',
                        'Notes' => $notes,
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            DB::commit();

            clear_form_preservation('stock_transfer_receive_add');

            return redirect()->route('transfer_receive')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Item Transfer Receive successfully added!'
                ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong! ' . $exception->getMessage()
            ]);
        }
    }

    public function edit($id)
    {
        $receive = TransItemTransferReceiveHD::with(['details.part', 'details.unit', 'staffInChargeTo', 'executeHD.requestHD', 'executeHD.details'])->where('id', $id)->firstOrFail();

        foreach ($receive->details as $dt) {
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('Sequence', $dt->Sequence)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;

            // Get original execute qty
            $executeDt = $receive->executeHD->details->where('PartID', $dt->PartID)->where('Sequence', $dt->Sequence)->first();
            $dt->MaxQty = $executeDt ? $executeDt->Qty : $dt->Qty;
        }

        return view('stock.transfer_receive.edit', compact('receive'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required',
            'TransactionDate' => 'required',
            'StaffInChargeTo' => 'required',
            "part" => "required|array|min:1"
        ]);

        DB::beginTransaction();
        try {
            $hd = TransItemTransferReceiveHD::with('details')->where('TransactionNo', $request->input('TransactionNo'))->firstOrFail();
            $executeHD = TransItemTransferExecuteHD::with('requestHD')->where('TransactionNo', $hd->ExecuteNo)->first();
            $warehouseToID = $executeHD->requestHD->WarehouseIDTo;
            $anomaliWh = $this->getOrCreateAnomaliWarehouse($warehouseToID);

            $transDate = \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d');

            // Delete old details and stock logs
            BukuStock::where('TransactionNo', $hd->TransactionNo)->delete();
            TransItemTransferReceiveDT::where('TransactionNo', $hd->TransactionNo)->delete();

            // 2. Update Header
            $hd->update([
                'TransactionDate' => $transDate,
                'StaffInChargeTo' => $request->input('StaffInChargeTo'),
                'LastUpdateBy' => Auth::user()->UserID,
            ]);

            // 3. Insert New Details & Update Stock
            $sequence_req = $request->input('sequence');
            $part = $request->input('part');
            $unit = $request->input('unit');
            $qty = $request->input('qty');
            $dimension = $request->input('dimension');
            $cartoon = $request->input('cartoon');

            foreach ($part as $i => $partId) {
                $sequence = $sequence_req[$i];
                // Get original execute qty
                $executeDT = TransItemTransferExecuteDT::where('TransactionNo', $hd->ExecuteNo)
                    ->where('Sequence', $sequence)
                    ->first();

                $executeQty = $executeDT ? $executeDT->Qty : 0;

                TransItemTransferReceiveDT::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'PartID' => $partId,
                    'UnitID' => $unit[$i],
                    'Qty' => $qty[$i],
                    'Dimension' => $dimension[$i] ?? null,
                    'CartoonNo' => $cartoon[$i] ?? null,
                    'Sequence' => $sequence,
                ]);

                // Get conversion factor
                $partUnit = MsPartUnit::where('PartID', $partId)->where('UnitID2', $unit[$i])->first();
                if (!$partUnit) {
                    throw new Exception('Unit ID for this part not found');
                }
                $conversion = $partUnit->Conversion;
                $lowestUnitId = $partUnit->UnitID1;

                $totalExecuteQty = $executeQty * $conversion;
                $totalReceiveQty = $qty[$i] * $conversion;

                $qtyToWarehouse = 0;
                $qtyToAnomali = 0;
                $notes = '';

                if ($totalReceiveQty > $totalExecuteQty) {
                    // SURPLUS Case
                    $qtyToWarehouse = $totalExecuteQty; // Only receive what was recommended
                    $qtyToAnomali = $totalReceiveQty - $totalExecuteQty; // Excess goes to Anomali
                    $notes = 'Surplus Receive (Execute: ' . $totalExecuteQty . ', Receive: ' . $totalReceiveQty . ')';
                } else {
                    // SHORTAGE or EQUAL Case
                    $qtyToWarehouse = $totalReceiveQty; // Receive what is actually there
                    $qtyToAnomali = $totalExecuteQty - $totalReceiveQty; // Difference stays in Anomali
                    $notes = ($totalReceiveQty < $totalExecuteQty) ? 'Shortage Receive (Execute: ' . $totalExecuteQty . ', Receive: ' . $totalReceiveQty . ')' : '';
                }

                // A. Stock OUT from Transit (ExecuteNo)
                BukuStock::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'TransactionDate' => $transDate,
                    'PartID' => $partId,
                    'WarehouseID' => $hd->ExecuteNo,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalExecuteQty * -1,
                    'TransactionType' => 'ITEMTRANSFER_RECEIVE',
                    'Notes' => $notes,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // B. Stock IN to WarehouseTo (Actual Received Qty)
                BukuStock::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'TransactionDate' => $transDate,
                    'PartID' => $partId,
                    'WarehouseID' => $warehouseToID,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $qtyToWarehouse,
                    'TransactionType' => 'ITEMTRANSFER_RECEIVE',
                    'Notes' => $notes,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // C. Stock IN to Anomali Warehouse
                if ($qtyToAnomali != 0) {
                    BukuStock::create([
                        'TransactionNo' => $hd->TransactionNo,
                        'TransactionDate' => $transDate,
                        'PartID' => $partId,
                        'WarehouseID' => $anomaliWh->WarehouseID,
                        'Sequence' => $sequence,
                        'UnitID' => $lowestUnitId,
                        'Qty' => $qtyToAnomali,
                        'TransactionType' => 'ITEMTRANSFER_RECEIVE_ANOMALI',
                        'Notes' => $notes,
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('transfer_receive')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Item Transfer Receive successfully updated!'
                ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong! ' . $exception->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $receive = TransItemTransferReceiveHD::with(['details.part', 'details.unit', 'executeHD', 'staffInChargeTo', 'warehouseTo'])->where('id', $id)->firstOrFail();
        foreach ($receive->details as $dt) {
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('UnitID2', $dt->UnitID)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;
        }
        $options = DocPrint::where('ModuleCode', 'ITRECEIVE')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.transfer_receive.show', compact('receive', 'options'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $hd = TransItemTransferReceiveHD::where('id', $id)->firstOrFail();

            BukuStock::where('TransactionNo', $hd->TransactionNo)->delete();
            TransItemTransferReceiveDT::where('TransactionNo', $hd->TransactionNo)->delete();
            $hd->delete();

            DB::commit();
            return response()->json(['message' => 'Item Transfer Receive successfully deleted!']);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response()->json(['message' => 'Something went wrong!'], 500);
        }
    }}
