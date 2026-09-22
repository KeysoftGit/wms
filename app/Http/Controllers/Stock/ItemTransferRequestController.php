<?php

namespace App\Http\Controllers\Stock;

use App\Models\DocPrint;
use App\Services\WarehouseAccessCriteria;
use App\Models\ControlPanel;
use App\Models\MsWarehouse;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MsPart;
use App\Models\MsEmployee;
use App\Models\TransItemTransferRequestDT;
use App\Models\TransItemTransferRequestHD;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Exception;

class ItemTransferRequestController extends Controller
{
    public function index()
    {
        return view('stock.transfer_request.index');
    }

    public function datatable(Request $request)
    {
        $data = TransItemTransferRequestHD::with(['warehouseFrom', 'warehouseTo'])->withCount('executes');
        WarehouseAccessCriteria::apply($data, 'WarehouseIDTo');

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
            ->addColumn('WarehouseFromName', function ($row) {
                return $row->warehouseFrom->WarehouseName ?? $row->WarehouseIDFrom;
            })
            ->addColumn('WarehouseToName', function ($row) {
                return $row->warehouseTo->WarehouseName ?? $row->WarehouseIDTo;
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('transfer_request.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if ($row->is_editable) {
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer_request.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('transfer_request.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer_request.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('transfer_request.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        $warehouses = (
            ControlPanel::isEnabled('implement_user_warehouse_mapping')
                ? MsWarehouse::accessibleTo(auth()->user())
                : MsWarehouse::query()
        )->where('Active', 1)->get();
        $employees = MsEmployee::where('Active', 1)->get();
        return view('stock.transfer_request.add', compact('warehouses', 'employees'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_ItemTransferRequestHD,TransactionNo',
            'TransactionDate' => 'required',
            'ExpiredDate' => 'nullable',
            'WarehouseIDFrom' => 'required',
            'StaffInChargeIDFrom' => 'required',
            "WarehouseIDTo" => "required",
            "StaffInChargeIDTo" => "required",
            'NeedFor' => 'nullable|string',
            "part" => "required|array|min:1"
        ]);

        try {
            DB::beginTransaction();

            $this->validateWarehouseSelection($request);

            if ($request->input('WarehouseIDFrom') == $request->input('WarehouseIDTo')) {
                throw new Exception('Target warehouse must not be the same as source warehouse!');
            }

            // VALIDATION (Using Qty * Conversion)
            $part = $request->input('part');
            $conversion = $request->input('conversion');
            $qty = $request->input('qty');

            $checkData = [];
            foreach ($part as $i => $id) {
                $conv = (float)($conversion[$i] ?? 1);
                $q = (float)($qty[$i] ?? 0);
                $totalQtyReq = $q * $conv;

                if (array_key_exists($id, $checkData)) {
                    $checkData[$id]['qty'] += $totalQtyReq;
                } else {
                    $checkData[$id] = [
                        'qty' => $totalQtyReq
                    ];
                }
            }


            $transactionNo = $request->TransactionNo;
            if ($request->has('automatic')) {
                $transactionNo = MsAutoNumber::generate('ItemTransferRequest', 'Trans_ItemTransferRequestHD', 'TransactionNo', Carbon::createFromFormat('d/m/Y', $request->TransactionDate)->format('Y-m-d'));
            }

            $hd = TransItemTransferRequestHD::create([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => Carbon::createFromFormat('d/m/Y', $request->TransactionDate)->format('Y-m-d'),
                'ExpiredDate' => $request->ExpiredDate ? Carbon::createFromFormat('d/m/Y', $request->ExpiredDate)->format('Y-m-d') : null,
                'NeedFor' => $request->NeedFor,
                'WarehouseIDFrom' => $request->WarehouseIDFrom,
                'WarehouseIDTo' => $request->WarehouseIDTo,
                'StaffInChargeIDFrom' => $request->StaffInChargeIDFrom,
                'StaffInChargeIDTo' => $request->StaffInChargeIDTo,
            ]);

            foreach ($request->part as $key => $part) {
                TransItemTransferRequestDT::create([
                    'TransactionNo' => $transactionNo,
                    'PartID' => $part,
                    'UnitID' => $request->unit[$key],
                    'Qty' => $request->qty[$key], // Save original qty
                    'Sequence' => $key,
                ]);
            }

            DB::commit();
            clear_form_preservation('transfer_request_form_data');
            return redirect()->route('transfer_request')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-check',
                'message' => 'Item Transfer Request successfully added!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $request = TransItemTransferRequestHD::with(['details.part', 'details.unit', 'warehouseFrom', 'warehouseTo', 'staffFrom', 'staffTo'])->where('id', $id)->firstOrFail();
        $options = DocPrint::where('ModuleCode', 'ITR')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.transfer_request.show', compact('request', 'options'));
    }

    public function edit($id)
    {
        $request = TransItemTransferRequestHD::with('details')->where('id', $id)->firstOrFail();
        if (!$request->is_editable) {
            abort(403, 'This request has already been executed and cannot be edited.');
        }
        $warehouses = (
            ControlPanel::isEnabled('implement_user_warehouse_mapping')
                ? MsWarehouse::accessibleTo(auth()->user())
                : MsWarehouse::query()
        )->where('Active', 1)->get();
        $employees = MsEmployee::where('Active', 1)->get();
        return view('stock.transfer_request.edit', compact('request', 'warehouses', 'employees'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:Trans_ItemTransferRequestHD,id',
            'TransactionDate' => 'required',
            'ExpiredDate' => 'nullable',
            'WarehouseIDFrom' => 'required',
            'StaffInChargeIDFrom' => 'required',
            "WarehouseIDTo" => "required",
            "StaffInChargeIDTo" => "required",
            'NeedFor' => 'nullable|string',
            "part" => "required|array|min:1"
        ]);

        try {
            DB::beginTransaction();

            $this->validateWarehouseSelection($request);

            if ($request->input('WarehouseIDFrom') == $request->input('WarehouseIDTo')) {
                throw new Exception('Target warehouse must not be the same as source warehouse!');
            }

            $hd = TransItemTransferRequestHD::where('id', $request->id)->firstOrFail();
            if (!$hd->is_editable) {
                throw new \Exception('This request has already been executed and cannot be updated.');
            }

            // VALIDATION (Using Qty * Conversion)
            $part = $request->input('part');
            $conversion = $request->input('conversion');
            $qty = $request->input('qty');

            $checkData = [];
            foreach ($part as $i => $id) {
                $conv = (float)($conversion[$i] ?? 1);
                $q = (float)($qty[$i] ?? 0);
                $totalQtyReq = $q * $conv;

                if (array_key_exists($id, $checkData)) {
                    $checkData[$id]['qty'] += $totalQtyReq;
                } else {
                    $checkData[$id] = [
                        'qty' => $totalQtyReq
                    ];
                }
            }


            $hd = TransItemTransferRequestHD::where('id', $request->id)->firstOrFail();
            $hd->update([
                'TransactionDate' => Carbon::createFromFormat('d/m/Y', $request->TransactionDate)->format('Y-m-d'),
                'ExpiredDate' => $request->ExpiredDate ? Carbon::createFromFormat('d/m/Y', $request->ExpiredDate)->format('Y-m-d') : null,
                'NeedFor' => $request->NeedFor,
                'WarehouseIDFrom' => $request->WarehouseIDFrom,
                'WarehouseIDTo' => $request->WarehouseIDTo,
                'StaffInChargeIDFrom' => $request->StaffInChargeIDFrom,
                'StaffInChargeIDTo' => $request->StaffInChargeIDTo,
            ]);

            TransItemTransferRequestDT::where('TransactionNo', $hd->TransactionNo)->delete();

            foreach ($request->part as $key => $part) {
                TransItemTransferRequestDT::create([
                    'TransactionNo' => $hd->TransactionNo,
                    'PartID' => $part,
                    'UnitID' => $request->unit[$key],
                    'Qty' => $request->qty[$key], // Save original qty
                    'Sequence' => $key,
                ]);
            }

            DB::commit();

            return redirect()->route('transfer_request')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-check',
                'message' => 'Item Transfer Request successfully updated!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $hd = TransItemTransferRequestHD::where('id', $id)->firstOrFail();
            if (!$hd->is_editable) {
                return response([
                    'status' => 'error',
                    'message' => 'This request has already been executed and cannot be deleted.'
                ], 403);
            }

            TransItemTransferRequestDT::where('TransactionNo', $hd->TransactionNo)->delete();
            $hd->delete();

            DB::commit();

            return response([
                'status' => 'success',
                'message' => 'Item Transfer Request successfully deleted!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function validateWarehouseSelection(Request $request): void
    {
        $activeWarehouseIds = MsWarehouse::where('Active', 1)
            ->whereIn('WarehouseID', [
                $request->input('WarehouseIDFrom'),
                $request->input('WarehouseIDTo'),
            ])
            ->pluck('WarehouseID')
            ->all();

        if (!in_array($request->input('WarehouseIDFrom'), $activeWarehouseIds, true)) {
            throw new Exception('Source warehouse is invalid or inactive.');
        }

        if (!in_array($request->input('WarehouseIDTo'), $activeWarehouseIds, true)) {
            throw new Exception('Target warehouse is invalid or inactive.');
        }

        $allowedWarehouseIds = WarehouseAccessCriteria::allowedIds();
        if ($allowedWarehouseIds !== null
            && !in_array($request->input('WarehouseIDTo'), $allowedWarehouseIds, true)) {
            throw new Exception('Target warehouse must be assigned to the logged-in user.');
        }
    }
}
