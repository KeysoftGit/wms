<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Master\CustomerController;
use App\Http\Controllers\Api\Master\DivisionController;
use App\Http\Controllers\Api\Master\EmployeeController;
use App\Http\Controllers\Api\Master\InventoryTypeController;
use App\Http\Controllers\Api\Master\PartController;
use App\Http\Controllers\Api\Master\VehicleController;
use App\Http\Controllers\Api\Master\WarehouseController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\QRController;
use App\Http\Controllers\Api\StockMonitorController;
use App\Http\Controllers\Api\Transaction\DeliveryOrderExecuteController;
use App\Http\Controllers\Api\Transaction\ItemTransferController;
use App\Http\Controllers\Api\Transaction\ItemTransferRequestController;
use App\Http\Controllers\Api\Transaction\GoodsReceivingAsnController;
use App\Http\Controllers\Api\Transaction\LocationAdjustmentController;
use App\Http\Controllers\Api\Transaction\PartUsageController;
use App\Http\Controllers\Api\Transaction\PurchaseReturnExecuteController;
use App\Http\Controllers\Api\Transaction\StockAdjustmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('dynamic.connection')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.jwt')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('permission')->group(function () {
            Route::get('/all', [PermissionController::class, 'getAllPermission']);
            Route::post('/specific', [PermissionController::class, 'getSpecificPermission']);
        });

        Route::prefix('warehouse')->group(function () {
            Route::get('/', [WarehouseController::class, 'getWarehouse']);
            Route::get('/paginated', [WarehouseController::class, 'getWarehousePaginated']);
        });

        Route::prefix('part')->group(function () {
            Route::get('/', [PartController::class, 'getPart']);
            Route::get('/paginated', [PartController::class, 'getPartPaginated']);
        });

        Route::prefix('employee')->group(function () {
            Route::get('/', [EmployeeController::class, 'getEmployee']);
            Route::get('/paginated', [EmployeeController::class, 'getEmployeePaginated']);
        });

        Route::prefix('customer')->group(function () {
            Route::get('/', [CustomerController::class, 'getCustomer']);
            Route::get('/paginated', [CustomerController::class, 'getCustomerPaginated']);
        });

        Route::prefix('division')->group(function () {
            Route::get('/', [DivisionController::class, 'getDivision']);
            Route::get('/paginated', [DivisionController::class, 'getDivisionPaginated']);
        });

        Route::prefix('inventory-type')->group(function () {
            Route::get('/', [InventoryTypeController::class, 'getInventoryType']);
            Route::get('/paginated', [InventoryTypeController::class, 'getInventoryTypePaginated']);
        });

        Route::prefix('vehicle')->group(function () {
            Route::get('/', [VehicleController::class, 'getVehicle']);
            Route::get('/paginated', [VehicleController::class, 'getVehiclePaginated']);
        });

        Route::prefix('stock-monitor')->group(function () {
            Route::get('/movements', [StockMonitorController::class, 'getMovements']);
            Route::get('/detail-options', [StockMonitorController::class, 'getDetailOptions']);
        });

        Route::prefix('qr')->group(function () {
            Route::get('/{code}', [QRController::class, 'getQR']);
        });

        Route::prefix('transfer-request')->group(function () {
            Route::get('/', [ItemTransferRequestController::class, 'getTransaction']);
            Route::get('/details', [ItemTransferRequestController::class, 'getTransactionDetails']);
            Route::post('/', [ItemTransferRequestController::class, 'storeTransaction']);
            Route::put('/', [ItemTransferRequestController::class, 'updateTransaction']);
            Route::delete('/', [ItemTransferRequestController::class, 'deleteTransaction']);
        });

        Route::prefix('part-usage')->group(function () {
            Route::get('/', [PartUsageController::class, 'getTransaction']);
            Route::get('/details', [PartUsageController::class, 'getTransactionDetails']);
            Route::post('/', [PartUsageController::class, 'storeTransaction']);
            Route::put('/', [PartUsageController::class, 'updateTransaction']);
            Route::delete('/', [PartUsageController::class, 'deleteTransaction']);
        });

        Route::prefix('stock-adjustment')->group(function () {
            Route::get('/', [StockAdjustmentController::class, 'getTransaction']);
            Route::get('/details', [StockAdjustmentController::class, 'getTransactionDetails']);
            Route::get('/stock-detail', [StockAdjustmentController::class, 'getStockDetail']);
            Route::post('/', [StockAdjustmentController::class, 'storeTransaction']);
            Route::put('/', [StockAdjustmentController::class, 'updateTransaction']);
            Route::delete('/', [StockAdjustmentController::class, 'deleteTransaction']);
        });

        Route::prefix('direct-item-transfer')->group(function () {
            Route::get('/', [ItemTransferController::class, 'getTransaction']);
            Route::get('/details', [ItemTransferController::class, 'getTransactionDetails']);
            Route::post('/', [ItemTransferController::class, 'storeTransaction']);
            Route::put('/', [ItemTransferController::class, 'updateTransaction']);
            Route::delete('/', [ItemTransferController::class, 'deleteTransaction']);
        });

        Route::prefix('location-adjustment')->group(function () {
            Route::get('/', [LocationAdjustmentController::class, 'getTransaction']);
            Route::get('/details', [LocationAdjustmentController::class, 'getTransactionDetails']);
            Route::get('/stock-location-check', [LocationAdjustmentController::class, 'checkStockLocation']);
            Route::post('/', [LocationAdjustmentController::class, 'storeTransaction']);
            Route::put('/', [LocationAdjustmentController::class, 'updateTransaction']);
            Route::delete('/', [LocationAdjustmentController::class, 'deleteTransaction']);
        });

        Route::prefix('goods-receiving-asn')->group(function () {
            Route::get('/', [GoodsReceivingAsnController::class, 'getTransaction']);
            Route::get('/details', [GoodsReceivingAsnController::class, 'getTransactionDetails']);
            Route::get('/asn-options', [GoodsReceivingAsnController::class, 'getAsnOptions']);
            Route::get('/asn-details', [GoodsReceivingAsnController::class, 'getAsnDetails']);
            Route::post('/', [GoodsReceivingAsnController::class, 'storeTransaction']);
            Route::put('/', [GoodsReceivingAsnController::class, 'updateTransaction']);
            Route::delete('/', [GoodsReceivingAsnController::class, 'deleteTransaction']);
        });

        Route::prefix('delivery-order-execute')->group(function () {
            Route::get('/', [DeliveryOrderExecuteController::class, 'getTransaction']);
            Route::get('/details', [DeliveryOrderExecuteController::class, 'getTransactionDetails']);
            Route::post('/execute', [DeliveryOrderExecuteController::class, 'execute']);
            Route::put('/execute', [DeliveryOrderExecuteController::class, 'updateExecution']);
            Route::delete('/execute', [DeliveryOrderExecuteController::class, 'deleteExecution']);
        });

        Route::prefix('purchase-return-execute')->group(function () {
            Route::get('/', [PurchaseReturnExecuteController::class, 'getTransaction']);
            Route::get('/details', [PurchaseReturnExecuteController::class, 'getTransactionDetails']);
            Route::post('/execute', [PurchaseReturnExecuteController::class, 'execute']);
            Route::delete('/execute', [PurchaseReturnExecuteController::class, 'deleteExecution']);
        });
    });
});


// Route::prefix('keyone-pikka')->group(function () {
//     Route::prefix('purchase-order')->group(function () {
//         Route::get('/', [PikkaPurchaseOrderController::class, 'getPOByWarehouseTenant']);
//         Route::get('/dt', [PikkaPurchaseOrderController::class, 'getPODetails']);
//     });


//     Route::prefix('direct-purchase')->group(function () {
//         Route::get('/', [PikkaDirectPurchaseController::class, 'getDPByWarehouseTenant']);
//         Route::get('/dt', [PikkaDirectPurchaseController::class, 'getDPDetails']);
//     });

//     Route::prefix('goods-receiving')->group(function () {
//         Route::post('/po', [PikkaGoodsReceivingController::class, 'storePO']);
//         Route::put('/dp', [PikkaGoodsReceivingController::class, 'storeDP']);
//     });

//     Route::prefix('purchase-return')->group(function () {
//         Route::get('/', [PikkaPurchaseReturnController::class, 'getPRByWarehouseTenant']);
//         Route::get('/dt', [PikkaPurchaseReturnController::class, 'getPRDetails']);
//     });

//     Route::prefix('goods-issue')->group(function () {
//         Route::put('/', [PikkaGoodsIssueController::class, 'store']);
//     });
// });
