<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.user.index');
    }

    public function datatable(Request $request)
    {
        $data = MsUser::select('id', 'UserID', 'UserName', 'EmployeeID', 'Active', 'created_at')->where('isAdmin', 0)->with('employee');

        return DataTables::of($data)
            ->editColumn('EmployeeID', fn ($row) => optional($row->employee)->EmployeeName ?: $row->EmployeeID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('user.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                if ($row->Active) {
                    $btn .= '<button class="btn btn-sm btn-alt-danger js-bs-tooltip-enabled disable-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.disable', $row->id) . '"><i class="fa fa-fw fa-x"></i></button>';
                } else {
                    $btn .= '<button class="btn btn-sm btn-alt-success js-bs-tooltip-enabled enable-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.enable', $row->id) . '"><i class="fa fa-fw fa-check"></i></button>';
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        return view('admin.user.add');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'UserID' => 'required|string|max:10|unique:Ms_User,UserID',
            'UserNameParam' => 'required|string|max:255|unique:Ms_User,UserName',
            'PasswordParam' => 'required|string|min:6',
            'ConfirmPassword' => 'required|string|same:PasswordParam',
            'EmployeeID' => 'required|unique:Ms_User,EmployeeID'
        ], [
            'UserID.unique' => 'User ID has already been taken!',
            'UserID.max' => 'User ID maximum characters is 10!',
            'UserNameParam.max' => 'Username maximum characters is 255!',
            'UserNameParam.unique' => 'UserName has already been taken!',
            'PasswordParam.min' => 'Password must be 6 characters minimum!',
            'ConfirmPassword.same' => 'Password and Confirm Password must be the same!',
            'EmployeeID.unique' => 'Employee ID has already been used!',
        ]);

        try {
            MsUser::create([
                'UserID' => trim($request->input('UserID')),
                'UserName' => trim($request->input('UserNameParam')),
                'Password' => trim($request->input('PasswordParam')),
                'WebPassword' => Hash::make($request->input('PasswordParam')),
                'EmployeeID' => trim($request->input('EmployeeID')),
                'isAdmin' => 0,
                'Active' => 0,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->route('user')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'User successfully added!'
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
        $user = MsUser::where('id', $id)->first();

        return view('admin.user.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'UserNameParam' => 'required|string|max:255',
            'PasswordParam' => 'nullable|min:6',
            'ConfirmPassword' => 'required_with:Password|same:PasswordParam',
            'EmployeeID' => 'required|unique:Ms_User,EmployeeID,' . $request->input('id') . ',id'
        ], [
            'UserNameParam.max' => 'Username maximum characters is 255!',
            'PasswordParam.min' => 'Password must be 6 characters minimum!',
            'ConfirmPassword.same' => 'Password and Confirm Password must be the same!',
            'EmployeeID.unique' => 'Employee ID has already been used!',
        ]);

        try {
            $user = MsUser::where('id', $request->input('id'))->first();

            if ($user->UserID == 'System') {
                return redirect()->back()->withInput()->withErrors([
                    'User is not editable.'
                ]);
            }

            if ($user->CreatedBy == 'System') {
                return redirect()->back()->withInput()->withErrors([
                    'User is not editable.'
                ]);
            }

            $user->update([
                'UserName' => trim($request->input('UserNameParam')),
                'Password' => $request->input('PasswordParam') ? trim($request->input('PasswordParam')) : $user->Password,
                'WebPassword' => $request->input('PasswordParam') ? Hash::make($request->input('PasswordParam')) : $user->WebPassword,
                'EmployeeID' => trim($request->input('EmployeeID')),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->route('user')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'User successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function disable($id)
    {
        try {
            $user = MsUser::where('id', $id)->first();

            if ($user->UserID == 'System') {
                return response([
                    'status' => 'failed',
                ]);
            }

            if ($user->CreatedBy == 'System') {
                return response([
                    'status' => 'failed',
                ]);
            }

            $user->update([
                'Active' => 0,
            ]);

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

    public function enable($id)
    {
        try {
            $user = MsUser::where('id', $id)->first();

            $user->update([
                'Active' => 1,
            ]);

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
}
