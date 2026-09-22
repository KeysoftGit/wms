<?php

use App\Http\Controllers\Sales\DeliveryOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::prefix('do')->group(function () {
        Route::middleware(['permission:admin|do.view'])->group(function () {
            Route::get('/', [DeliveryOrderController::class, 'index'])->name('do');
            Route::get('/datatable', [DeliveryOrderController::class, 'datatable'])->name('do.datatable');
            Route::get('/show/{id}', [DeliveryOrderController::class, 'show'])->name('do.show');
        });

        Route::middleware(['permission:admin|do.add'])->group(function () {
            Route::get('/add', [DeliveryOrderController::class, 'add'])->name('do.add');
            Route::post('/store', [DeliveryOrderController::class, 'store'])->name('do.store');
        });

        Route::middleware(['permission:admin|do.edit'])->group(function () {
            Route::get('/edit/{id}', [DeliveryOrderController::class, 'edit'])->name('do.edit');
            Route::post('/update', [DeliveryOrderController::class, 'update'])->name('do.update');
        });

        Route::middleware(['permission:admin|do.delete'])->group(function () {
            Route::delete('/delete/{id}', [DeliveryOrderController::class, 'destroy'])->name('do.delete');
        });

        Route::get('/so', [DeliveryOrderController::class, 'loadSO'])->name('do.so');
        Route::get('/so/address', [DeliveryOrderController::class, 'getAddress'])->name('do.so.address');
        Route::get('/so/detail', [DeliveryOrderController::class, 'getSODetail'])->name('do.so.detail');
    });
});
