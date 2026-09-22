<?php

namespace App\Http\Controllers\User;

use App\Models\MsCustomer;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use App\Models\MsCustomerShipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\CustomerExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
public function index()
    {
        return view('user.customer.index');
    }

    public function export()
    {
        $data = MsCustomer::with(['division', 'salesman'])->get();
        return Excel::download(new CustomerExport($data), 'Master_Customer_' . date('d_m_Y_H_i_s') . '.xlsx');
    }

    public function datatable(Request $request)
    {
        $data = MsCustomer::select('Ms_Customer.id', 'CustomerID', 'Ms_Customer.Active', 'CustomerName', 'Ms_Customer.DivisionID', 'Ms_Customer.SalesmanID', 'Ms_Customer.created_at')
            ->with('division', 'salesman');

        return DataTables::of($data)
            ->editColumn('DivisionID', fn ($row) => optional($row->division)->DivisionName ?? $row->DivisionID)
            ->editColumn('SalesmanID', fn ($row) => optional($row->salesman)->EmployeeName ?: $row->SalesmanID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('user.customer.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if (Auth::user()->hasAnyPermission(['admin', 'customer.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('user.customer.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'customer.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('user.customer.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        return view('user.customer.add');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'CustomerID' => 'required_without:automatic|string|max:50|unique:Ms_Customer,CustomerID',
            'CustomerName' => 'nullable|string|max:255',
            'ContactPerson' => 'nullable|string|max:255',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Term' => 'nullable|integer',
            'LimitDaysETD' => 'nullable|integer',
            'LimitDaysETA' => 'nullable|integer',
            'CreditLimit' => 'nullable|numeric',
            'InvoiceLimit' => 'nullable|numeric',
            'LockDueDateByDay' => 'nullable|integer',
            'IndividualID' => 'nullable|string|max:50',
            'IndividualName' => 'nullable|string|max:255',
            'NPWPOwner' => 'nullable|string|max:50',
            'Shipment.*' => 'required|string|max:255'
        ], [
            'CustomerID.unique' => 'Customer ID has already been taken!',
            'CustomerID.max' => 'Customer ID maximum characters is 50!',
            'CustomerName.max' => 'Customer Name maximum characters is 255!',
            'ContactPerson.max' => 'Contact Person maximum characters is 255!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone.max' => 'Phone maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'IndividualID.max' => 'Bank ID maximum characters is 50!',
            'IndividualName.max' => 'Bank Name maximum characters is 255!',
            'NPWPOwner.max' => 'Bank Account No maximum characters is 50!',
            'Shipment.max' => 'Shipment maximum characters is 255!',
        ]);

        $masterAuto = MsAutoNumber::find("1");

        $digit = null;

        if ($request->input('automatic')) {
            $checkLast = MsCustomer::where('IsAuto', 1)->orderBy('LastDigit', 'desc')->first();
            if (!$checkLast) {
                $digit = 1;
            } else {
                $digit = $checkLast->LastDigit + 1;
            }

            $id = $masterAuto->Master06 . str_pad($digit, 4, "0", STR_PAD_LEFT);
            $checkExist = MsCustomer::where('CustomerID', $id)->first();
            if ($checkExist) {
                return redirect()->back()->withInput()->withErrors([
                    "Customer ID has already been taken!"
                ]);
            }
        } else {
            $id = trim($request->input('CustomerID'));
        }

        try {
            DB::beginTransaction();
            $imageLocation = '';
            $imagePerson = '';
            $imageID = '';

            if ($request->file('PathImageLocation')) {
                $file = $request->file('PathImageLocation');
                $imageLocation = str_replace('/', '', $id) . '_location_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imageLocation, 'customer_location');
            }

            if ($request->file('PathImagePerson')) {
                $file = $request->file('PathImagePerson');
                $imagePerson = str_replace('/', '', $id) . '_person_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imagePerson, 'customer_person');
            }

            if ($request->file('PathImageId')) {
                $file = $request->file('PathImageId');
                $imageID = str_replace('/', '', $id) . '_id_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imageID, 'customer_id');
            }

            MsCustomer::create([
                'CustomerID' => $id,
                'CustomerName' => $request->input('CustomerName') ? trim($request->input('CustomerName')) : null,
                'ContactPerson' => $request->input('ContactPerson') ? trim($request->input('ContactPerson')) : null,
                'Birthday' => $request->input('Birthday') ? \DateTime::createFromFormat('d/m/Y', $request->input('Birthday'))->format('Y-m-d') : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'SubDistrictID' => $request->input('SubDistrictID') ?? null,
                'Phone' => $request->input('Phone') ? trim($request->input('Phone')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'Term' => $request->input('Term') ?? 0,
                'LimitDaysETA' => $request->input('LimitDaysETA') ?? 0,
                'LimitDaysETD' => $request->input('LimitDaysETD') ?? 0,
                'CreditLimit' => $request->input('CreditLimit') ?? 0,
                'ChequeOutstandingRecognize' => $request->input('ChequeOutstandingRecognize') ?? 0,
                'InvoiceLimit' => $request->input('InvoiceLimit') ?? 0,
                'LockDueDateByDay' => $request->input('LockDueDateByDay') ?? 0,
                'DivisionID' => $request->input('DivisionID') ?? null,
                'SalesmanID' => $request->input('SalesmanID') ?? null,
                'IndividualID' => $request->input('IndividualID') ? trim($request->input('IndividualID')) : null,
                'IndividualName' => $request->input('IndividualName') ? trim($request->input('IndividualName')) : null,
                'NPWPOwner' => $request->input('NPWPOwner') ? trim($request->input('NPWPOwner')) : null,
                'PathImageLocation' => $request->file('PathImageLocation') ? $imageLocation : null,
                'PathImagePerson' => $request->file('PathImagePerson') ? $imagePerson : null,
                'PathImageId' => $request->file('PathImageId') ? $imageID : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
            ]);

            if ($request->input('Shipment')) {
                $address = $request->input('ShipmentAddress');
                foreach ($request->input('Shipment') as $i => $shipment) {
                    MsCustomerShipment::create([
                        'CustomerID' => $id,
                        'Shipment' => $shipment,
                        'Address' => $address[$i],
                    ]);
                }
            }

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Customer @CustomerID ='" . $id . "'");
            DB::commit();
            return redirect()->route('user.customer')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Customer successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            $customer = MsCustomer::find($id);
            if ($customer) {
                MsCustomerShipment::where('CustomerID', $id)->delete();
                $customer->delete();
            }

            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function show($id)
    {
        $customer = MsCustomer::where('id', $id)->first();

        return view('user.customer.show', compact('customer'));
    }

    public function edit($id)
    {
        $customer = MsCustomer::where('id', $id)->first();

        return view('user.customer.edit', compact('customer'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'CustomerName' => 'nullable|string|max:255',
            'ContactPerson' => 'nullable|string|max:255',
            'NPWP' => 'nullable|string|max:50',
            'City' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Term' => 'nullable|integer',
            'LimitDaysETD' => 'nullable|integer',
            'LimitDaysETA' => 'nullable|integer',
            'CreditLimit' => 'nullable|numeric',
            'InvoiceLimit' => 'nullable|numeric',
            'LockDueDateByDay' => 'nullable|integer',
            'IndividualID' => 'nullable|string|max:50',
            'IndividualName' => 'nullable|string|max:255',
            'NPWPOwner' => 'nullable|string|max:50',
            'Shipment.*' => 'required|string|max:255'
        ], [
            'CustomerName.max' => 'Customer Name maximum characters is 255!',
            'ContactPerson.max' => 'Contact Person maximum characters is 255!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'Phone.max' => 'Phone maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'IndividualID.max' => 'Bank ID maximum characters is 50!',
            'IndividualName.max' => 'Bank Name maximum characters is 255!',
            'NPWPOwner.max' => 'Bank Account No maximum characters is 50!',
            'Shipment.max' => 'Shipment maximum characters is 255!',
        ]);

        try {
            DB::beginTransaction();
            $customer = MsCustomer::find($request->input('id'));

            if ($customer->CreatedBy == 'System') {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors([
                    'Customer is not editable'
                ]);
            }

            $imageLocation = '';
            $imagePerson = '';
            $imageID = '';

            if ($request->file('PathImageLocation')) {
                if ($customer->PathImageLocation != null && $customer->PathImageLocation != '') {
                    Storage::disk('customer_location')->delete($customer->PathImageLocation);
                }

                $file = $request->file('PathImageLocation');
                $imageLocation = str_replace('/', '', $request->input('id')) . '_location_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imageLocation, 'customer_location');
            }

            if ($request->file('PathImagePerson')) {
                if ($customer->PathImagePerson != null && $customer->PathImagePerson != '') {
                    Storage::disk('customer_person')->delete($customer->PathImagePerson);
                }

                $file = $request->file('PathImagePerson');
                $imagePerson = str_replace('/', '', $request->input('id')) . '_person_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imagePerson, 'customer_person');
            }

            if ($request->file('PathImageId')) {
                if ($customer->PathImageId != null && $customer->PathImageId != '') {
                    Storage::disk('customer_id')->delete($customer->PathImageId);
                }

                $file = $request->file('PathImageId');
                $imageID = str_replace('/', '', $request->input('id')) . '_id_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $imageID, 'customer_id');
            }

            $customer->update([
                'CustomerName' => $request->input('CustomerName') ? trim($request->input('CustomerName')) : null,
                'ContactPerson' => $request->input('ContactPerson') ? trim($request->input('ContactPerson')) : null,
                'Birthday' => $request->input('Birthday') ? \DateTime::createFromFormat('d/m/Y', $request->input('Birthday'))->format('Y-m-d') : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'SubDistrictID' => $request->input('SubDistrictID') ?? null,
                'Phone' => $request->input('Phone') ? trim($request->input('Phone')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'Term' => $request->input('Term') ?? 0,
                'LimitDaysETA' => $request->input('LimitDaysETA') ?? 0,
                'LimitDaysETD' => $request->input('LimitDaysETD') ?? 0,
                'CreditLimit' => $request->input('CreditLimit') ?? 0,
                'ChequeOutstandingRecognize' => $request->input('ChequeOutstandingRecognize') ?? 0,
                'InvoiceLimit' => $request->input('InvoiceLimit') ?? 0,
                'LockDueDateByDay' => $request->input('LockDueDateByDay') ?? 0,
                'DivisionID' => $request->input('DivisionID') ?? null,
                'SalesmanID' => $request->input('SalesmanID') ?? null,
                'IndividualID' => $request->input('IndividualID') ? trim($request->input('IndividualID')) : null,
                'IndividualName' => $request->input('IndividualName') ? trim($request->input('IndividualName')) : null,
                'NPWPOwner' => $request->input('NPWPOwner') ? trim($request->input('NPWPOwner')) : null,
                'PathImageLocation' => $request->file('PathImageLocation') ? $imageLocation : $customer->PathImageLocation,
                'PathImagePerson' => $request->file('PathImagePerson') ? $imagePerson : $customer->PathImagePerson,
                'PathImageId' => $request->file('PathImageId') ? $imageID : $customer->PathImageId,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
            ]);

            MsCustomerShipment::where('CustomerID', $request->input('id'))->delete();

            if ($request->input('Shipment')) {
                $address = $request->input('ShipmentAddress');
                foreach ($request->input('Shipment') as $i => $shipment) {
                    MsCustomerShipment::create([
                        'CustomerID' => $request->input('id'),
                        'Shipment' => $shipment,
                        'Address' => $address[$i],
                    ]);
                }
            }

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Customer @CustomerID ='" . $request->input('id') . "'");

            DB::commit();
            return redirect()->route('user.customer')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Customer successfully updated!'
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
            $customer = MsCustomer::where('id', $id)->first();

            if ($customer->CreatedBy == 'System') {
                return response([
                    'status' => 'failed',
                ]);
            }
$code = $customer->CustomerID;

            MsCustomerShipment::where('CustomerID', $code)->delete();
            $customer->delete();

            // MUMBO JUMBO
            DB::select("exec AutoCreate_Customer @CustomerID ='" . $code . "'");
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
