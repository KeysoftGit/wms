<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MsAutoNumber;
use App\Models\MsEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('user.employee.index');
    }

    public function datatable(Request $request)
    {
        $data = MsEmployee::select('id', 'EmployeeID', 'Ms_Employee.Active', DB::raw("CONCAT(FirstName, ' ', LastName) AS EmployeeName"), 'Ms_Employee.DivisionID', 'Position', 'Ms_Employee.created_at')
            ->with('division');

        return DataTables::eloquent($data)
            ->editColumn('DivisionID', fn ($row) => optional($row->division)->DivisionName ?? $row->DivisionID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('user.employee.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if (Auth::user()->hasAnyPermission(['admin', 'employee.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('user.employee.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'employee.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.employee.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->filterColumn('EmployeeName', function ($query, $keyword) {
                $sql = "CONCAT(FirstName, ' ', LastName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->toJson();
    }

    public function add()
    {
        return view('user.employee.add');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'EmployeeID' => 'required_without:automatic|string|max:50|unique:Ms_Employee,EmployeeID',
            'FirstName' => 'nullable|string|max:255',
            'LastName' => 'nullable|string|max:255',
            'IDNumber' => 'nullable|string|max:50',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone1' => 'nullable|string|max:50',
            'Phone2' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Position' => 'nullable|string|max:255',
            'BankID' => 'nullable|string|max:50',
            'BankName' => 'nullable|string|max:255',
            'BankAccountNo' => 'nullable|string|max:50',
            'BankAccountOwner' => 'nullable|string|max:255',
        ], [
            'EmployeeID.unique' => 'Employee ID has already been taken!',
            'EmployeeID.max' => 'Employee ID maximum characters is 50!',
            'FirstName.max' => 'Employee First Name maximum characters is 255!',
            'LastName.max' => 'Employee Last Name maximum characters is 255!',
            'IDNumber.max' => 'ID Number maximum characters is 50!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone1.max' => 'Phone 1 maximum characters is 50!',
            'Phone2.max' => 'Phone 2 maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'Position.max' => 'Position maximum characters is 255!',
            'BankID.max' => 'Bank ID maximum characters is 50!',
            'BankName.max' => 'Bank Name maximum characters is 255!',
            'BankAccountNo.max' => 'Bank Account No maximum characters is 50!',
            'BankAccountOwner.max' => 'Bank Account Owner maximum characters is 255!',
        ]);

        try {
            DB::beginTransaction();
            $masterAuto = MsAutoNumber::find("1");

            $digit = null;

            if ($request->input('automatic')) {
                $checkLast = MsEmployee::where('IsAuto', 1)->orderBy('LastDigit', 'desc')->first();
                if (!$checkLast) {
                    $digit = 1;
                } else {
                    $digit = $checkLast->LastDigit + 1;
                }

                $id = $masterAuto->Master03 . str_pad($digit, 4, "0", STR_PAD_LEFT);
                $checkExist = MsEmployee::where('EmployeeID', $id)->first();
                if ($checkExist) {
                    return redirect()->back()->withInput()->withErrors([
                        "Employee ID has already been taken!"
                    ]);
                }
            } else {
                $id = trim($request->input('EmployeeID'));
            }

            $filename = '';

            if ($request->file('Photo')) {
                $file = $request->file('Photo');
                $filename = $id . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $filename, 'employee_photo');
            }

            MsEmployee::create([
                'EmployeeID' => $id,
                'FirstName' => $request->input('FirstName') ? trim($request->input('FirstName')) : null,
                'LastName' => $request->input('LastName') ? trim($request->input('LastName')) : null,
                'Gender' => $request->input('Gender') ? trim($request->input('Gender')) : null,
                'BirthDate' => $request->input('BirthDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('BirthDate'))->format('Y-m-d') : null,
                'BloodType' => $request->input('BloodType') ? trim($request->input('BloodType')) : null,
                'IDNumber' => $request->input('IDNumber') ? trim($request->input('IDNumber')) : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'MatrialStatus' => $request->input('MatrialStatus') ?? null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'Phone1' => $request->input('Phone1') ? trim($request->input('Phone1')) : null,
                'Phone2' => $request->input('Phone2') ? trim($request->input('Phone2')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'HireDate' => $request->input('HireDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('HireDate'))->format('Y-m-d') : null,
                'ActiveDate' => $request->input('ActiveDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('ActiveDate'))->format('Y-m-d') : null,
                'DivisionID' => $request->input('DivisionID') ?? null,
                'ReferenceID' => $request->input('ReferenceID') ?? null,
                'SupervisorID' => $request->input('SupervisorID') ?? null,
                'Position' => $request->input('Position') ? trim($request->input('Position')) : null,
                'BankID' => $request->input('BankID') ? trim($request->input('BankID')) : null,
                'BankName' => $request->input('BankName') ? trim($request->input('BankName')) : null,
                'BankAccountNo' => $request->input('BankAccountNo') ? trim($request->input('BankAccountNo')) : null,
                'BankAccountOwner' => $request->input('BankAccountOwner') ? trim($request->input('BankAccountOwner')) : null,
                'Photo2' => $request->file('Photo') ? $filename : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
            ]);
            DB::commit();
            return redirect()->route('user.employee')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Employee successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function show($id)
    {
        $employee = MsEmployee::where('id', $id)->first();

        return view('user.employee.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = MsEmployee::where('id', $id)->first();

        return view('user.employee.edit', compact('employee'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'FirstName' => 'nullable|string|max:255',
            'LastName' => 'nullable|string|max:255',
            'IDNumber' => 'nullable|string|max:50',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone1' => 'nullable|string|max:50',
            'Phone2' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Position' => 'nullable|string|max:255',
            'BankID' => 'nullable|string|max:50',
            'BankName' => 'nullable|string|max:255',
            'BankAccountNo' => 'nullable|string|max:50',
            'BankAccountOwner' => 'nullable|string|max:255',
        ], [
            'FirstName.max' => 'Employee First Name maximum characters is 255!',
            'LastName.max' => 'Employee Last Name maximum characters is 255!',
            'IDNumber.max' => 'ID Number maximum characters is 50!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone1.max' => 'Phone 1 maximum characters is 50!',
            'Phone2.max' => 'Phone 2 maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'Position.max' => 'Position maximum characters is 255!',
            'BankID.max' => 'Bank ID maximum characters is 50!',
            'BankName.max' => 'Bank Name maximum characters is 255!',
            'BankAccountNo.max' => 'Bank Account No maximum characters is 50!',
            'BankAccountOwner.max' => 'Bank Account Owner maximum characters is 255!',
        ]);

        try {
            DB::beginTransaction();
            $employee = MsEmployee::find($request->input('id'));

            if ($employee->CreatedBy == 'System') {
                return redirect()->back()->withInput()->withErrors([
                    'Employee is not editable'
                ]);
            }

            $filename = '';

            if ($request->file('Photo')) {
                if ($employee->Photo != null && $employee->Photo != '') {
                    Storage::disk('employee_photo')->delete($employee->Photo);
                }

                $file = $request->file('Photo');
                $filename = $employee->EmployeeID . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $filename, 'employee_photo');
            }

            $employee->update([
                'FirstName' => $request->input('FirstName') ? trim($request->input('FirstName')) : null,
                'LastName' => $request->input('LastName') ? trim($request->input('LastName')) : null,
                'Gender' => $request->input('Gender') ? trim($request->input('Gender')) : null,
                'BirthDate' => $request->input('BirthDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('BirthDate'))->format('Y-m-d') : null,
                'BloodType' => $request->input('BloodType') ? trim($request->input('BloodType')) : null,
                'IDNumber' => $request->input('IDNumber') ? trim($request->input('IDNumber')) : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'MatrialStatus' => $request->input('MatrialStatus') ?? null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'Phone1' => $request->input('Phone1') ? trim($request->input('Phone1')) : null,
                'Phone2' => $request->input('Phone2') ? trim($request->input('Phone2')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'HireDate' => $request->input('HireDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('HireDate'))->format('Y-m-d') : null,
                'ActiveDate' => $request->input('ActiveDate') ? \DateTime::createFromFormat('d/m/Y', $request->input('ActiveDate'))->format('Y-m-d') : null,
                'DivisionID' => $request->input('DivisionID') ?? null,
                'ReferenceID' => $request->input('ReferenceID') ?? null,
                'SupervisorID' => $request->input('SupervisorID') ?? null,
                'Position' => $request->input('Position') ? trim($request->input('Position')) : null,
                'BankID' => $request->input('BankID') ? trim($request->input('BankID')) : null,
                'BankName' => $request->input('BankName') ? trim($request->input('BankName')) : null,
                'BankAccountNo' => $request->input('BankAccountNo') ? trim($request->input('BankAccountNo')) : null,
                'BankAccountOwner' => $request->input('BankAccountOwner') ? trim($request->input('BankAccountOwner')) : null,
                'Photo2' => $request->file('Photo') ? $filename : $employee->Photo2,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
            ]);
            DB::commit();
            return redirect()->route('user.employee')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Employee successfully updated!'
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
            $employee = MsEmployee::where('id', $id)->first();

            if ($employee->CreatedBy == 'System') {
                return response([
                    'status' => 'failed',
                ]);
            }


            $employee->delete();
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
