<?php

use App\Http\Controllers\Stock\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::prefix('opname')->group(function () {
        Route::middleware(['permission:admin|opname.view'])->group(function () {
            Route::get('/', [StockOpnameController::class, 'index'])->name('opname');
            Route::get('/datatable', [StockOpnameController::class, 'datatable'])->name('opname.datatable');
            Route::get('/show/{id}', [StockOpnameController::class, 'show'])->name('opname.show');
        });

        Route::middleware(['permission:admin|opname.add'])->group(function () {
            Route::get('/add', [StockOpnameController::class, 'add'])->name('opname.add');
            Route::post('/store', [StockOpnameController::class, 'store'])->name('opname.store');
        });

        Route::middleware(['permission:admin|opname.edit'])->group(function () {
            Route::get('/edit/{id}', [StockOpnameController::class, 'edit'])->name('opname.edit');
            Route::post('/update', [StockOpnameController::class, 'update'])->name('opname.update');
            Route::post('/import', [StockOpnameController::class, 'checkExcel'])->name('opname.import');
            Route::get('/template/{id}', [StockOpnameController::class, 'downloadTemplate'])->name('opname.template');
        });

        Route::middleware(['permission:admin|opname.delete'])->group(function () {
            Route::delete('/delete/{id}', [StockOpnameController::class, 'destroy'])->name('opname.delete');
        });

        Route::get('/stock', [StockOpnameController::class, 'getStock'])->name('opname.stock');
    });
});
