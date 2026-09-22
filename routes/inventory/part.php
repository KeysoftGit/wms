<?php

use App\Http\Controllers\Inventory\PartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|part.view'])->group(function () {
        Route::get('/inventory/part', [PartController::class, 'index'])->name('inventory.part');
        Route::get('/inventory/part/export', [PartController::class, 'export'])->name('inventory.part.export');
        Route::get('/inventory/part/datatable', [PartController::class, 'datatable'])->name('inventory.part.datatable');
        Route::get('/inventory/part/show/${id}', [PartController::class, 'show'])->name('inventory.part.show');
        Route::get('/inventory/part/qr', [PartController::class, 'qr'])->name('inventory.part.qr');
    });

    Route::middleware(['permission:admin|part.add'])->group(function () {
        Route::get('/inventory/part/add', [PartController::class, 'add'])->name('inventory.part.add');
        Route::post('/inventory/part/store', [PartController::class, 'store'])->name('inventory.part.store');
    });

    Route::middleware(['permission:admin|part.edit'])->group(function () {
        Route::get('/inventory/part/edit/${id}', [PartController::class, 'edit'])->name('inventory.part.edit');
        Route::post('/inventory/part/update', [PartController::class, 'update'])->name('inventory.part.update');

        Route::post('/inventory/part/update-non-batch', [PartController::class, 'updateNonBatch'])->name('inventory.part.update_non_batch');
    });

    Route::middleware(['permission:admin|part.delete'])->group(function () {
        Route::delete('/inventory/part/delete/${id}', [PartController::class, 'destroy'])->name('inventory.part.delete');
    });
});
