<?php

use App\Models\MsPart;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DynamicReportController;
use App\Http\Controllers\PrintBridgeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Auth Route
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/login-portal', [LoginController::class, 'loginPortal'])->name('login.portal');
Route::post('/login/submit', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//Route::get('/create-admin', function (){
//   \App\Models\MsUser::create([
//       'UserID' => 'Admin',
//       'UserName' => 'Admin',
//       'Password' => '12345',
//       'EmployeeID' => '',
//       'Active' => 1,
//       'CreatedBy' => 'Admin',
//       'WebPassword' => \Illuminate\Support\Facades\Hash::make('12345')
//   ]);
//});

//Route::get('/setrole', function (){
//    $user = \App\Models\MsUser::find('Admin');
//    $user->givePermissionTo('admin');
//});
//
Route::get('/permissions', function () {
    $menus = \App\Models\Menu::all();

    foreach ($menus as $menu) {
        $actions = explode(',', $menu->actions);

        foreach ($actions as $action) {
            $permission = $menu->Name . '.' . $action;
            $check = \Spatie\Permission\Models\Permission::where('name', $permission)->first();
            if (!$check) {
                \Spatie\Permission\Models\Permission::create([
                    'name' => $permission,
                ]);
            }
        }
    }
    //    \Spatie\Permission\Models\Permission::create([
    //        'name' => 'admin',
    //    ]);
});

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::get('/print-bridge', [PrintBridgeController::class, 'redirect'])->name('print_bridge');
    Route::get('/print-bridge/temp/{token}/download', [PrintBridgeController::class, 'download'])
        ->name('print_bridge.download');
    Route::post('/print-bridge/temp/{token}/cleanup', [PrintBridgeController::class, 'cleanup'])
        ->name('print_bridge.cleanup');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart_data');
    Route::get('/password', [PasswordController::class, 'index'])->name('password');
    Route::post('/password/update', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/helper/available-stock-details', [HelperController::class, 'getAvailableStockDetails'])
        ->name('helper.available_stock_details');
    Route::get('/helper/fifo-allocation', [HelperController::class, 'getFifoAllocation'])
        ->name('helper.fifo_allocation');

    ///////////////////////////////
    //MISC                      //
    //////////////////////////////
    Route::get('/misc/district', [MiscController::class, 'getDistrict'])->name('misc.district');
    Route::get('/misc/subdistrict', [MiscController::class, 'getSubdistrict'])->name('misc.subdistrict');
    Route::get('/misc/vehicle', [MiscController::class, 'getVehicle'])->name('misc.vehicle');
    Route::get('/misc/division', [MiscController::class, 'getDivision'])->name('misc.division');
    Route::get('/misc/employee', [MiscController::class, 'getEmployee'])->name('misc.employee');
    Route::get('/misc/supplier', [MiscController::class, 'getSupplier'])->name('misc.supplier');
    Route::get('/misc/customer', [MiscController::class, 'getCustomer'])->name('misc.customer');
    Route::get('/misc/shipment', [MiscController::class, 'getShipmentAddress'])->name('misc.shipment');
    Route::get('/misc/account', [MiscController::class, 'getAccount'])->name('misc.account');
    Route::get('/misc/country', [MiscController::class, 'getCountry'])->name('misc.country');
    Route::get('/misc/currency', [MiscController::class, 'getCurrency'])->name('misc.currency');
    Route::get('/misc/warehouse', [MiscController::class, 'getWarehouse'])->name('misc.warehouse');
    Route::get('/misc/unit', [MiscController::class, 'getUnit'])->name('misc.unit');
    Route::get('/misc/type', [MiscController::class, 'getType'])->name('misc.type');
    Route::get('/misc/category', [MiscController::class, 'getCategory'])->name('misc.category');
    Route::get('/misc/specification', [MiscController::class, 'getSpecification'])->name('misc.specification');
    Route::get('/misc/variant', [MiscController::class, 'getVariant'])->name('misc.variant');
    Route::get('/misc/falocation', [MiscController::class, 'getFixedAssetLocation'])->name('misc.falocation');
    Route::get('/misc/facategory', [MiscController::class, 'getFixedAssetCategory'])->name('misc.facategory');
    Route::get('/misc/fa', [MiscController::class, 'getFixedAsset'])->name('misc.fa');

    Route::get('/misc/journal_type', [MiscController::class, 'getJournalType'])->name('misc.journal_type');
    Route::get('/misc/part', [MiscController::class, 'getPart'])->name('misc.part');
    Route::get('/misc/batch-no', [MiscController::class, 'getBatchNoSelect2'])->name('misc.batch_no');
    Route::get('/misc/stock-by-control-panel', [MiscController::class, 'getStockByControlPanel'])->name('misc.stock_by_control_panel');
    Route::get('/misc/part-with-scanner', [MiscController::class, 'getPart_withScanner'])->name('misc.part_with_scanner');
    Route::get('/misc/part-tanpa-serial-number', [MiscController::class, 'getPart_tanpaSerialNumber'])->name('misc.part_tanpa_serial_number');
    Route::get('/misc/division2', [MiscController::class, 'getDivisionSelect2'])->name('misc.division2');
    Route::get('/misc/warehouse2', [MiscController::class, 'getWarehouseSelect2'])->name('misc.warehouse2');
    Route::get('/misc/partunit2', [MiscController::class, 'getPartUnitSelect2'])->name('misc.partunit2');
    Route::get('/misc/conversion', [MiscController::class, 'getUnitConversion'])->name('misc.conversion');
    Route::get('/misc/part/content', [MiscController::class, 'getPartContent'])->name('misc.part_content');
    Route::get('/misc/user', [MiscController::class, 'getUser'])->name('misc.user');

    Route::get('misc/pu/{id}/units', [MiscController::class, 'getPartUsageUnits'])
        ->name('misc.part_usage_units');


    ///////////////////////////////
    //COMPANY PROFILE           //
    //////////////////////////////
    Route::middleware(['permission:admin|company_profile.view'])->group(function () {
        Route::get('/company', [CompanyController::class, 'index'])->name('company');
    });
    Route::middleware(['permission:admin|company_profile.edit'])->group(function () {
        Route::get('/company/edit', [CompanyController::class, 'edit'])->name('company.edit');
        Route::post('/company/update', [CompanyController::class, 'update'])->name('company.update');
    });



    ///////////////////////////////
    //IMPORT MASTER             //
    //////////////////////////////
    Route::middleware(['permission:admin|import.import'])->group(function () {
        Route::get('/import', [ImportController::class, 'index'])->name('import');
        Route::get('/import/instruction', [ImportController::class, 'instruction'])->name('import.instruction');
        Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
        Route::post('/import/check', [ImportController::class, 'checkProgress'])->name('import.check');
    });



    ///////////////////////////////
    //REPORT                    //
    //////////////////////////////

    Route::middleware(['permission:admin|report.view'])->group(function () {
        Route::get('/report', [ReportController::class, 'index'])->name('report');
        Route::get('/report/type', [ReportController::class, 'getTypes'])->name('report.type');
        Route::get('/material-cost', [ReportController::class, 'getMaterialCost'])->name('report.mc');
    });

    Route::get('/report/dynamic', [DynamicReportController::class, 'reportList'])->name('report.dynamic.list');
    Route::get('/report/v/{slug}', [DynamicReportController::class, 'index'])->name('report.dynamic.index');
    Route::match(['get', 'post'], '/report/data/{slug}', [DynamicReportController::class, 'data'])->name('report.dynamic.data');
    Route::get('/report/matrix/{slug}', [DynamicReportController::class, 'matrix'])->name('report.dynamic.matrix');
    Route::post('/report/matrix-data/{slug}', [DynamicReportController::class, 'matrixData'])->name('report.dynamic.matrix-data');
    Route::get('/report/export/{slug}', [DynamicReportController::class, 'export'])->name('report.dynamic.export');
    Route::get('/report/export-pdf/{slug}', [DynamicReportController::class, 'exportPdf'])->name('report.dynamic.export-pdf');
    Route::get('/report/preview/{slug}', [DynamicReportController::class, 'preview'])->name('report.dynamic.preview');

    Route::middleware(['permission:admin|activity_log.view'])->group(function () {
        Route::prefix('activity-log')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('activity_log');
            Route::get('/datatable', [ActivityLogController::class, 'datatable'])->name('activity_log.datatable');
        });
    });

    Route::get('/fix_fa', function () {
        $fas = \App\Models\MsFixedAsset::all();

        foreach ($fas as $fa) {
            if ($fa->OriginalRate == 0) {
                $fa->update([
                    'OriginalRate' => 1
                ]);
            }
        }
    });
});
