<?php

namespace App\Http\Controllers\User;

use App\Models\MsDivision;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class DivisionController extends Controller
{
    public function index()
    {
        return view('user.division.index');
    }

    public function datatable(Request $request)
    {
        $data = MsDivision::select(
            'Ms_Division.id',
            'Ms_Division.DivisionID',
            'Ms_Division.DivisionName',
            'Ms_Division.Active',
            'Ms_Division.SubDivisionID',
            DB::raw("CONCAT(Ms_Employee.FirstName, ' ', Ms_Employee.LastName) AS EmployeeName"),
            'Ms_Division.Notes',
            'Ms_Division.created_at'
        )
            ->leftJoin('Ms_Employee', 'Ms_Division.StaffInChargeID', '=', 'Ms_Employee.EmployeeID')
            ->with('subDivision');

        return DataTables::of($data)
            ->editColumn('SubDivisionID', fn ($row) => optional($row->subDivision)->DivisionName ?? $row->SubDivisionID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                if (Auth::user()->hasAnyPermission(['admin', 'division.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('user.division.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'division.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.division.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->filterColumn('EmployeeName', function ($query, $keyword) {
                $sql = "CONCAT(Ms_Employee.FirstName, ' ', Ms_Employee.LastName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->make(true);
    }

    public function add()
    {
        return view('user.division.add');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'DivisionID' => 'required_without:automatic|string|max:50|unique:Ms_Division,DivisionID',
            'DivisionName' => 'nullable|string|max:255',
            'Notes' => 'nullable|string'
        ], [
            'DivisionID.unique' => 'Division ID has already been taken!',
            'DivisionID.max' => 'Division ID maximum characters is 50!',
            'DivisionName.max' => 'Division Name maximum characters is 255!',
        ]);

        try {
            DB::beginTransaction();
            $masterAuto = MsAutoNumber::find("1");

            $digit = null;

            if ($request->input('automatic')) {
                $checkLast = MsDivision::where('IsAuto', 1)->orderBy('LastDigit', 'desc')->first();
                if (!$checkLast) {
                    $digit = 1;
                } else {
                    $digit = $checkLast->LastDigit + 1;
                }

                $id = $masterAuto->Master04 . str_pad($digit, 4, "0", STR_PAD_LEFT);

                $checkExist = MsDivision::where('DivisionID', $id)->first();
                if ($checkExist) {
                    return redirect()->back()->withInput()->withErrors([
                        "Division ID has already been taken!"
                    ]);
                }
            } else {
                $id = trim($request->input('DivisionID'));
            }

            MsDivision::create([
                'DivisionID' => $id,
                'DivisionName' => $request->input('DivisionName') ? trim($request->input('DivisionName')) : null,
                'SubDivisionID' => $request->input('SubDivisionID') ?? null,
                'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
            ]);
            DB::commit();
            return redirect()->route('user.division')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Division successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function edit($id)
    {
        $division = MsDivision::where('id', $id)->first();

        return view('user.division.edit', compact('division'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'DivisionName' => 'nullable|string|max:255',
            'Notes' => 'nullable|string'
        ], [
            'DivisionName.max' => 'Type Name maximum characters is 255!',
        ]);

        try {
            DB::beginTransaction();
            $division = MsDivision::find($request->input('id'));

            if ($request->input('SubDivisionID')) {
                $checkParent = MsDivision::find($request->input('SubDivisionID'));

                if ($checkParent->SubDivisionID == $request->input('id')) {
                    return redirect()->back()->withInput()->withErrors([
                        "Can't pick " . $request->input('ParentID') . " as sub division because this division is it's sub division!",
                    ]);
                }
            }

            $division->update([
                'DivisionName' => $request->input('DivisionName') ? trim($request->input('DivisionName')) : null,
                'SubDivisionID' => $request->input('SubDivisionID') ?? null,
                'StaffInChargeID' => $request->input('StaffInChargeID') ?? null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Active' => $request->input('Active') ?? 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
            DB::commit();
            return redirect()->route('user.division')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Division successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $division = MsDivision::where('id', $id)->first();

            $division->delete();
            DB::commit();
            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response([
                'status' => 'failed',
            ]);
        }
    }
}
