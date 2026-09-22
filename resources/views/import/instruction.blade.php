@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Import Master</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-center text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <h1 class="h3 fw-bold mb-5">
                    Import Master
                </h1>

                <div class="block block-rounded">
                    <div class="block-content">
                        <h3 class="fw-bold mb-0">Instruction</h3>
                        <p class="text-info">If a field on the template is not mentioned here, that means it doesn't have any specific rules.</p>

                        <div class="table-responsive">
                            <table class="table table-borderless">
                                {{-- Country --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Country</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CountryID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 3</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CountryName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- Currency --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Currency</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CurrencyID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 3</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CurrencyName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- Vehicle --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Vehicle</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">VehicleID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">VehicleName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">LicenseNumber</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 50</td>
                                </tr>


                                {{-- Division --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Division</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DivisionID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DivisionName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SubDivisionID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference another <b>DivisionID</b> from Master Division. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">StaffInChargeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>

                                {{-- Employee --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Employee</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">EmployeeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">FirstName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">LastName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BirthDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Gender</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        M = Male<br>
                                        F = Female
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BloodType</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>A, B, AB, O</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">MaritalStatus</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>SINGLE, MARRIED</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">IDNumber</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">NPWP</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">City</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Country</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>CountryID</b> from Master Country. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Phone1</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Phone2</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Email</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">HireDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ActiveDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Position</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DivisionID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>DivisionID</b> from Master Division. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ReferenceID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference another <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SupervisorID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference another <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BankID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BankName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BankAccountNo</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BankAccountOwner</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 255</td>
                                </tr>

                                {{-- Supplier --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Supplier</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SupplierID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SupplierName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ContactPerson</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">NPWP</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">City</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Country</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>CountryID</b> from Master Country. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Phone</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Email</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Website</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Term</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ETA</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ETD</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>

                                {{-- Customer --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Customer</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CustomerID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CustomerName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Birthday</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ContactPerson</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">NPWP</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">City</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Country</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>CountryID</b> from Master Country. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Phone</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Email</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Term</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ETA</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ETD</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DivisionID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>DivisionID</b> from Master Division. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SalesmanID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SubDistrictID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>SubDistrictID</b> from Master Sub District. <span class="fw-bold text-danger">Must already exists within app!</span><br>
                                    </td>
                                </tr>




                                {{-- COA --}}
                                <tr class="instruction-header">
                                    <td colspan="3">COA</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AccountNo</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AccountName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Parent</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference another <b>AccountNo</b> from Master Chart of Accounts. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                        If doesn't have parent, fill it with "0"
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Currency</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>CurrencyID</b> from Master Currency. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AccountType</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>ACCOUNTPAYABLE,ACCOUNTRECEIVABLE, ACCUMULATEDDEPRECIATION, CASHANDBANK, COSTOFGOODSSOLD, EQUITY ,EXPENSES, FIXEDASSET, INVENTORY, LONGTERMPAYABLE, OTHERASSET, OTHERCURRENTASSET, OTHERCURRENTLIABILITY, OTHEREXPENSES, OTHERINCOME, REVENUE</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Header</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        1 = Header<br>
                                        0 = Detail
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">NormalBalance</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        D = Debit<br>
                                        C = Credit
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BalanceSheet</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        1 = Balance Sheet Report<br>
                                        0 = Gain Loss Report
                                    </td>
                                </tr>




                                {{-- FACategory --}}
                                <tr class="instruction-header">
                                    <td colspan="3">FACategory</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CategoryID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CategoryName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">FixedAssetType</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>TANGIBLE, INTAGIBLE</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AgeInYear</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AgeInMonth</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DepreciationMethod</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        SL = Single Line<br>
                                        DD = Double Decline<br>
                                        ND = No Depreciation
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">FixedAssetAccount</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>AccountNo</b> from Master Chart of Account. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DepreciationExpenseAccount</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>AccountNo</b> from Master Chart of Account. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">AccumulatedDepreciationAccount</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>AccountNo</b> from Master Chart of Account. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                {{-- FALocation --}}
                                <tr class="instruction-header">
                                    <td colspan="3">FALocation</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">LocationID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">LocationName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">StaffInChargeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>




                                {{-- Warehouse --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Warehouse</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">WarehouseID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">WarehouseName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ParentID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference another <b>WarehouseID</b> from Master Warehoyse. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">StaffInChargeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>EmployeeID</b> from Master Employee. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>




                                {{-- Unit --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Unit</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">UnitID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">UnitName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- PartCategory --}}
                                <tr class="instruction-header">
                                    <td colspan="3">PartCategory</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CategoryID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 4</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CategoryName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- PartSpecification --}}
                                <tr class="instruction-header">
                                    <td colspan="3">PartSpecification</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SpecificationID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 2</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SpecificationName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- PartVariant --}}
                                <tr class="instruction-header">
                                    <td colspan="3">PartVariant</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">VariantID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 2</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">VariantName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                {{-- InventoryType --}}
                                <tr class="instruction-header">
                                    <td colspan="3">InventoryType</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">InventoryTypeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">InventoryTypeName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>

                                {{-- Part --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Part</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">PartID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">PartName</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">OtherID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 255</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CategoryID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>CategoryID</b> from Master Part Category. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SpecificationID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>SpecificationID</b> from Master Part Specification. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">VariantID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>VariantID</b> from Master Part Variant. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">InventoryTypeID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>InventoryTypeID</b> from Master Inventory Type. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">MinimumStockBuffer</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">MaximumStockBuffer</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DeferedWarehouseID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>WarehouseID</b> from Master Warehouse. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">PartType</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        S = Stock<br>
                                        N = Service
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">WithSerialNumber</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td class="fw-bold">
                                        1 = Yes<br>
                                        0 = No
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Pricing</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>BasedOnDiscBarometer, BasedOnPrice</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Guarantee</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be one of these : <br>
                                        <b>GUARANTEE_PART, GUARANTEE_SERVICE, GUARANTEE_PART_SERVICE, NON_GUARANTEE</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit1</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>UnitID</b> from Master Unit. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit2</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>UnitID</b> from Master Unit. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                        <b>Unit1 must be filled if you want to use Unit2! Unit2Conversion must also be filled!</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit2Conversion</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit3</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>UnitID</b> from Master Unit. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                        <b>Unit2 must be filled if you want to use Unit3! Unit3Conversion must also be filled!</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit3Conversion</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit4</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>UnitID</b> from Master Unit. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                        <b>Unit3 must be filled if you want to use Unit4! Unit4Conversion must also be filled!</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit4Conversion</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit5</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Reference <b>UnitID</b> from Master Unit. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span><br>
                                        <b>Unit4 must be filled if you want to use Unit5! Unit5Conversion must also be filled!</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit5Conversion</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">Must be a number</td>
                                </tr>


                                {{-- Beginning Stock --}}
                                <tr class="instruction-header">
                                    <td colspan="3">BeginningStock</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">PartID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>PartID</b> from Master Part. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">TransactionDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DueDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">WarehouseID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>WarehouseID</b> from Master Warehouse. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Qty</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BeginningBalance</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>

                                {{-- Hutang --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Hutang</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BalanceNo</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">TransactionDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">SupplierID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>SupplierID</b> from Master Supplier. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DueDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CurrencyID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>CurrencyID</b> from Master Currency. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Rate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Amount</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>


                                {{-- Piutang --}}
                                <tr class="instruction-header">
                                    <td colspan="3">Piutang</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BalanceNo</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Max character 50</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">TransactionDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CustomerID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>CustomerID</b> from Master Customer. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">DueDate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>
                                        Must be in the format of <b>"DD-MM-YYYY"</b><br>
                                        Change cell format to "Text" if it doesn't work
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">CurrencyID</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td style="width: 100%">
                                        Reference <b>CurrencyID</b> from Master Currency. <span class="fw-bold text-danger">Must already exists within app (Added/Imported)!</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Rate</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Amount</td>
                                    <td style="width: 10px" class="text-center">:</td>
                                    <td>Must be a number</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @include('import.modal')
        @include('import.error')
    </div>
    <!-- END Hero -->


@endsection

@section('styles')
    <style>
        .instruction-header {
            border-radius: 10px;
            background-color: #0a74a6;
            color: white;
            font-weight: bold;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->

    <!-- Page JS Code -->
    <script>

    </script>
@endsection
