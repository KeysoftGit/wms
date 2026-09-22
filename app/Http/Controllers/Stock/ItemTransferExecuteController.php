<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\BukuStockHelper;
use App\Models\DocPrint;
use App\Services\WarehouseAccessCriteria;
use App\Models\BukuStock;
use App\Models\MsWarehouse;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MsPart;
use App\Models\MsUser;
use App\Models\MsEmployee;
use App\Models\MsPartUnit;
use App\Models\TransItemTransferRequestHD;
use App\Models\TransItemTransferRequestDT;
use App\Models\TransItemTransferExecuteHD;
use App\Models\TransItemTransferExecuteDT;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ItemTransferExecuteController extends Controller
{
    public function index()
    {
        return view('stock.transfer_execute.index');
    }

    public function datatable(Request $request)
    {
        $data = TransItemTransferExecuteHD::withCount('receives');
        WarehouseAccessCriteria::applyRelation($data, 'requestHD', 'WarehouseIDFrom');

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
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Show" href="' . route('transfer_execute.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if ($row->isEditable) {
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer_execute.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Edit" href="' . route('transfer_execute.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer_execute.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('transfer_execute.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        $requests = TransItemTransferRequestHD::orderBy('TransactionNo', 'desc');
        WarehouseAccessCriteria::apply($requests, 'WarehouseIDFrom');
        $this->onlyRequestsWithRemainingQuantity($requests);
        $requests = $requests->get();

        return view('stock.transfer_execute.add', compact('requests'));
    }

    public function getRequestDetails(Request $request)
    {
        $requestNo = $request->get('requestNo');
        $headerQuery = TransItemTransferRequestHD::with(['details.part', 'details.unit', 'warehouseFrom', 'warehouseTo', 'staffFrom'])
            ->where('TransactionNo', $requestNo);
        WarehouseAccessCriteria::apply($headerQuery, 'WarehouseIDFrom');
        $header = $headerQuery->first();

        if (!$header) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        // Add staff information for the frontend
        if ($header->staffFrom) {
            $header->staff_from_name = $header->staffFrom->EmployeeID . ' - ' . $header->staffFrom->EmployeeName;
            $header->StaffInChargeIDFrom = $header->StaffInChargeIDFrom; // Ensure it's explicitly included
        }

        // Calculate remaining qty for each detail and get conversion
        foreach ($header->details as $dt) {
            $executedQty = TransItemTransferExecuteDT::whereHas('parent', function ($q) use ($requestNo) {
                $q->where('RequestNo', $requestNo);
            })->where('Sequence', $dt->Sequence)->sum('Qty');

            $dt->RemainingQty = $dt->Qty - $executedQty;

            // Get conversion factor
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('UnitID2', $dt->UnitID)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;
        }

        return response()->json($header);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_ItemTransferExecuteHD,TransactionNo',
            'TransactionDate' => 'required',
            'RequestNo' => 'required',
            'StaffInChargeFrom' => 'required',
            "part" => "required|array|min:1"
        ]);

        $sequence_req = $request->input('sequence');
        $part = $request->input('part');
        $unit = $request->input('unit');
        $qty = $request->input('qty');
        $dimension = $request->input('dimension');
        $cartoon = $request->input('cartoon');

        $requestNo = $request->input('RequestNo');
        $requestHDQuery = TransItemTransferRequestHD::where('TransactionNo', $requestNo);
        WarehouseAccessCriteria::apply($requestHDQuery, 'WarehouseIDFrom');
        $requestHD = $requestHDQuery->first();

        if (!$requestHD) {
            return redirect()->back()->withInput()->withErrors([
                'The selected request is unavailable or its target warehouse is not assigned to the logged-in user.'
            ]);
        }

        foreach ($part as $i => $p) {
            if ($qty[$i] <= 0) continue;

            $partId = $p;
            $unitId = $unit[$i];
            $sequence = $sequence_req[$i];

            $requestedItem = TransItemTransferRequestDT::where('TransactionNo', $requestNo)
                ->where('Sequence', $sequence)
                ->first();

            $executedQty = TransItemTransferExecuteDT::whereHas('parent', function ($q) use ($requestNo) {
                $q->where('RequestNo', $requestNo);
            })->where('Sequence', $sequence)->sum('Qty');

            $remainingQty = $requestedItem->Qty - $executedQty;

            if ($qty[$i] > $remainingQty) {
                return redirect()->back()->withInput()->withErrors(["Quantity for Part ID {$partId} exceeds the limit (Remaining request quantity: {$remainingQty})."]);
            }
        }

        // Filter: Only keep indices where qty > 0
        $validIndices = [];
        foreach ($qty as $i => $q) {
            if ($q > 0) {
                $validIndices[] = $i;
            }
        }

        if (count($validIndices) == 0) {
            return redirect()->back()->withInput()->withErrors(['Please input at least one item with Qty > 0']);
        }

        DB::beginTransaction();
        try {
            if ($request->input('automatic')) {
                $prefix = "ITE";
                $transactionDate = \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'));
                $count = TransItemTransferExecuteHD::whereMonth('TransactionDate', $transactionDate->format('m'))
                    ->whereYear('TransactionDate', $transactionDate->format('Y'))
                    ->count();
                $digit = $count + 1;

                do {
                    $id = $prefix . '/' . $transactionDate->format('Y')
                        . '/' . $transactionDate->format('m')
                        . '/' . str_pad($digit, 4, "0", STR_PAD_LEFT);

                    $checkExist = TransItemTransferExecuteHD::where('TransactionNo', $id)->first();

                    if ($checkExist) {
                        $digit++;
                    }
                } while ($checkExist);
            } else {
                $id = trim($request->input('TransactionNo'));
            }

            TransItemTransferExecuteHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'RequestNo' => $request->input('RequestNo'),
                'StaffInChargeFrom' => $request->input('StaffInChargeFrom'),
                'CreatedBy' => Auth::user()->UserID,
                'LastUpdateBy' => Auth::user()->UserID,
            ]);

            foreach ($validIndices as $i) {
                $sequence = $sequence_req[$i];

                TransItemTransferExecuteDT::create([
                    'TransactionNo' => $id,
                    'PartID' => $part[$i],
                    'UnitID' => $unit[$i],
                    'Qty' => $qty[$i],
                    'Dimension' => $dimension[$i] ?? null,
                    'CartoonNo' => $cartoon[$i] ?? null,
                    'Sequence' => $sequence,
                ]);

                // Get conversion factor
                $partUnit = MsPartUnit::where('PartID', $part[$i])->where('UnitID2', $unit[$i])->first();
                if (!$partUnit) {
                    throw new Exception('Unit ID for this part not found');
                }
                $lowestUnitId = $partUnit->UnitID1;
                $conversion = $partUnit->Conversion;
                $totalQty = $qty[$i] * $conversion;

                $availableStock = BukuStockHelper::calculateCurrentStock($part[$i], $requestHD->WarehouseIDFrom);

                if ($totalQty > $availableStock) {
                    throw new \Exception('Transfer amount must not exceed current stock on the source warehouse! (Available: ' . $availableStock . ')');
                }

                // Stock OUT from WarehouseFrom (Save TotalQty)
                BukuStock::create([
                    'TransactionNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $part[$i],
                    'WarehouseID' => $requestHD->WarehouseIDFrom,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalQty * -1,
                    'TransactionType' => 'ITEMTRANSFER_EXECUTE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                BukuStock::create([
                    'TransactionNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $part[$i],
                    'WarehouseID' => $id,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalQty,
                    'TransactionType' => 'ITEMTRANSFER_EXECUTE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

            }

            DB::commit();

            clear_form_preservation('stock_transfer_execute_add');

            return redirect()->route('transfer_execute')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Item Transfer Execute successfully added!'
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
        $transfer = TransItemTransferExecuteHD::with(['details.part', 'details.unit', 'requestHD', 'staffInChargeFrom'])->where('id', $id)->firstOrFail();
        foreach ($transfer->details as $dt) {
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('UnitID2', $dt->UnitID)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;
        }
        $options = DocPrint::where('ModuleCode', 'ITE')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.transfer_execute.show', compact('transfer', 'options'));
    }

    public function edit($id)
    {
        $transfer = TransItemTransferExecuteHD::with(['details.part', 'details.unit'])->where('id', $id)->firstOrFail();

        if (!$transfer->isEditable) {
            return redirect()->route('transfer_execute')->with([
                'type' => 'danger',
                'icon' => 'fa fa-fw fa-circle-xmark',
                'message' => 'This transaction is locked and cannot be edited because a Receive has been created!'
            ]);
        }

        foreach ($transfer->details as $dt) {
            $partUnit = MsPartUnit::where('PartID', $dt->PartID)->where('UnitID2', $dt->UnitID)->first();
            $dt->Conversion = $partUnit ? $partUnit->Conversion : 1;
        }

        $requests = TransItemTransferRequestHD::orderBy('TransactionNo', 'desc');
        WarehouseAccessCriteria::apply($requests, 'WarehouseIDFrom');
        $requests = $requests->get();

        return view('stock.transfer_execute.edit', compact('transfer', 'requests'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required',
            'TransactionDate' => 'required',
            'StaffInChargeFrom' => 'required',
            "part" => "required|array|min:1"
        ]);

        $hd = TransItemTransferExecuteHD::where('TransactionNo', $request->input('TransactionNo'))->firstOrFail();
        $requestNo = $hd->RequestNo;

        $sequence_req = $request->input('sequence');
        $part = $request->input('part');
        $unit = $request->input('unit');
        $qty = $request->input('qty');

        // Validate each item directly against remaining qty
        foreach ($part as $i => $p) {
            if ($qty[$i] <= 0) continue;

            $partId = $p;
            $unitId = $unit[$i];
            $sequence = $sequence_req[$i];

            $requestedItem = TransItemTransferRequestDT::where('TransactionNo', $requestNo)
                ->where('Sequence', $sequence)
                ->first();

            $executedQty = TransItemTransferExecuteDT::whereHas('parent', function ($q) use ($requestNo, $hd) {
                $q->where('RequestNo', $requestNo)
                    ->where('TransactionNo', '!=', $hd->TransactionNo);
            })->where('Sequence', $sequence)->sum('Qty');

            $remainingQty = $requestedItem->Qty - $executedQty;

            if ($qty[$i] > $remainingQty) {
                return redirect()->back()->withInput()->withErrors(["Quantity for Part ID {$partId} exceeds the limit (Remaining request quantity: {$remainingQty})."]);
            }
        }

        DB::beginTransaction();
        try {

            if (!$hd->isEditable) {
                return redirect()->route('transfer_execute')->with([
                    'type' => 'danger',
                    'icon' => 'fa fa-fw fa-circle-xmark',
                    'message' => 'This transaction is locked and cannot be updated!'
                ]);
            }

            // 1. Reverse Previous Stock (Only from WarehouseFrom)
            $requestHD = TransItemTransferRequestHD::where('TransactionNo', $hd->RequestNo)->first();
            $oldDetails = TransItemTransferExecuteDT::where('TransactionNo', $hd->TransactionNo)->get();

            // Delete old details and stock logs
            BukuStock::where('TransactionNo', $hd->TransactionNo)->whereIn('TransactionType', ['ITEMTRANSFER_EXECUTE'])->delete();
            TransItemTransferExecuteDT::where('TransactionNo', $hd->TransactionNo)->delete();

            // 2. Update Header
            $hd->update([
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'StaffInChargeFrom' => $request->input('StaffInChargeFrom'),
                'LastUpdateBy' => Auth::user()->UserID,
            ]);

            // 3. Insert New Details & Update Stock
            $part = $request->input('part');
            $unit = $request->input('unit');
            $qty = $request->input('qty');
            $dimension = $request->input('dimension');
            $cartoon = $request->input('cartoon');

            foreach ($part as $i => $partId) {
                $sequence = $sequence_req[$i];

                TransItemTransferExecuteDT::create([
                    'TransactionNo' => $request->input('TransactionNo'),
                    'PartID' => $partId,
                    'UnitID' => $unit[$i],
                    'Qty' => $qty[$i],
                    'Dimension' => $dimension[$i] ?? null,
                    'CartoonNo' => $cartoon[$i] ?? null,
                    'Sequence' => $sequence,
                ]);

                // Get new conversion factor
                $partUnit = MsPartUnit::where('PartID', $partId)->where('UnitID2', $unit[$i])->first();
                if (!$partUnit) {
                    throw new Exception('Unit ID for this part not found');
                }
                $conversion = $partUnit->Conversion;
                $lowestUnitId = $partUnit->UnitID1;
                $totalQty = $qty[$i] * $conversion;
                $availableStock = BukuStockHelper::calculateCurrentStock($part[$i], $requestHD->WarehouseIDFrom);

                if ($totalQty > $availableStock) {
                    throw new \Exception('Transfer amount must not exceed current stock on the source warehouse! (Available: ' . $availableStock . ')');
                }

                // Stock OUT (Save TotalQty)
                BukuStock::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $partId,
                    'WarehouseID' => $requestHD->WarehouseIDFrom,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalQty * -1,
                    'TransactionType' => 'ITEMTRANSFER_EXECUTE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                BukuStock::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $part[$i],
                    'WarehouseID' => $hd->TransactionNo,
                    'Sequence' => $sequence,
                    'UnitID' => $lowestUnitId,
                    'Qty' => $totalQty,
                    'TransactionType' => 'ITEMTRANSFER_EXECUTE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

            }

            DB::commit();

            return redirect()->route('transfer_execute')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Item Transfer Execute successfully updated!'
                ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong! ' . $exception->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $hd = TransItemTransferExecuteHD::where('id', $id)->firstOrFail();

            if (!$hd->isEditable) {
                return response()->json(['message' => 'This transaction is locked and cannot be deleted!'], 400);
            }

            // Reverse stock
            $requestHD = TransItemTransferRequestHD::where('TransactionNo', $hd->RequestNo)->first();
            $details = TransItemTransferExecuteDT::where('TransactionNo', $hd->TransactionNo)->get();

            BukuStock::where('TransactionNo', $hd->TransactionNo)->whereIn('TransactionType', ['ITEMTRANSFER_EXECUTE'])->delete();
            TransItemTransferExecuteDT::where('TransactionNo', $hd->TransactionNo)->delete();
            $hd->delete();

            DB::commit();
            return response()->json(['message' => 'Item Transfer Execute successfully deleted!']);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response()->json(['message' => 'Something went wrong!'], 500);
        }
    }

    private function onlyRequestsWithRemainingQuantity($query): void
    {
        $query->whereExists(function ($detailQuery) {
            $detailQuery->selectRaw('1')
                ->from('Trans_ItemTransferRequestDT as request_detail')
                ->whereColumn(
                    'request_detail.TransactionNo',
                    'Trans_ItemTransferRequestHD.TransactionNo'
                )
                ->whereRaw(
                    'request_detail.Qty > (
                        SELECT COALESCE(SUM(execute_detail.Qty), 0)
                        FROM Trans_ItemTransferExecuteDT AS execute_detail
                        INNER JOIN Trans_ItemTransferExecuteHD AS execute_header
                            ON execute_header.TransactionNo = execute_detail.TransactionNo
                        WHERE execute_header.RequestNo = Trans_ItemTransferRequestHD.TransactionNo
                            AND execute_detail.Sequence = request_detail.Sequence
                    )'
                );
        });
    }
}
