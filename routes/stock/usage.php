<?php

use App\Http\Controllers\Stock\PartUsageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::prefix('usage')->group(function () {
        Route::middleware(['permission:admin|part_usage.view'])->group(function () {
            Route::get('/', [PartUsageController::class, 'index'])->name('usage');
            Route::get('/datatable', [PartUsageController::class, 'datatable'])->name('usage.datatable');
            Route::get('/show/{id}', [PartUsageController::class, 'show'])->name('usage.show');
        });

        Route::middleware(['permission:admin|part_usage.add'])->group(function () {
            Route::get('/add', [PartUsageController::class, 'add'])->name('usage.add');
            Route::post('/store', [PartUsageController::class, 'store'])->name('usage.store');
        });

        Route::middleware(['permission:admin|part_usage.edit'])->group(function () {
            Route::get('/edit/{id}', [PartUsageController::class, 'edit'])->name('usage.edit');
            Route::post('/update', [PartUsageController::class, 'update'])->name('usage.update');
        });

        Route::middleware(['permission:admin|part_usage.delete'])->group(function () {
            Route::delete('/delete/{id}', [PartUsageController::class, 'destroy'])->name('usage.delete');
        });
    });
});
