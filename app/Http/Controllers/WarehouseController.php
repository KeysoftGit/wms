<?php

namespace App\Http\Controllers;

use App\Models\MsWarehouse;
use App\Models\MsAutoNumber;
use App\Models\MsDivision;
use App\Models\MsPart;
use App\Models\MsQR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('warehouse.index');
    }

    public function datatable(Request $request)
    {
        $data = MsWarehouse::select('id', 'WarehouseID', 'Ms_Warehouse.Active', 'WarehouseName', 'ParentID', 'StaffInChargeID', 'Location', 'Notes', 'created_at')
            ->with('parent', 'staff');

        return DataTables::of($data)
            ->editColumn('ParentID', fn ($row) => optional($row->parent)->WarehouseName ?? $row->ParentID)
            ->editColumn('StaffInChargeID', fn ($row) => optional($row->staff)->EmployeeName ?: $row->StaffInChargeID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('warehouse.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if (Auth::user()->hasAnyPermission(['admin', 'warehouse.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('warehouse.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'warehouse.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('warehouse.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->make(true);
    }

    public function show($id)
    {
        $warehouse = MsWarehouse::with(['parent', 'staff', 'division'])
            ->where('id', $id)
            ->firstOrFail();
        $qr = $this->findWarehouseQr($warehouse);

        return view('warehouse.show', compact('warehouse', 'qr'));
    }

    public function add()
    {

        if (Schema::hasColumn('Ms_Warehouse', 'DivisionID')) {
            $showDivisions = true;
            $divisionIds = MsWarehouse::whereNotNull('DivisionID')->pluck('DivisionID');
            $divisions = MsDivision::whereNotIn('DivisionID', $divisionIds)->get();
        } else {
            $showDivisions = false;
            $divisions = collect();
        }

        return view('warehouse.add', compact('showDivisions', 'divisions'));
    }


    public function store(Request $request)
    {

        $this->validate($request, [
            'WarehouseID' => 'required_without:automatic|string|max:50|unique:Ms_Warehouse,WarehouseID',
            'WarehouseName' => 'nullable|string|max:255',
        ], [
            'WarehouseID.unique' => 'Warehouse ID has already been taken!',
            'WarehouseID.max' => 'Warehouse ID maximum characters is 50!',
            'WarehouseName.max' => 'Warehouse Name maximum characters is 255!',
        ]);

        $masterAuto = MsAutoNumber::find("1");

        $digit = null;

        if ($request->input('automatic')) {
            $checkLast = MsWarehouse::where('IsAuto', 1)->orderBy('LastDigit', 'desc')->first();
            if (!$checkLast) {
                $digit = 1;
            } else {
                $digit = $checkLast->LastDigit + 1;
            }

            $id = $masterAuto->Master07 . str_pad($digit, 4, "0", STR_PAD_LEFT);
            $checkExist = MsWarehouse::where('WarehouseID', $id)->first();
            if ($checkExist) {
                return redirect()->back()->withInput()->withErrors([
                    "Warehouse ID has already been taken!"
                ]);
            }
        } else {
            $id = trim($request->input('WarehouseID'));
        }

        try {

            if (Schema::hasColumn('Ms_Warehouse', 'DivisionID')) {
                $warehouse = MsWarehouse::create([
                    'WarehouseID' => $id,
                    'WarehouseName' => $request->input('WarehouseName') ? trim($request->input('WarehouseName')) : null,
                    'Location' => $request->input('Location') ? trim($request->input('Location')) : null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'ParentID' => $request->input('ParentID') ?? null,
                    'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'Active' => $request->input('Active') ?? 0,
                    'IsAuto' => $request->input('automatic') ?? 0,
                    'LastDigit' => $digit,
                    'DivisionID' => $request->input('DivisionID') ?? null,
                ]);
            } else {
                $warehouse = MsWarehouse::create([
                    'WarehouseID' => $id,
                    'WarehouseName' => $request->input('WarehouseName') ? trim($request->input('WarehouseName')) : null,
                    'Location' => $request->input('Location') ? trim($request->input('Location')) : null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'ParentID' => $request->input('ParentID') ?? null,
                    'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'Active' => $request->input('Active') ?? 0,
                    'IsAuto' => $request->input('automatic') ?? 0,
                    'LastDigit' => $digit,
                ]);
            }

            $this->createWarehouseQr($warehouse);

            return redirect()->route('warehouse')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Warehouse successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function edit($id)
    {

        $warehouse = MsWarehouse::where('id', $id)->first();

        if (Schema::hasColumn('Ms_Warehouse', 'DivisionID')) {
            $showDivisions = true;
            $divisionIds = MsWarehouse::whereNotNull('DivisionID')
                ->where('id', '!=', $warehouse->id)
                ->pluck('DivisionID');
            $divisions = MsDivision::whereNotIn('DivisionID', $divisionIds)->get();
        } else {
            $showDivisions = false;
            $divisions = collect();
        }

        return view('warehouse.edit', compact('warehouse', 'showDivisions', 'divisions'));
    }

    public function update(Request $request)
    {

        $this->validate($request, [
            'WarehouseName' => 'nullable|string|max:255',
        ], [
            'WarehouseName.max' => 'Warehouse Name maximum characters is 255!',
            'ContactPerson.max' => 'Contact Person maximum characters is 255!',
        ]);

        try {
            $warehouse = MsWarehouse::find($request->input('id'));

            if ($request->input('ParentID')) {
                $checkParent = MsWarehouse::find($request->input('ParentID'));

                if ($checkParent->ParentID == $request->input('id')) {
                    return redirect()->back()->withInput()->withErrors([
                        "Can't pick " . $request->input('ParentID') . " as parent because it's parent is this warehouse!",
                    ]);
                }
            }

            if (Schema::hasColumn('Ms_Warehouse', 'DivisionID')) {

                $warehouse->update([
                    'WarehouseName' => $request->input('WarehouseName') ? trim($request->input('WarehouseName')) : null,
                    'Location' => $request->input('Location') ? trim($request->input('Location')) : null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'ParentID' => $request->input('ParentID') ?? null,
                    'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'Active' => $request->input('Active') ?? 0,
                    'DivisionID' => $request->input('DivisionID') ?? null,
                ]);
            } else {
                $warehouse->update([
                    'WarehouseName' => $request->input('WarehouseName') ? trim($request->input('WarehouseName')) : null,
                    'Location' => $request->input('Location') ? trim($request->input('Location')) : null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'ParentID' => $request->input('ParentID') ?? null,
                    'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'Active' => $request->input('Active') ?? 0,
                ]);
            }



            return redirect()->route('warehouse')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Warehouse successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function destroy($id)
    {

        try {
            $warehouse = MsWarehouse::where('id', $id)->first();

            $used = MsPart::where('DeferedWarehouseID', $warehouse->WarehouseID)->exists();
            if ($used) {
                return response([
                    'status' => 'failed',
                ]);
            }

            if (Schema::hasColumn('Ms_Warehouse', 'DivisionID')) {
                if ($warehouse->division && $warehouse->division->connectedWith) {
                    return response([
                        'status' => 'failed',
                    ]);
                }
            }

            $warehouse->delete();

            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response([
                'status' => 'failed',
            ]);
        }
    }

    public function generateQr($id)
    {
        try {
            $warehouse = MsWarehouse::where('id', $id)->firstOrFail();

            if (!$this->findWarehouseQr($warehouse)) {
                $this->createWarehouseQr($warehouse);
            }

            return redirect()->route('warehouse.show', $warehouse->id)->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Warehouse QR successfully generated!'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    private function findWarehouseQr(MsWarehouse $warehouse): ?MsQR
    {
        return MsQR::whereRaw("JSON_VALUE(json_value, '$.data.WarehouseID') = ?", [$warehouse->WarehouseID])
            ->whereRaw("JSON_VALUE(json_value, '$.data.PartID') IS NULL")
            ->first();
    }

    private function createWarehouseQr(MsWarehouse $warehouse): MsQR
    {
        $code = $this->generateQrCode();
        $data = [
            'WarehouseID' => $warehouse->WarehouseID,
        ];

        return MsQR::create([
            'code' => $code,
            'json_value' => ['data' => array_merge(['Code' => $code], $data)],
            'json_display' => ['data' => array_merge(['Code' => $code], $data)],
            'show_content' => true,
        ]);
    }

    private function generateQrCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (MsQR::where('code', $code)->exists());

        return $code;
    }
}
