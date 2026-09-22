<?php

use App\Http\Controllers\Stock\StockCardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|stock_monitor.view'])->group(function () {
        Route::get('/monitor', [StockCardController::class, 'index'])->name('monitor');
        Route::get('/monitor/datatable', [StockCardController::class, 'advancedDatatable'])->name('monitor.datatable');
        Route::get('/monitor/detail', [StockCardController::class, 'detail'])->name('monitor.detail');
    });
});
