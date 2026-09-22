<?php

namespace App\Http\Controllers\User;

use App\Models\MsSupplier;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
public function index()
    {
        return view('user.supplier.index');
    }

    public function datatable(Request $request)
    {
        $data = MsSupplier::select('id', 'SupplierID', 'Ms_Supplier.Active', 'SupplierName', 'ContactPerson', 'Email', 'created_at');

        return DataTables::eloquent($data)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('user.supplier.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if (Auth::user()->hasAnyPermission(['admin', 'supplier.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('user.supplier.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'supplier.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.supplier.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->toJson();
    }

    public function add()
    {
        return view('user.supplier.add');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'SupplierID' => 'required_without:automatic|string|max:50|unique:Ms_Supplier,SupplierID',
            'SupplierName' => 'nullable|string|max:255',
            'ContactPerson' => 'nullable|string|max:50',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Website' => 'nullable|string|max:50',
            'Term' => 'nullable|integer',
            'LimitDaysETD' => 'nullable|integer',
            'LimitDaysETA' => 'nullable|integer',
            'AccountPayableLimit' => 'nullable|integer',
            'IndividualID' => 'nullable|string|max:50',
            'IndividualName' => 'nullable|string|max:255',
            'NPWPOwner' => 'nullable|string|max:50',
            'BankID' => 'nullable|string|max:50',
            'BankName' => 'nullable|string|max:255',
            'BankAccountNo' => 'nullable|string|max:50',
            'BankAccountOwner' => 'nullable|string|max:255',
        ], [
            'SupplierID.unique' => 'Supplier ID has already been taken!',
            'SupplierID.max' => 'Supplier ID maximum characters is 50!',
            'SupplierName.max' => 'Supplier Name maximum characters is 255!',
            'ContactPerson.max' => 'Contact Person maximum characters is 50!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone.max' => 'Phone maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'Website.max' => 'Website maximum characters is 50!',
            'IndividualID.max' => 'Bank ID maximum characters is 50!',
            'IndividualName.max' => 'Bank Name maximum characters is 255!',
            'NPWPOwner.max' => 'Bank Account No maximum characters is 50!',
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
                $checkLast = MsSupplier::where('IsAuto', 1)->orderBy('LastDigit', 'desc')->first();
                if (!$checkLast) {
                    $digit = 1;
                } else {
                    $digit = $checkLast->LastDigit + 1;
                }

                $id = $masterAuto->Master05 . str_pad($digit, 4, "0", STR_PAD_LEFT);

                $checkExist = MsSupplier::where('SupplierID', $id)->first();
                if ($checkExist) {
                    return redirect()->back()->withInput()->withErrors([
                        "Supplier ID has already been taken!"
                    ]);
                }
            } else {
                $id = trim($request->input('SupplierID'));
            }

            MsSupplier::create([
                'SupplierID' => $id,
                'SupplierName' => $request->input('SupplierName') ? trim($request->input('SupplierName')) : null,
                'ContactPerson' => $request->input('ContactPerson') ? trim($request->input('ContactPerson')) : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'Phone' => $request->input('Phone') ? trim($request->input('Phone')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'Website' => $request->input('Website') ? trim($request->input('Website')) : null,
                'Term' => $request->input('Term') ?? 0,
                'LimitDaysETA' => $request->input('LimitDaysETA') ?? 0,
                'LimitDaysETD' => $request->input('LimitDaysETD') ?? 0,
                'Forwarder' => $request->input('Forwarder') ?? 0,
                'AccountPayableLimit' => $request->input('AccountPayableLimit') ?? 0,
                'IndividualID' => $request->input('IndividualID') ? trim($request->input('IndividualID')) : null,
                'IndividualName' => $request->input('IndividualName') ? trim($request->input('IndividualName')) : null,
                'NPWPOwner' => $request->input('NPWPOwner') ? trim($request->input('NPWPOwner')) : null,
                'BankID' => $request->input('BankID') ? trim($request->input('BankID')) : null,
                'BankName' => $request->input('BankName') ? trim($request->input('BankName')) : null,
                'BankAccountNo' => $request->input('BankAccountNo') ? trim($request->input('BankAccountNo')) : null,
                'BankAccountOwner' => $request->input('BankAccountOwner') ? trim($request->input('BankAccountOwner')) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
                'DivisionID' => $request->input('DivisionID') ?? null,
            ]);

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Supplier @SupplierID ='" . $id . "'");
            DB::commit();
            return redirect()->route('user.supplier')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Supplier successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function show($id)
    {
        $supplier = MsSupplier::where('id', $id)->first();

        return view('user.supplier.show', compact('supplier'));
    }

    public function edit($id)
    {
        $supplier = MsSupplier::where('id', $id)->first();

        return view('user.supplier.edit', compact('supplier'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'SupplierName' => 'nullable|string|max:255',
            'ContactPerson' => 'nullable|string|max:50',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Website' => 'nullable|string|max:50',
            'Term' => 'nullable|integer',
            'LimitDaysETD' => 'nullable|integer',
            'LimitDaysETA' => 'nullable|integer',
            'AccountPayableLimit' => 'nullable|integer',
            'IndividualID' => 'nullable|string|max:50',
            'IndividualName' => 'nullable|string|max:255',
            'NPWPOwner' => 'nullable|string|max:50',
            'BankID' => 'nullable|string|max:50',
            'BankName' => 'nullable|string|max:255',
            'BankAccountNo' => 'nullable|string|max:50',
            'BankAccountOwner' => 'nullable|string|max:255',
        ], [
            'SupplierName.max' => 'Supplier Name maximum characters is 255!',
            'ContactPerson.max' => 'Contact Person maximum characters is 50!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone.max' => 'Phone maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'Website.max' => 'Email maximum characters is 50!',
            'IndividualID.max' => 'Bank ID maximum characters is 50!',
            'IndividualName.max' => 'Bank Name maximum characters is 255!',
            'NPWPOwner.max' => 'Bank Account No maximum characters is 50!',
            'BankID.max' => 'Bank ID maximum characters is 50!',
            'BankName.max' => 'Bank Name maximum characters is 255!',
            'BankAccountNo.max' => 'Bank Account No maximum characters is 50!',
            'BankAccountOwner.max' => 'Bank Account Owner maximum characters is 255!',
        ]);

        try {

            DB::beginTransaction();
            $supplier = MsSupplier::find($request->input('id'));
            if ($supplier->CreatedBy == 'System') {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors([
                    'Supplier is not editable'
                ]);
            }

            $filename = '';

            if ($request->file('Photo')) {
                if ($supplier->Photo != null && $supplier->Photo != '') {
                    Storage::disk('supplier_photo')->delete($supplier->Photo);
                }

                $file = $request->file('Photo');
                $filename = $supplier->SupplierID . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $filename, 'supplier_photo');
            }

            $supplier->update([
                'SupplierName' => $request->input('SupplierName') ? trim($request->input('SupplierName')) : null,
                'ContactPerson' => $request->input('ContactPerson') ? trim($request->input('ContactPerson')) : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'Phone' => $request->input('Phone') ? trim($request->input('Phone')) : null,
                'Website' => $request->input('Website') ? trim($request->input('Website')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'Term' => $request->input('Term') ?? 0,
                'LimitDaysETA' => $request->input('LimitDaysETA') ?? 0,
                'LimitDaysETD' => $request->input('LimitDaysETD') ?? 0,
                'Forwarder' => $request->input('Forwarder') ?? 0,
                'AccountPayableLimit' => $request->input('AccountPayableLimit') ?? 0,
                'IndividualID' => $request->input('IndividualID') ? trim($request->input('IndividualID')) : null,
                'IndividualName' => $request->input('IndividualName') ? trim($request->input('IndividualName')) : null,
                'NPWPOwner' => $request->input('NPWPOwner') ? trim($request->input('NPWPOwner')) : null,
                'BankID' => $request->input('BankID') ? trim($request->input('BankID')) : null,
                'BankName' => $request->input('BankName') ? trim($request->input('BankName')) : null,
                'BankAccountNo' => $request->input('BankAccountNo') ? trim($request->input('BankAccountNo')) : null,
                'BankAccountOwner' => $request->input('BankAccountOwner') ? trim($request->input('BankAccountOwner')) : null,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
                'DivisionID' => $request->input('DivisionID') ?? null,
            ]);

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Supplier @SupplierID ='" . $request->input('id') . "'");

            DB::commit();
            return redirect()->route('user.supplier')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Supplier successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $supplier = MsSupplier::where('id', $id)->first();

            if ($supplier->CreatedBy == 'System') {
                return response([
                    'status' => 'failed',
                ]);
            }
$code = $supplier->SupplierID;

            $supplier->delete();

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Supplier @CustomerID ='" . $code . "'");
            DB::commit();
            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response([
                'status' => 'failed',
                'message' => $e->getMessage() ?: 'Something went wrong!',
            ]);
        }
    }
}
