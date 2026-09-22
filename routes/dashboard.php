<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|dashboard.wms'])->group(function (){
        Route::get('/dashboard/warehouse/data', [DashboardController::class, 'getWarehouseData'])->name('dashboard.warehouse.data');
    });

    Route::middleware(['permission:admin|dashboard.main'])->group(function (){
        Route::get('/dashboard/main/top', [DashboardController::class, 'getMainTop'])->name('dashboard.main.top');
        Route::get('/dashboard/main/revenue', [DashboardController::class, 'getRevenue'])->name('dashboard.main.revenue');
        Route::get('/dashboard/main/operational', [DashboardController::class, 'getOperational'])->name('dashboard.main.operational');
        Route::get('/dashboard/main/earning', [DashboardController::class, 'getEarning'])->name('dashboard.main.earning');
        Route::get('/dashboard/main/statement', [DashboardController::class, 'getIncomeStatement'])->name('dashboard.main.statement');
    });

    Route::middleware(['permission:admin|dashboard.sales'])->group(function (){
        Route::get('/dashboard/sales/top', [DashboardController::class, 'getSalesTop'])->name('dashboard.sales.top');
        Route::get('/dashboard/sales/revenue', [DashboardController::class, 'getSalesRevenue'])->name('dashboard.sales.revenue');
        Route::get('/dashboard/sales/upcross', [DashboardController::class, 'getUpCross'])->name('dashboard.sales.upcross');
        Route::get('/dashboard/sales/subdistrict', [DashboardController::class, 'getSubdistrict'])->name('dashboard.sales.subdistrict');
        Route::get('/dashboard/sales/category', [DashboardController::class, 'getCategory'])->name('dashboard.sales.category');
        Route::get('/dashboard/sales/division', [DashboardController::class, 'getDivision'])->name('dashboard.sales.division');
    });

    Route::middleware(['permission:admin|dashboard.purchase'])->group(function (){
        Route::get('/dashboard/purchase/supplier', [DashboardController::class, 'getSupplier'])->name('dashboard.purchase.supplier');
        Route::get('/dashboard/purchase/active', [DashboardController::class, 'getActiveSupplier'])->name('dashboard.purchase.active');
        Route::get('/dashboard/purchase/nonactive', [DashboardController::class, 'getNonActiveSupplier'])->name('dashboard.purchase.nonactive');
        Route::get('/dashboard/purchase/partner', [DashboardController::class, 'getPartners'])->name('dashboard.purchase.partner');
        Route::get('/dashboard/purchase/spending', [DashboardController::class, 'getSpending'])->name('dashboard.purchase.spending');
        Route::get('/dashboard/purchase/procurement', [DashboardController::class, 'getProcurement'])->name('dashboard.purchase.procurement');
        Route::get('/dashboard/purchase/avg_days', [DashboardController::class, 'getAvgDays'])->name('dashboard.purchase.avg_days');
        Route::get('/dashboard/purchase/avg_supplier', [DashboardController::class, 'getAvgSupplier'])->name('dashboard.purchase.avg_supplier');
    });
});
