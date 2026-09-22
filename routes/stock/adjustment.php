<?php

use App\Http\Controllers\Stock\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::prefix('adjust')->group(function () {
        Route::middleware(['permission:admin|stock_adj.view'])->group(function () {
            Route::get('/', [StockAdjustmentController::class, 'index'])->name('adjust');
            Route::get('/datatable', [StockAdjustmentController::class, 'datatable'])->name('adjust.datatable');
            Route::get('/show/{id}', [StockAdjustmentController::class, 'show'])->name('adjust.show');
        });

        Route::middleware(['permission:admin|stock_adj.add'])->group(function () {
            Route::get('/add', [StockAdjustmentController::class, 'add'])->name('adjust.add');
            Route::post('/store', [StockAdjustmentController::class, 'store'])->name('adjust.store');
        });

        Route::middleware(['permission:admin|stock_adj.edit'])->group(function () {
            Route::get('/edit/{id}', [StockAdjustmentController::class, 'edit'])->name('adjust.edit');
            Route::post('/update', [StockAdjustmentController::class, 'update'])->name('adjust.update');
        });

        Route::middleware(['permission:admin|stock_adj.delete'])->group(function () {
            Route::delete('/delete/{id}', [StockAdjustmentController::class, 'destroy'])->name('adjust.delete');
        });

        Route::get('/opname', [StockAdjustmentController::class, 'getOpname'])->name('adjust.opname');
        Route::get('/opname/detail', [StockAdjustmentController::class, 'getOpnameDetail'])->name('adjust.opname.detail');
        Route::get('/stock/detail', [StockAdjustmentController::class, 'getStockDetail'])->name('adjust.stock.detail');
    });
});
