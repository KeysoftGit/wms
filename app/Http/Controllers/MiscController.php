<?php

namespace App\Http\Controllers;

use App\Models\ControlPanel;
use App\Models\BukuStock;
use App\Models\MsAutoJournalDT;
use App\Models\MsBatchNo;
use App\Models\MsCOA;
use App\Models\MsCountry;
use App\Models\MsCurrency;
use App\Models\MsCustomer;
use App\Models\MsCustomerShipment;
use App\Models\MsDistrict;
use App\Models\MsDivision;
use App\Models\MsEmployee;
use App\Models\MsFixedAsset;
use App\Models\MsFixedAssetCategory;
use App\Models\MsFixedAssetLocation;
use App\Models\MsInventoryType;
use App\Models\MsPart;
use App\Models\MsPartCategory;
use App\Models\MsPartSpecification;
use App\Models\MsPartUnit;
use App\Models\MsPartVariant;
use App\Models\MsSubDistrict;
use App\Models\MsSupplier;
use App\Models\MsUnit;
use App\Models\MsUser;
use App\Models\MsVehicle;
use App\Models\MsWarehouse;
use App\Models\TransStockOpnameHD;
use App\Services\WarehouseAccessCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MiscController extends Controller
{
    public function getDistrict(Request $request)
    {
        try {
            $districts = MsDistrict::query();

            $data = [];
            foreach ($districts->get() as $district) {
                $data[] = [
                    'id' => $district->DistrictID,
                    'text' => $district->DistrictName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getSubdistrict(Request $request)
    {
        try {
            $subdistricts = MsSubDistrict::query();

            $data = [];
            foreach ($subdistricts->get() as $subdistrict) {
                $data[] = [
                    'id' => $subdistrict->SubDistrictID,
                    'text' => $subdistrict->SubDistrictName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getVehicle(Request $request)
    {
        try {
            $vehicles = MsVehicle::query();

            if ($request->get('search')) {
                $vehicles->where(function ($query) use ($request) {
                    return $query->where('VehicleID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('VehicleName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            foreach ($vehicles->get() as $vehicle) {
                $data[] = [
                    'id' => $vehicle->VehicleID,
                    'text' => $vehicle->VehicleID . ($vehicle->VehicleName ? ' - ' . $vehicle->VehicleName : ''),
                ];
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getDivision(Request $request)
    {
        try {
            $divisions = MsDivision::query();
            WarehouseAccessCriteria::applyDivisions($divisions);

            if ($request->get('id')) {
                $divisions->where('DivisionID', '!=', $request->input('id'));
            }

            $data = [];
            foreach ($divisions->get() as $division) {
                $data[] = [
                    'id' => $division->DivisionID,
                    'text' => $division->DivisionName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getCountry(Request $request)
    {
        try {
            $countries = MsCountry::query();

            $data = [];
            foreach ($countries->get() as $country) {
                $data[] = [
                    'id' => $country->CountryID,
                    'text' => $country->CountryName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getCurrency(Request $request)
    {
        try {
            $currencies = MsCurrency::query();

            if ($request->get('select2')) {
                $currencies->where(function ($query) use ($request) {
                    return $query->where('CurrencyID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('CurrencyName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            if ($request->get('all')) {
                $data[] = [
                    'id' => '',
                    'text' => 'All',
                ];
            }
            foreach ($currencies->get() as $currency) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $currency->CurrencyID,
                        'text' => $currency->CurrencyID . ($currency->CurrencyName != null ? ' - ' . $currency->CurrencyName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $currency->CurrencyID,
                        'text' => $currency->CurrencyName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getAccount(Request $request)
    {
        try {
            $accounts = MsCOA::query();

            if ($request->get('id')) {
                $accounts->where('AccountNo', '!=', $request->input('id'));
            }

            if ($request->get('type')) {
                $accounts->where('AccountType', $request->get('type'));
            }

            if ($request->get('parent')) {
                $accounts->where('Parent', '!=', '0');
            }

            if ($request->get('header')) {
                $accounts->where('Header', '!=', '1');
            }

            if ($request->get('parent_only')) {
                $accounts->where('Parent', '0');
            }

            if ($request->get('select2')) {
                $accounts->where(function ($query) use ($request) {
                    return $query->where('AccountNo', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('AccountName', 'like', '%' . $request->query('search') . '%');
                });
            }


            $data = [];
            foreach ($accounts->get() as $account) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $account->AccountNo,
                        'text' => $account->AccountNo . ($account->AccountName != null ? ' - ' . $account->AccountName : ''),
                        'currency' => $account->CurrencyID,
                    ];
                } else {
                    $data[] = [
                        'id' => $account->AccountNo,
                        'text' => $account->AccountName,
                        'currency' => $account->CurrencyID,
                        'type' => $account->AccountType
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getEmployee(Request $request)
    {
        try {
            $employees = MsEmployee::query();

            if ($request->get('id')) {
                $employees->where('EmployeeID', '!=', $request->input('id'));
            }

            if ($request->get('driver')) {
                $employees->where('Position', 'Driver');
            }

            if ($request->get('select2')) {
                $employees->where(function ($query) use ($request) {
                    return $query->where('EmployeeID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('FirstName', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('LastName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            foreach ($employees->get() as $employee) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $employee->EmployeeID,
                        'text' => $employee->EmployeeID . ($employee->FirstName != null ? ' - ' . $employee->FirstName . ' ' . $employee->LastName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $employee->EmployeeID,
                        'text' => $employee->FirstName . ' ' . $employee->LastName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getSupplier(Request $request)
    {
        try {
            $suppliers = MsSupplier::query();

            if ($request->get('id')) {
                $suppliers->where('SupplierID', '!=', $request->input('id'));
            }

            if ($request->get('select2')) {
                $suppliers->where(function ($query) use ($request) {
                    return $query->where('SupplierID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('SupplierName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            foreach ($suppliers->get() as $supplier) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $supplier->SupplierID,
                        'text' => $supplier->SupplierID . ($supplier->SupplierName != null ? ' - ' . $supplier->SupplierName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $supplier->SupplierID,
                        'text' => $supplier->SupplierName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getCustomer(Request $request)
    {
        try {
            $customers = MsCustomer::query();

            if ($request->get('id')) {
                $customers->where('CustomerID', '!=', $request->input('id'));
            }

            if ($request->get('select2')) {
                $customers->where(function ($query) use ($request) {
                    return $query->where('CustomerID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('CustomerName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            foreach ($customers->get() as $customer) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $request->get('realID') ? $customer->id : $customer->CustomerID,
                        'text' => $customer->CustomerID . ($customer->CustomerName != null ? ' - ' . $customer->CustomerName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $request->get('realID') ? $customer->id : $customer->CustomerID,
                        'text' => $customer->CustomerName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getShipmentAddress(Request $request)
    {
        try {
            $shipments = MsCustomerShipment::where('CustomerID', $request->get('id'));

            if ($request->get('numId')) {
                $customer = MsCustomer::where('id', $request->get('id'))->first();
                $shipments = MsCustomerShipment::where('CustomerID', $customer->CustomerID);
            }

            $data = [];
            foreach ($shipments->get() as $shipment) {
                $data[] = [
                    'id' => $shipment->Shipment,
                    'text' => $shipment->Shipment . ($shipment->Address != null ? ' - ' . $shipment->Address : ''),
                ];
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }



    public function getWarehouse(Request $request)
    {
        try {
            $warehouses = ControlPanel::isEnabled('implement_user_warehouse_mapping')
                ? MsWarehouse::accessibleTo(auth()->user())
                : MsWarehouse::query();

            if ($request->get('id')) {
                $warehouses->where('WarehouseID', '!=', $request->input('id'));
            }

            $data = [];
            foreach ($warehouses->get() as $warehouse) {
                $data[] = [
                    'id' => $warehouse->WarehouseID,
                    'text' => $warehouse->WarehouseName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getUnit(Request $request)
    {
        try {
            $units = MsUnit::query();

            $data = [];
            foreach ($units->get() as $unit) {
                $data[] = [
                    'id' => $unit->UnitID,
                    'text' => $unit->UnitName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getCategory(Request $request)
    {
        try {
            $categories = MsPartCategory::query();

            $data = [];
            foreach ($categories->get() as $category) {
                $data[] = [
                    'id' => $category->CategoryID,
                    'text' => $category->CategoryName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getSpecification(Request $request)
    {
        try {
            $specs = MsPartSpecification::query();

            $data = [];
            foreach ($specs->get() as $spec) {
                $data[] = [
                    'id' => $spec->SpecificationID,
                    'text' => $spec->SpecificationName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getVariant(Request $request)
    {
        try {
            $variants = MsPartVariant::query();

            $data = [];
            foreach ($variants->get() as $variant) {
                $data[] = [
                    'id' => $variant->VariantID,
                    'text' => $variant->VariantName,
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getType(Request $request)
    {
        try {
            $types = MsInventoryType::query();

            if ($request->get('select2')) {
                $types->where(function ($query) use ($request) {
                    return $query->where('InventoryTypeID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('InventoryTypeName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];

            if ($request->get('all')) {
                $data[] = [
                    'id' => '',
                    'text' => 'All',
                ];
            }

            foreach ($types->get() as $type) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $type->InventoryTypeID,
                        'text' => $type->InventoryTypeID . ($type->InventoryTypeName != null ? ' - ' . $type->InventoryTypeName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $type->InventoryTypeID,
                        'text' => $type->InventoryTypeName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getFixedAssetLocation(Request $request)
    {
        try {
            $locations = MsFixedAssetLocation::query();

            if ($request->get('except')) {
                $locations->whereNot('LocationID', $request->get('except'));
            }

            if ($request->get('select2')) {
                $locations->where(function ($query) use ($request) {
                    return $query->where('LocationID', 'like', '%' . $request->query('search') . '%')
                        ->orWhere('LocationName', 'like', '%' . $request->query('search') . '%');
                });
            }

            $data = [];
            foreach ($locations->get() as $location) {
                if ($request->get('select2')) {
                    $data[] = [
                        'id' => $location->LocationID,
                        'text' => $location->LocationID . ($location->LocationName != null ? ' - ' . $location->LocationName : ''),
                    ];
                } else {
                    $data[] = [
                        'id' => $location->LocationID,
                        'text' => $location->LocationName,
                    ];
                }
            }

            if ($request->get('select2')) {
                return response($data);
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getFixedAssetCategory(Request $request)
    {
        try {
            $categories = MsFixedAssetCategory::query();

            $data = [];
            foreach ($categories->get() as $category) {
                $data[] = [
                    'id' => $category->CategoryID,
                    'text' => $category->CategoryName,
                    'type' => $category->FixedAssetType,
                    'ageYear' => $category->AgeInYear,
                    'ageMonth' => $category->AgeInMonth,
                    'dMethod' => $category->DepreciationMethod,
                    'faAccount' => $category->assetAccount ? $category->assetAccount->AccountNo . ($category->assetAccount->AccountName != null ? ' - ' . $category->assetAccount->AccountName : '') : '',
                    'deAccount' => $category->deAccount ? $category->deAccount->AccountNo . ($category->deAccount->AccountName != null ? ' - ' . $category->deAccount->AccountName : '') : '',
                    'adAccount' =>  $category->adAccount ? $category->adAccount->AccountNo . ($category->adAccount->AccountName != null ? ' - ' . $category->adAccount->AccountName : '') : '',
                ];
            }

            return response([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }





    public function getJournalType(Request $request)
    {
        try {
            $journals = MsAutoJournalDT::where('AccountType', 'like', '%' . $request->query('search') . '%')
                ->distinct();

            $data = [];
            foreach ($journals->get('AccountType') as $journal) {
                $data[] = [
                    'id' => $journal->AccountType,
                    'text' => $journal->AccountType,
                ];
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    // PO

    public function getPart(Request $request)
    {
        $parts = MsPart::where('PartID', 'like', '%' . $request->query('search') . '%')
            ->orWhere('PartName', 'like', '%' . $request->query('search') . '%');

        $data = [];
        foreach ($parts->get() as $part) {
            $data[] = [
                'id' => $part->PartID,
                'text' => $part->PartID . ($part->PartName ? ' - ' . $part->PartName : ''),
                'WithSerialNo' => (int) $part->WithSerialNo,
            ];
        }

        return response($data);
    }

    public function getBatchNoSelect2(Request $request)
    {
        $batchNos = MsBatchNo::where('PartID', $request->get('part_id'))
            ->where('BatchNo', 'like', '%' . $request->query('search') . '%')
            ->orderBy('BatchNo')
            ->limit(20)
            ->get();

        $data = [];
        foreach ($batchNos as $batchNo) {
            $data[] = [
                'id' => $batchNo->BatchNo,
                'text' => $batchNo->BatchNo,
            ];
        }

        return response($data);
    }

    public function getStockByControlPanel(Request $request)
    {
        $partId = $request->get('part');
        $warehouseId = $request->get('warehouse');

        $stock = BukuStock::where('PartID', $partId)
            ->where('WarehouseID', $warehouseId)
            ->sum('Qty');

        return response([
            'stock' => (float) ($stock ?? 0),
            'source' => 'buku_stock',
        ]);
    }



    public function getPart_withScanner(Request $request)
    {
        $search = trim($request->query('search'));
        $parts  = MsPart::where('PartID', 'LIKE', "%$search%")
            ->orWhere('PartName', 'LIKE', "%$search%");

        $data = [];
        if ($search) {
            foreach ($parts->get() as $part) {
                $data[] = [
                    'id' => $part->PartID,
                    'text' => $part->PartID . ($part->PartName ? ' - ' . $part->PartName : ''),
                ];
            }
        }

        return response($data);
    }



    public function getPart_tanpaSerialNumber(Request $request)
    {
        $search = trim($request->query('search'));
        $parts  = MsPart::where('WithSerialNo', '=', 0)
            ->where(function ($query) use ($search) {
                $query->where('PartID', 'LIKE', "%$search%");
                $query->orWhere('PartName', 'LIKE', "%$search%");
            });

        $data = [];
        if ($search) {
            foreach ($parts->get() as $part) {
                $data[] = [
                    'id' => $part->PartID,
                    'text' => $part->PartID . ($part->PartName ? ' - ' . $part->PartName : ''),
                ];
            }
        }

        return response($data);
    }



    public function getDivisionSelect2(Request $request)
    {
        $divisions = MsDivision::query();
        WarehouseAccessCriteria::applyDivisions($divisions);

        $divisions->where(function ($query) use ($request) {
            $query->where('DivisionID', 'like', '%' . $request->query('search') . '%')
                ->orWhere('DivisionName', 'like', '%' . $request->query('search') . '%');
        });

        if ($request->get('id')) {
            $divisions->where('DivisionID', '!=', $request->input('id'));
        }

        $data = [];
        foreach ($divisions->get() as $division) {
            $data[] = [
                'id' => $division->DivisionID,
                'text' => $division->DivisionID . ($division->DivisionName ? ' - ' . $division->DivisionName : ''),
            ];
        }

        return response($data);
    }

    public function getWarehouseSelect2(Request $request)
    {
        if ($request->boolean('stock_monitor')) {
            $warehouses = MsWarehouse::query();
            WarehouseAccessCriteria::applyForStockMonitoring(
                $warehouses,
                $warehouses->getModel()->qualifyColumn('WarehouseID')
            );
        } else {
            $warehouses = ControlPanel::isEnabled('implement_user_warehouse_mapping')
                && !$request->boolean('transfer_from')
                ? MsWarehouse::accessibleTo(auth()->user())
                : MsWarehouse::query();
        }

        $warehouses->where(function ($query) use ($request) {
            $query->where('WarehouseID', 'like', '%' . $request->query('search') . '%')
                ->orWhere('WarehouseName', 'like', '%' . $request->query('search') . '%');
        });

        if ($request->boolean('parent_only')) {
            $warehouses->where(function ($query) {
                $query->whereNull('ParentID')
                    ->orWhere('ParentID', '');
            });
        }

        $data = [];
        if ($request->get('all')) {
            $data[] = [
                'id' => '',
                'text' => 'All',
            ];
        }

        foreach ($warehouses->get() as $warehouse) {
            if ($request->get('opname')) {
                $check = TransStockOpnameHD::where('WarehouseID', $warehouse->WarehouseID)
                    ->where('Status', '!=', 'DONE')
                    ->first();

                if (!$check || $request->get('edit') == $warehouse->WarehouseID) {
                    $data[] = [
                        'id' => $warehouse->WarehouseID,
                        'text' => $warehouse->WarehouseID . ($warehouse->WarehouseName ? ' - ' . $warehouse->WarehouseName : ''),
                    ];
                }
            } else {
                $data[] = [
                    'id' => $warehouse->WarehouseID,
                    'text' => $warehouse->WarehouseID . ($warehouse->WarehouseName ? ' - ' . $warehouse->WarehouseName : ''),
                ];
            }
        }

        return response($data);
    }

    public function getPartUnitSelect2(Request $request)
    {
        $units = MsPartUnit::where('PartID', $request->get('id'))
            ->where('UnitID2', 'like', '%' . $request->query('search') . '%')
            ->orderBy('Sequence', 'asc')->get();

        $data = [];
        foreach ($units as $unit) {
            $data[] = [
                'id' => $unit->UnitID2,
                'text' => $unit->UnitID2 . ($unit->unit2->UnitName ? ' - ' . $unit->unit2->UnitName : ''),
            ];
        }

        return response($data);
    }

    public function getUnitConversion(Request $request)
    {
        $partId = $request->get('part_id');
        $unitId = $request->get('unit_id');
        $warehouseId = $request->get('warehouse_id');
        $batchNo = $request->input('batch_no');

        $conversion = null;

        if ($partId && $unitId && ($warehouseId !== null && $warehouseId !== '' || $batchNo !== null && $batchNo !== '')) {
            $stockQuery = BukuStock::where('PartID', $partId)
                ->where('UnitID2', $unitId)
                ->whereNotNull('Qty2')
                ->where('Qty2', '<>', 0);

            if ($warehouseId !== null && $warehouseId !== '') {
                $stockQuery->where('WarehouseID', $warehouseId);
            }

            if ($batchNo === '__NULL__') {
                $stockQuery->whereNull('BatchNo');
            } elseif ($batchNo !== null && $batchNo !== '') {
                $stockQuery->where('BatchNo', $batchNo);
            }

            $rows = $stockQuery->get();
            if ($rows->isNotEmpty()) {
                $qtyBase = 0;
                $qtySource = 0;
                foreach ($rows as $row) {
                    $qtyBase += abs((float) $row->Qty);
                    $qtySource += abs((float) $row->Qty2);
                }

                if ($qtySource > 0.000001) {
                    $conversion = $qtyBase / $qtySource;
                }
            }
        }

        if ($conversion === null) {
            $unit = MsPartUnit::where('PartID', $partId)->where('UnitID2', $unitId)->first();
            $conversion = $unit ? $unit->Conversion : null;
        }

        return response([
            'conversion' => $conversion
        ]);
    }

    public function getPartContent(Request $request)
    {
        $part = MsPart::where('PartID', $request->get('id'))->first();

        return response([
            'vat' => $part->VAT2 ?? 0,
            'image' => $part->Image2 ? asset('storage/part/' . $part->Image2) : '',
        ]);
    }



    public function getFixedAsset(Request $request)
    {
        $fixedassets = MsFixedAsset::where('Status', 'ACTIVE')
            ->where(function ($query) use ($request) {
                $query->where('FixedAssetCode', 'like', '%' . $request->query('search') . '%')
                    ->orWhere('FixedAssetName', 'like', '%' . $request->query('search') . '%');
            });

        $data = [];

        if ($request->get('edit')) {
            $edit = MsFixedAsset::where('FixedAssetCode', $request->get('edit'))->first();
            $data[] = [
                'id' => $edit->FixedAssetCode,
                'text' => $edit->FixedAssetCode . ($edit->FixedAssetName ? ' - ' . $edit->FixedAssetName : ''),
            ];
        }

        foreach ($fixedassets->get() as $fixedasset) {
            $data[] = [
                'id' => $fixedasset->FixedAssetCode,
                'text' => $fixedasset->FixedAssetCode . ($fixedasset->FixedAssetName ? ' - ' . $fixedasset->FixedAssetName : ''),
            ];
        }

        return response($data);
    }


    public function getUser(Request $request)
    {
        $users = MsUser::where('isAdmin', 0)
            ->where(function ($query) use ($request) {
                $query->where('UserName', 'like', '%' . $request->query('search') . '%');
            })->whereHas('employee', function ($query) use ($request) {
                $query->where('FirstName', 'like', '%' . $request->query('search') . '%')
                    ->orWhere('LastName', 'like', '%' . $request->query('search') . '%');
            });

        $data = [];

        foreach ($users->get() as $user) {
            $data[] = [
                'id' => $user->UserID,
                'text' => $user->UserName . ($user->employee->FirstName != null ? ' - ' . $user->employee->FirstName . ' ' . $user->employee->LastName : ''),
            ];
        }

        return response($data);
    }


    public function getPartUsageUnits($id)
    {
        $part = MsPart::with('units.unit2')->find($id);

        if (!$part) {
            return response()->json([]);
        }

        $units = $part->units
            ->filter(fn($u) => $u->unit2 && $u->Conversion == 1)
            ->map(fn($u) => [
                'UnitID'     => $u->unit2->UnitID,
                'UnitName'   => $u->unit2->UnitName,
                'Conversion' => $u->Conversion,
            ])
            ->values();

        return response()->json($units);
    }
}
