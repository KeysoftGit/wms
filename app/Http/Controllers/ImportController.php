<?php

namespace App\Http\Controllers;

use App\Imports\ValidateSheet;
use App\Jobs\ImportMasterJob;
use App\Models\ControlPanel;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function instruction()
    {
        return view('import.instruction');
    }

    public function upload(Request $request)
    {
        try {
            $validate = new ValidateSheet();
            $validate->onlySheets($request->input('sheet'));

            Log::info('Starting upload for sheet: ' . $request->input('sheet'));

            $request->validate([
                'excel' => 'required|file|mimes:xls,xlsx|max:10240',
                'sheet' => 'required|string'
            ]);

            $rows = Excel::toArray($validate, $request->file('excel'));

            if (!isset($rows[$request->input('sheet')])) {
                throw new \Exception("Sheet '{$request->input('sheet')}' not found in the uploaded file.");
            }

            $result = [
                'success' => true
            ];

            Log::info('Validating sheet data...');

            switch ($request->input('sheet')) {
                case 'Country':
                    $result = $this->validateCountry($rows[$request->input('sheet')]);
                    break;
                case 'Currency':
                    $result = $this->validateCurrency($rows[$request->input('sheet')]);
                    break;
                case 'Vehicle':
                    $result = $this->validateVehicle($rows[$request->input('sheet')]);
                    break;
                case 'Unit':
                    $result = $this->validateUnit($rows[$request->input('sheet')]);
                    break;
                case 'PartCategory':
                    $result = $this->validatePartCategory($rows[$request->input('sheet')]);
                    break;
                case 'PartSpecification':
                    $result = $this->validatePartSpecification($rows[$request->input('sheet')]);
                    break;
                case 'PartVariant':
                    $result = $this->validatePartVariant($rows[$request->input('sheet')]);
                    break;
                case 'InventoryType':
                    $result = $this->validateInventoryType($rows[$request->input('sheet')]);
                    break;
                case 'COA':
                    $result = $this->validateCOA($rows[$request->input('sheet')]);
                    break;
                case 'FACategory':
                    $result = $this->validateFACategory($rows[$request->input('sheet')]);
                    break;
                case 'FALocation':
                    $result = $this->validateFALocation($rows[$request->input('sheet')]);
                    break;
                case 'Warehouse':
                    $result = $this->validateWarehouse($rows[$request->input('sheet')]);
                    break;
                case 'Division':
                    $result = $this->validateDivision($rows[$request->input('sheet')]);
                    break;
                case 'Employee':
                    $result = $this->validateEmployee($rows[$request->input('sheet')]);
                    break;
                case 'Supplier':
                    $result = $this->validateSupplier($rows[$request->input('sheet')]);
                    break;
                case 'Customer':
                    $result = $this->validateCustomer($rows[$request->input('sheet')]);
                    break;
                case 'Part':
                    $result = $this->validatePart($rows[$request->input('sheet')]);
                    break;
                case 'BeginningStock':
                    $result = $this->validateBeginningStock($rows[$request->input('sheet')]);
                    break;
                case 'Hutang':
                    $result = $this->validateHutang($rows[$request->input('sheet')]);
                    break;
                case 'Piutang':
                    $result = $this->validatePiutang($rows[$request->input('sheet')]);
                    break;
                default:
                    throw new \Exception("Invalid sheet type: {$request->input('sheet')}");
            }

            if (!$result['success']) {
                Log::error('Validation failed', ['errors' => $result['errors']]);
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors']
                ], 422);
            }

            $file = $request->file('excel');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('', $filename, 'import_excel');

            Log::info('File saved: ' . $filename);

            $id = generateRandomString(15);
            Import::create([
                'job_id' => $id,
                'status' => 'importing'
            ]);

            Log::info('Job created with ID: ' . $id);

            $db = [
                'driver' => 'sqlsrv',
                'host' => session('db_host'),
                'port' => session('db_port'),
                'database' => session('db_database'),
                'username' => session('db_user'),
                'password' => session('db_password')
            ];

            if (empty($db['host']) || empty($db['database'])) {
                throw new \Exception('Database configuration is missing. Please reconnect to the database.');
            }

            $UserID = Auth::user()->UserID;
            ImportMasterJob::dispatch($request->input('sheet'), $filename, $id, $db, $UserID);

            Log::info('Job dispatched successfully');

            return response()->json([
                'success' => true,
                'id' => $id,
                'message' => 'Import started successfully to ' . $db['database']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            Log::error('Excel file error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'errors' => ['Invalid Excel file format. Please make sure the file is a valid .xls or .xlsx file.']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'errors' => ['An error occurred during upload: ' . $e->getMessage()]
            ], 500);
        }
    }

    public function checkProgress(Request $request)
    {
        $check = Import::where('job_id', $request->input('id'))->first();

        Log::info('progress');
        Log::info($check->status);
        return response([
            'status' => (string) $check->status,
        ]);
    }









    //VALIDATION

    public function validateCountry($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.CountryID.required"] = "Row {$r} : Country ID is required!";
            $messages["{$i}.CountryID.unique"] = "Row {$r} : Country ID has already been taken!";
            $messages["{$i}.CountryID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.CountryID.max"] = "Row {$r} : Country ID maximum characters is 3!";
            $messages["{$i}.CountryName.max"] = "Row {$r} : Country Name maximum characters is 255!";
        }

        //$messages['*.CountryID.distinct'] = "";

        $validator = Validator::make($rows, [
            '*.CountryID' => 'required|string|max:3|unique:Ms_Country,CountryID|distinct',
            '*.CountryName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateCurrency($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.CurrencyID.required"] = "Row {$r} : Currency ID is required!";
            $messages["{$i}.CurrencyID.unique"] = "Row {$r} : Currency ID has already been taken!";
            $messages["{$i}.CurrencyID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.CurrencyID.max"] = "Row {$r} : Currency ID maximum characters is 3!";
            $messages["{$i}.CurrencyName.max"] = "Row {$r} : Currency Name maximum characters is 255!";
        }

        //$messages['*.CountryID.distinct'] = "";

        $validator = Validator::make($rows, [
            '*.CurrencyID' => 'required|string|max:3|unique:Ms_Currency,CurrencyID|distinct',
            '*.CurrencyName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateVehicle($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.VehicleID.required"] = "Row {$r} : Vehicle ID is required!";
            $messages["{$i}.VehicleID.unique"] = "Row {$r} : Vehicle ID has already been taken!";
            $messages["{$i}.VehicleID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.VehicleID.max"] = "Row {$r} : Vehicle ID maximum characters is 50!";
            $messages["{$i}.VehicleName.max"] = "Row {$r} : Vehicle Name maximum characters is 255!";
            $messages["{$i}.LicenseNumber.max"] = "Row {$r} : License Number maximum characters is 50!";
        }

        $validator = Validator::make($rows, [
            '*.VehicleID' => 'required|string|max:50|unique:Ms_Vehicle,VehicleID|distinct',
            '*.VehicleName' => 'nullable|string|max:255',
            '*.LicenseNumber' => 'nullable|string|max:50',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateUnit($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.UnitID.required"] = "Row {$r} : Unit ID is required!";
            $messages["{$i}.UnitID.unique"] = "Row {$r} : Unit ID has already been taken!";
            $messages["{$i}.UnitID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.UnitID.max"] = "Row {$r} : Unit ID maximum characters is 50!";
            $messages["{$i}.UnitName.max"] = "Row {$r} : Unit Name maximum characters is 255!";
        }

        $validator = Validator::make($rows, [
            '*.UnitID' => 'required|string|max:50|unique:Ms_Unit,UnitID|distinct',
            '*.UnitName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validatePartCategory($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.CategoryID.required"] = "Row {$r} : Category ID is required!";
            $messages["{$i}.CategoryID.unique"] = "Row {$r} : Category ID has already been taken!";
            $messages["{$i}.CategoryID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.CategoryID.max"] = "Row {$r} : Category ID maximum characters is 4!";
            $messages["{$i}.CategoryName.max"] = "Row {$r} : Category Name maximum characters is 255!";
        }

        $validator = Validator::make($rows, [
            '*.CategoryID' => 'required|string|max:4|unique:Ms_PartCategory,CategoryID',
            '*.CategoryName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validatePartSpecification($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.SpecificationID.required"] = "Row {$r} : Specification ID is required!";
            $messages["{$i}.SpecificationID.unique"] = "Row {$r} : Specification ID has already been taken!";
            $messages["{$i}.SpecificationID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.SpecificationID.max"] = "Row {$r} : Specification ID maximum characters is 2!";
            $messages["{$i}.SpecificationName.max"] = "Row {$r} : Specification Name maximum characters is 255!";
        }

        $validator = Validator::make($rows, [
            '*.SpecificationID' => 'required|string|max:2|unique:Ms_PartSpecification,SpecificationID',
            '*.SpecificationName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validatePartVariant($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.VariantID.required"] = "Row {$r} : Variant ID is required!";
            $messages["{$i}.VariantID.unique"] = "Row {$r} : Variant ID has already been taken!";
            $messages["{$i}.VariantID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.VariantID.max"] = "Row {$r} : Variant ID maximum characters is 2!";
            $messages["{$i}.VariantName.max"] = "Row {$r} : Variant Name maximum characters is 255!";
        }

        $validator = Validator::make($rows, [
            '*.VariantID' => 'required|string|max:2|unique:Ms_PartVariant,VariantID',
            '*.VariantName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateInventoryType($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.InventoryTypeID.required"] = "Row {$r} : Type ID is required!";
            $messages["{$i}.InventoryTypeID.unique"] = "Row {$r} : Type ID has already been taken!";
            $messages["{$i}.InventoryTypeID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.InventoryTypeID.max"] = "Row {$r} : Type ID maximum characters is 50!";
            $messages["{$i}.InventoryTypeName.max"] = "Row {$r} : Type Name maximum characters is 255!";
        }

        $validator = Validator::make($rows, [
            '*.InventoryTypeID' => 'required|string|max:50|unique:Ms_InventoryType,InventoryTypeID|distinct',
            '*.InventoryTypeName' => 'nullable|string|max:255',
            '*.Notes' => 'nullable|string'
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateCOA($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.AccountNo.required"] = "Row {$r} : Account ID is required!";
            $messages["{$i}.AccountNo.unique"] = "Row {$r} : Account No has already been taken!";
            $messages["{$i}.AccountNo.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.AccountNo.max"] = "Row {$r} : Account No maximum characters is 50!";
            $messages["{$i}.AccountName.max"] = "Row {$r} : Account Name maximum characters is 255!";
            $messages["{$i}.Parent.exists"] = "Row {$r} : Parent Account doesn't exist!";
            $messages["{$i}.Currency.exists"] = "Row {$r} : Currency ID doesn't exist!";
            $messages["{$i}.AccountType.in"] = "Row {$r} : Account Type value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.Header.in"] = "Row {$r} : Header value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.NormalBalance.in"] = "Row {$r} : Normal Balance value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.BalanceSheet.in"] = "Row {$r} : Balance Sheet value is invalid! Please check the detailed rules for the correct values!";
        }

        $validator = Validator::make($rows, [
            '*.AccountNo' => 'required|string|max:50|unique:Ms_COA,AccountNo|distinct',
            '*.AccountName' => 'nullable|string|max:255',
            '*.Parent' => 'exclude_if:*.Parent,0|nullable|exists:Ms_COA,AccountNo',
            '*.Currency' => 'nullable|exists:Ms_Currency,CurrencyID',
            '*.AccountType' => 'nullable|in:ACCOUNTPAYABLE,ACCOUNTRECEIVABLE,ACCUMULATEDDEPRECIATION,CASHANDBANK,COSTOFGOODSSOLD,EQUITY,EXPENSES,FIXEDASSET,INVENTORY,LONGTERMPAYABLE,OTHERASSET,OTHERCURRENTASSET,OTHERCURRENTLIABILITY,OTHEREXPENSES,OTHERINCOME,REVENUE',
            '*.Header' => 'nullable|in:1,0',
            '*.NormalBalance' => 'nullable|in:D,C',
            '*.BalanceSheet' => 'nullable|in:1,0',
            '*.Notes' => 'nullable|string',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateFACategory($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.CategoryID.required"] = "Row {$r} : Category ID is required!";
            $messages["{$i}.CategoryID.unique"] = "Row {$r} : Category ID has already been taken!";
            $messages["{$i}.CategoryID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.CategoryID.max"] = "Row {$r} : Category ID maximum characters is 50!";
            $messages["{$i}.CategoryName.max"] = "Row {$r} : Category Name maximum characters is 255!";
            $messages["{$i}.FixedAssetType.in"] = "Row {$r} : Fixed Asset Type value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.AgeInYear.numeric"] = "Row {$r} : Age In Year must be in number format!";
            $messages["{$i}.AgeInMonth.numeric"] = "Row {$r} : Age In Month must be in number format!";
            $messages["{$i}.DepreciationMethod.in"] = "Row {$r} : Depreciation Method value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.FixedAssetAccount.exists"] = "Row {$r} : Fixed Asset Account No doesn't exist!";
            $messages["{$i}.DepreciationExpenseAccount.exists"] = "Row {$r} : Depreciation Expense Account No doesn't exist!";
            $messages["{$i}.AccumulatedDepreciationAccount.exists"] = "Row {$r} : Accumulated Depreciation Account No doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.CategoryID' => 'required|string|max:50|unique:Ms_FixedAssetCategory,CategoryID|distinct',
            '*.CategoryName' => 'nullable|string|max:255',
            '*.FixedAssetType' => 'nullable|in:TANGIBLE,INTANGIBLE',
            '*.AgeInYear' => 'nullable|numeric',
            '*.AgeInMonth' => 'nullable|numeric',
            '*.DepreciationMethod' => 'nullable|in:SL,DD,ND',
            '*.FixedAssetAccount' => 'nullable|exists:Ms_COA,AccountNo',
            '*.DepreciationExpenseAccount' => 'nullable|exists:MS_COA,AccountNo',
            '*.AccumulatedDepreciationAccount' => 'nullable|exists:MS_COA,AccountNo',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateFALocation($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.LocationID.required"] = "Row {$r} : Location ID is required!";
            $messages["{$i}.LocationID.unique"] = "Row {$r} : Location ID has already been taken!";
            $messages["{$i}.LocationID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.LocationID.max"] = "Row {$r} : Location ID maximum characters is 50!";
            $messages["{$i}.LocationName.max"] = "Row {$r} : Location Name maximum characters is 255!";
            $messages["{$i}.StaffInChargeID.exists"] = "Row {$r} : Staff In Charge ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.LocationID' => 'required|string|max:50|unique:Ms_FixedAssetLocation,LocationID|distinct',
            '*.LocationName' => 'nullable|string|max:255',
            '*.StaffInChargeID' => 'nullable|exists:Ms_Employee,EmployeeID',
            '*.LocationAddress' => 'nullable|string',
            '*.Notes' => 'nullable|string',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateWarehouse($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.WarehouseID.required"] = "Row {$r} : Warehouse ID is required!";
            $messages["{$i}.WarehouseID.unique"] = "Row {$r} : Warehouse ID has already been taken!";
            $messages["{$i}.WarehouseID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.WarehouseID.max"] = "Row {$r} : Warehouse ID maximum characters is 50!";
            $messages["{$i}.WarehouseName.max"] = "Row {$r} : Warehouse Name maximum characters is 255!";
            $messages["{$i}.ParentID.exists"] = "Row {$r} : Parent ID doesn't exist!";
            $messages["{$i}.StaffInChargeID.exists"] = "Row {$r} : Staff In Charge ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.WarehouseID' => 'required|string|max:50|unique:Ms_Warehouse,WarehouseID|distinct',
            '*.WarehouseName' => 'nullable|string|max:255',
            '*.Location' => 'nullable|string',
            '*.ParentID' => 'nullable|exists:Ms_Warehouse,WarehouseID',
            '*.StaffInChargeID' => 'nullable|exists:Ms_Employee,EmployeeID',
            '*.Notes' => 'nullable|string',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateDivision($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.DivisionID.required"] = "Row {$r} : Division ID is required!";
            $messages["{$i}.DivisionID.unique"] = "Row {$r} : Division ID has already been taken!";
            $messages["{$i}.DivisionID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.DivisionID.max"] = "Row {$r} : Division ID maximum characters is 50!";
            $messages["{$i}.DivisionName.max"] = "Row {$r} : Division Name maximum characters is 255!";
            $messages["{$i}.SubDivisionID.exists"] = "Row {$r} : Sub Division ID doesn't exist!";
            $messages["{$i}.StaffInChargeID.exists"] = "Row {$r} : Staff In Charge ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.DivisionID' => 'required|string|max:50|unique:Ms_Division,DivisionID|distinct',
            '*.DivisionName' => 'nullable|string|max:255',
            '*.SubDivisionID' => 'nullable|exists:Ms_Division,DivisionID',
            '*.StaffInChargeID' => 'nullable|exists:Ms_Employee,EmployeeID',
            '*.Notes' => 'nullable|string',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateEmployee($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.EmployeeID.required"] = "Row {$r} : Employee ID is required!";
            $messages["{$i}.EmployeeID.unique"] = "Row {$r} : Employee ID has already been taken!";
            $messages["{$i}.EmployeeID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.EmployeeID.max"] = "Row {$r} : Employee ID maximum characters is 50!";
            $messages["{$i}.FirstName.max"] = "Row {$r} : First Name maximum characters is 255!";
            $messages["{$i}.LastName.max"] = "Row {$r} : Last Name maximum characters is 255!";
            $messages["{$i}.IDNumber.max"] = "Row {$r} : ID Number maximum characters is 50!";
            $messages["{$i}.NPWP.max"] = "Row {$r} : NPWP maximum characters is 50!";
            $messages["{$i}.City.max"] = "Row {$r} : City maximum characters is 50!";
            $messages["{$i}.Phone1.max"] = "Row {$r} : Phone1 maximum characters is 50!";
            $messages["{$i}.Phone2.max"] = "Row {$r} : Phone2 maximum characters is 50!";
            $messages["{$i}.Email.max"] = "Row {$r} : Email maximum characters is 50!";
            $messages["{$i}.Position.max"] = "Row {$r} : Position maximum characters is 255!";
            $messages["{$i}.BankID.max"] = "Row {$r} : Bank ID maximum characters is 50!";
            $messages["{$i}.BankName.max"] = "Row {$r} : Bank Name maximum characters is 255!";
            $messages["{$i}.BankAccountNo.max"] = "Row {$r} : Bank Account No maximum characters is 50!";
            $messages["{$i}.BankAccountOwner.max"] = "Row {$r} : Bank Account Owner maximum characters is 255!";
            $messages["{$i}.Gender.in"] = "Row {$r} : Gender value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.BloodType.in"] = "Row {$r} : Blood Type value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.MatrialStatus.in"] = "Row {$r} : Marital Status value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.BirthDate.date_format"] = "Row {$r} : BirthDate format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.HireDate.date_format"] = "Row {$r} : Hire Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.ActiveDate.date_format"] = "Row {$r} : Active Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.Country.exists"] = "Row {$r} : Country ID doesn't exist!";
            $messages["{$i}.DivisionID.exists"] = "Row {$r} : Division ID doesn't exist!";
            $messages["{$i}.ReferenceID.exists"] = "Row {$r} : Reference ID doesn't exist!";
            $messages["{$i}.SupervisorID.exists"] = "Row {$r} : Supervisor ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.EmployeeID' => 'required|string|max:50|unique:Ms_Employee,EmployeeID|distinct',
            '*.FirstName' => 'nullable|string|max:255',
            '*.LastName' => 'nullable|string|max:255',
            '*.IDNumber' => 'nullable|max:50',
            '*.NPWP' => 'nullable|max:50',
            '*.City' => 'nullable|string|max:50',
            '*.Phone1' => 'nullable|string|max:50',
            '*.Phone2' => 'nullable|string|max:50',
            '*.Email' => 'nullable|string|max:50',
            '*.Position' => 'nullable|string|max:255',
            '*.BankID' => 'nullable|string|max:50',
            '*.BankName' => 'nullable|string|max:255',
            '*.BankAccountNo' => 'nullable|max:50',
            '*.BankAccountOwner' => 'nullable|string|max:255',
            '*.BirthDate' => 'nullable|date_format:d-m-Y',
            '*.HireDate' => 'nullable|date_format:d-m-Y',
            '*.ActiveDate' => 'nullable|date_format:d-m-Y',
            '*.Gender' => 'nullable|in:M,F',
            '*.BloodType' => 'nullable|in:A,B,AB,O',
            '*.MatrialStatus' => 'nullable|in:SINGLE,MARRIED',
            '*.Country' => 'nullable|exists:Ms_Country,CountryID',
            '*.DivisionID' => 'nullable|exists:Ms_Division,DivisionID',
            '*.ReferenceID' => 'nullable|exists:Ms_Employee,EmployeeID',
            '*.SupervisorID' => 'nullable|exists:Ms_Employee,EmployeeID',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateSupplier($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.SupplierID.required"] = "Row {$r} : Supplier ID is required!";
            $messages["{$i}.SupplierID.unique"] = "Row {$r} : Supplier ID has already been taken!";
            $messages["{$i}.SupplierID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.SupplierID.max"] = "Row {$r} : Supplier ID maximum characters is 50!";
            $messages["{$i}.SupplierName.max"] = "Row {$r} : Supplier Name maximum characters is 255!";
            $messages["{$i}.ContactPerson.max"] = "Row {$r} : Contact Person maximum characters is 50!";
            $messages["{$i}.NPWP.max"] = "Row {$r} : NPWP maximum characters is 50!";
            $messages["{$i}.City.max"] = "Row {$r} : City maximum characters is 50!";
            $messages["{$i}.Phone.max"] = "Row {$r} : Phone maximum characters is 50!";
            $messages["{$i}.Email.max"] = "Row {$r} : Email maximum characters is 50!";
            $messages["{$i}.Website.max"] = "Row {$r} : Position maximum characters is 50!";
            $messages["{$i}.Term.numeric"] = "Row {$r} : Term must be in a number format!";
            $messages["{$i}.ETA.numeric"] = "Row {$r} : ETA must be in a number format!";
            $messages["{$i}.ETD.numeric"] = "Row {$r} : ETD must be in a number format!";
            $messages["{$i}.Country.exists"] = "Row {$r} : Country ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.SupplierID' => 'required|string|max:50|unique:Ms_Supplier,SupplierID|distinct',
            '*.SupplierName' => 'nullable|string|max:255',
            '*.ContactPerson' => 'nullable|string|max:50',
            '*.NPWP' => 'nullable|max:50',
            '*.City' => 'nullable|string|max:50',
            '*.Phone' => 'nullable|string|max:50',
            '*.Email' => 'nullable|string|max:50',
            '*.Website' => 'nullable|string|max:50',
            '*.Term' => 'nullable|numeric',
            '*.ETA' => 'nullable|numeric',
            '*.ETD' => 'nullable|numeric',
            '*.Country' => 'nullable|exists:Ms_Country,CountryID',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateCustomer($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.CustomerID.required"] = "Row {$r} : Customer ID is required!";
            $messages["{$i}.CustomerID.unique"] = "Row {$r} : Customer ID has already been taken!";
            $messages["{$i}.CustomerID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.CustomerID.max"] = "Row {$r} : Customer ID maximum characters is 50!";
            $messages["{$i}.CustomerName.max"] = "Row {$r} : Customer Name maximum characters is 255!";
            $messages["{$i}.ContactPerson.max"] = "Row {$r} : Contact Person maximum characters is 50!";
            $messages["{$i}.NPWP.max"] = "Row {$r} : NPWP maximum characters is 50!";
            $messages["{$i}.City.max"] = "Row {$r} : City maximum characters is 50!";
            $messages["{$i}.Phone.max"] = "Row {$r} : Phone maximum characters is 50!";
            $messages["{$i}.Email.max"] = "Row {$r} : Email maximum characters is 50!";
            $messages["{$i}.Term.numeric"] = "Row {$r} : Term must be in a number format!";
            $messages["{$i}.ETA.numeric"] = "Row {$r} : ETA must be in a number format!";
            $messages["{$i}.ETD.numeric"] = "Row {$r} : ETD must be in a number format!";
            $messages["{$i}.Country.exists"] = "Row {$r} : Country ID doesn't exist!";
            $messages["{$i}.Birthday.date_format"] = "Row {$r} : Birthday format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.DivisionID.exists"] = "Row {$r} : Division ID doesn't exist!";
            $messages["{$i}.SalesmanID.exists"] = "Row {$r} : Salesman ID doesn't exist!";
            $messages["{$i}.SubDistrictID.exists"] = "Row {$r} : Sub District ID doesn't exist!";
        }

        $validator = Validator::make($rows, [
            '*.CustomerID' => 'required|string|max:50|unique:Ms_Customer,CustomerID|distinct',
            '*.CustomerName' => 'nullable|string|max:255',
            '*.ContactPerson' => 'nullable|string|max:50',
            '*.NPWP' => 'nullable|max:50',
            '*.City' => 'nullable|string|max:50',
            '*.Country' => 'nullable|exists:Ms_Country,CountryID',
            '*.Phone' => 'nullable|string|max:50',
            '*.Email' => 'nullable|string|max:50',
            '*.Term' => 'nullable|numeric',
            '*.ETA' => 'nullable|numeric',
            '*.ETD' => 'nullable|numeric',
            '*.Birthday' => 'nullable|date_format:d-m-Y',
            '*.DivisionID' => 'nullable|exists:Ms_Division,DivisionID',
            '*.SalesmanID' => 'nullable|exists:Ms_Employee,EmployeeID',
            '*.SubDistrictID' => 'required|exists:Ms_SubDistrict,SubDistrictID',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validatePart($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.PartID.required"] = "Row {$r} : Part ID is required!";
            $messages["{$i}.PartID.unique"] = "Row {$r} : Part ID has already been taken!";
            $messages["{$i}.PartID.distinct"] = "Row {$r} : There's a duplicate ID in the sheet!";
            $messages["{$i}.PartID.max"] = "Row {$r} : Part ID maximum characters is 50!";
            $messages["{$i}.PartName.max"] = "Row {$r} : Part Name maximum characters is 255!";
            $messages["{$i}.PartName.unique"] = "Row {$r} : Part Name has already been taken!";
            $messages["{$i}.OtherID.max"] = "Row {$r} : Other ID maximum characters is 255!";
            $messages["{$i}.CategoryID.required"] = "Row {$r} : Category ID is required!";
            $messages["{$i}.CategoryID.exists"] = "Row {$r} : Category ID doesn't exist!";
            $messages["{$i}.SpecificationID.required"] = "Row {$r} : Specification ID is required!";
            $messages["{$i}.SpecificationID.exists"] = "Row {$r} : Specification ID doesn't exist!";
            $messages["{$i}.VariantID.required"] = "Row {$r} : Variant ID is required!";
            $messages["{$i}.VariantID.exists"] = "Row {$r} : Variant ID doesn't exist!";
            $messages["{$i}.InventoryTypeID.required"] = "Row {$r} : Inventory Type ID is required!";
            $messages["{$i}.InventoryTypeID.exists"] = "Row {$r} : Inventory Type ID doesn't exist!";
            $messages["{$i}.MinimumStockBuffer.numeric"] = "Row {$r} : Minimum Stock Buffer must be in a number format!";
            $messages["{$i}.MaximumStockBuffer.numeric"] = "Row {$r} : Maximum Stock Buffer must be in a number format!";
            $messages["{$i}.Unit2Conversion.numeric"] = "Row {$r} : Unit2 Conversion must be in a number format!";
            $messages["{$i}.Unit3Conversion.numeric"] = "Row {$r} : Unit3 Conversion must be in a number format!";
            $messages["{$i}.Unit4Conversion.numeric"] = "Row {$r} : Unit4 Conversion must be in a number format!";
            $messages["{$i}.Unit5Conversion.numeric"] = "Row {$r} : Unit5 Conversion must be in a number format!";
            $messages["{$i}.PartType.in"] = "Row {$r} : Part Type value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.WithSerialNumber.in"] = "Row {$r} : WithSerialNumber value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.Pricing.in"] = "Row {$r} : Pricing value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.Guarantee.in"] = "Row {$r} : Guarantee value is invalid! Please check the detailed rules for the correct values!";
            $messages["{$i}.DeferedWarehouseID.exists"] = "Row {$r} : Defered Warehouse ID doesn't exist!";
            $messages["{$i}.Unit1.required_with"] = "Row {$r} : Unit1 is required when Unit2 is filled!";
            $messages["{$i}.Unit1.exists"] = "Row {$r} : Unit1 ID doesn't exist!";
            $messages["{$i}.Unit2.required_with"] = "Row {$r} : Unit2 is required when Unit2Conversion or Unit3 is filled!";
            $messages["{$i}.Unit2.exists"] = "Row {$r} : Unit2 ID doesn't exist!";
            $messages["{$i}.Unit2Conversion.required_with"] = "Row {$r} : Unit2 Conversion is required when Unit2 or Unit3 is filled!";
            $messages["{$i}.Unit3.required_with"] = "Row {$r} : Unit3 is required when Unit3Conversion or Unit4 is filled!";
            $messages["{$i}.Unit3.exists"] = "Row {$r} : Unit3 ID doesn't exist!";
            $messages["{$i}.Unit3Conversion.required_with"] = "Row {$r} : Unit3 Conversion is required when Unit3 or Unit4 is filled!";
            $messages["{$i}.Unit4.required_with"] = "Row {$r} : Unit4 is required when Unit4Conversion or Unit5 is filled!";
            $messages["{$i}.Unit4.exists"] = "Row {$r} : Unit4 ID doesn't exist!";
            $messages["{$i}.Unit4Conversion.required_with"] = "Row {$r} : Unit4 Conversion is required when Unit4 or unit5 is filled!";
            $messages["{$i}.Unit5.exists"] = "Row {$r} : Unit5 ID doesn't exist!";
            $messages["{$i}.Unit5.required_with"] = "Row {$r} : Unit5 is required when Unit5Conversion is filled!";
            $messages["{$i}.Unit5Conversion.required_with"] = "Row {$r} : Unit5 Conversion is required when Unit5 is filled!";
        }

        // Base rules
        $rules = [
            '*.PartID' => 'required|string|max:50|unique:Ms_Part,PartID|distinct',
            '*.PartName' => 'nullable|string|max:255',
            '*.OtherID' => 'nullable|string|max:255',
            '*.CategoryID' => 'required|exists:Ms_PartCategory,CategoryID',
            '*.SpecificationID' => 'required|exists:Ms_PartSpecification,SpecificationID',
            '*.VariantID' => 'required|exists:Ms_PartVariant,VariantID',
            '*.InventoryTypeID' => 'required|exists:Ms_InventoryType,InventoryTypeID',
            '*.MinimumStockBuffer' => 'nullable|numeric',
            '*.MaximumStockBuffer' => 'nullable|numeric',
            '*.DeferedWarehouseID' => 'nullable|exists:Ms_Warehouse,WarehouseID',
            '*.PartType' => 'nullable|in:S,N',
            '*.WithSerialNumber' => 'nullable|in:1,0',
            '*.Pricing' => 'nullable|in:BasedOnDiscBarometer,BasedOnPrice',
            '*.Guarantee' => 'nullable|in:GUARANTEE_PART,GUARANTEE_SERVICE,GUARANTEE_PART_SERVICE,NON_GUARANTEE',
            '*.Unit1' => 'nullable|required_with:*.Unit2|exists:Ms_Unit,UnitID',
            '*.Unit2' => 'nullable|required_with:*.Unit3,*.Unit2Conversion|exists:Ms_Unit,UnitID',
            '*.Unit2Conversion' => 'nullable|required_with:*.Unit3,*.Unit2|numeric',
            '*.Unit3' => 'nullable|required_with:*.Unit4,*.Unit3Conversion|exists:Ms_Unit,UnitID',
            '*.Unit3Conversion' => 'nullable|required_with:*.Unit3,*.Unit4|numeric',
            '*.Unit4' => 'nullable|required_with:*.Unit5,*.Unit4Conversion|exists:Ms_Unit,UnitID',
            '*.Unit4Conversion' => 'nullable|required_with:*.Unit5,*.Unit4|numeric',
            '*.Unit5' => 'nullable|required_with:*.Unit5Conversion|exists:Ms_Unit,UnitID',
            '*.Unit5Conversion' => 'nullable|required_with:*.Unit5|numeric',
        ];

        // Tambahkan unique rule ke PartName jika setting enabled
        if (ControlPanel::isEnabled('part_name_unique')) {
            $rules['*.PartName'] = 'nullable|string|max:255|unique:Ms_Part,PartName';
        }

        $validator = Validator::make($rows, $rules, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateBeginningStock($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.PartID.required"] = "Row {$r} : Part ID is required!";
            $messages["{$i}.PartID.exists"] = "Row {$r} : Part ID doesn't exist!";
            $messages["{$i}.TransactionDate.required"] = "Row {$r} : Transaction Date is required!";
            $messages["{$i}.TransactionDate.date_format"] = "Row {$r} : Transaction Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.ExpDate.date_format"] = "Row {$r} : Exp Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.WarehouseID.required"] = "Row {$r} : Warehouse ID is required!";
            $messages["{$i}.WarehouseID.exists"] = "Row {$r} : Warehouse ID doesn't exist!";
            $messages["{$i}.Qty.required"] = "Row {$r} : Qty is required!";
            $messages["{$i}.Qty.numeric"] = "Row {$r} : Qty must be in a number format!";
            $messages["{$i}.BeginningBalance.required"] = "Row {$r} : Beginning Balance is required!";
            $messages["{$i}.BeginningBalance.numeric"] = "Row {$r} : Beginning Balance must be in a number format!";
        }

        $validator = Validator::make($rows, [
            '*.PartID' => 'required|exists:Ms_Part,PartID',
            '*.TransactionDate' => 'required|date_format:d-m-Y',
            '*.ExpDate' => 'nullable|date_format:d-m-Y',
            '*.WarehouseID' => 'required|exists:Ms_Warehouse,WarehouseID',
            '*.Qty' => 'required|numeric',
            '*.BeginningBalance' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validateHutang($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.BalanceNo.required"] = "Row {$r} : Balance No is required!";
            $messages["{$i}.BalanceNo.unique"] = "Row {$r} : Balance No already exists!";
            $messages["{$i}.BalanceNo.distinct"] = "Row {$r} : There's a duplicate Balance No in the sheet!";
            $messages["{$i}.BalanceNo.max"] = "Row {$r} : Balance No maximum characters is 50!";
            $messages["{$i}.TransactionDate.required"] = "Row {$r} : Transaction Date is required!";
            $messages["{$i}.TransactionDate.date_format"] = "Row {$r} : Transaction Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.DueDate.required"] = "Row {$r} : Due Date is required!";
            $messages["{$i}.DueDate.date_format"] = "Row {$r} : Due Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.SupplierID.required"] = "Row {$r} : Supplier ID is required!";
            $messages["{$i}.SupplierID.exists"] = "Row {$r} : Supplier ID doesn't exist!";
            $messages["{$i}.CurrencyID.required"] = "Row {$r} : Currency ID is required!";
            $messages["{$i}.CurrencyID.exists"] = "Row {$r} : Currency ID doesn't exist!";
            $messages["{$i}.Rate.required"] = "Row {$r} : Rate is required!";
            $messages["{$i}.Rate.numeric"] = "Row {$r} : Rate must be in a number format!";
            $messages["{$i}.Amount.required"] = "Row {$r} : Amount is required!";
            $messages["{$i}.Amount.numeric"] = "Row {$r} : Amount must be in a number format!";
        }

        $validator = Validator::make($rows, [
            '*.BalanceNo' => 'required|string|max:50|unique:Buku_Hutang,BalanceNo|distinct',
            '*.SupplierID' => 'required|exists:Ms_Supplier,SupplierID',
            '*.TransactionDate' => 'required|date_format:d-m-Y',
            '*.DueDate' => 'nullable|date_format:d-m-Y',
            '*.CurrencyID' => 'required|exists:Ms_Currency,CurrencyID',
            '*.Rate' => 'required|numeric',
            '*.Amount' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }

    public function validatePiutang($rows)
    {
        $messages = [];
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $messages["{$i}.BalanceNo.required"] = "Row {$r} : Balance No is required!";
            $messages["{$i}.BalanceNo.max"] = "Row {$r} : Balance No maximum characters is 50!";
            $messages["{$i}.BalanceNo.unique"] = "Row {$r} : Balance No already exists!";
            $messages["{$i}.BalanceNo.distinct"] = "Row {$r} : There's a duplicate Balance No in the sheet!";
            $messages["{$i}.TransactionDate.required"] = "Row {$r} : Transaction Date is required!";
            $messages["{$i}.TransactionDate.date_format"] = "Row {$r} : Transaction Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.DueDate.required"] = "Row {$r} : Due Date is required!";
            $messages["{$i}.DueDate.date_format"] = "Row {$r} : Due Date format is invalid! Please check the detailed rules for the correct format!";
            $messages["{$i}.CustomerID.required"] = "Row {$r} : Customer ID is required!";
            $messages["{$i}.CustomerID.exists"] = "Row {$r} : Customer ID doesn't exist!";
            $messages["{$i}.CurrencyID.required"] = "Row {$r} : Currency ID is required!";
            $messages["{$i}.CurrencyID.exists"] = "Row {$r} : Currency ID doesn't exist!";
            $messages["{$i}.Rate.required"] = "Row {$r} : Rate is required!";
            $messages["{$i}.Rate.numeric"] = "Row {$r} : Rate must be in a number format!";
            $messages["{$i}.Amount.required"] = "Row {$r} : Amount is required!";
            $messages["{$i}.Amount.numeric"] = "Row {$r} : Amount must be in a number format!";
        }

        $validator = Validator::make($rows, [
            '*.BalanceNo' => 'required|string|max:50|unique:Buku_Piutang,BalanceNo|distinct',
            '*.CustomerID' => 'required|exists:Ms_Customer,CustomerID',
            '*.TransactionDate' => 'required|date_format:d-m-Y',
            '*.DueDate' => 'nullable|date_format:d-m-Y',
            '*.CurrencyID' => 'required|exists:Ms_Currency,CurrencyID',
            '*.Rate' => 'required|numeric',
            '*.Amount' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray()
            ];
        }

        return [
            'success' => true
        ];
    }
}
