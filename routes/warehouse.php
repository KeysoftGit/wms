<?php

use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|warehouse.view'])->group(function (){
        Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse');
        Route::get('/warehouse/datatable', [WarehouseController::class, 'datatable'])->name('warehouse.datatable');
        Route::get('/warehouse/show/{id}', [WarehouseController::class, 'show'])->name('warehouse.show');
    });

    Route::middleware(['permission:admin|warehouse.add'])->group(function (){
        Route::get('/warehouse/add', [WarehouseController::class, 'add'])->name('warehouse.add');
        Route::post('/warehouse/store', [WarehouseController::class, 'store'])->name('warehouse.store');
    });

    Route::middleware(['permission:admin|warehouse.edit'])->group(function (){
        Route::get('/warehouse/edit/${id}', [WarehouseController::class, 'edit'])->name('warehouse.edit');
        Route::post('/warehouse/update', [WarehouseController::class, 'update'])->name('warehouse.update');
        Route::post('/warehouse/generate-qr/{id}', [WarehouseController::class, 'generateQr'])->name('warehouse.generate_qr');
    });

    Route::middleware(['permission:admin|warehouse.delete'])->group(function (){
        Route::delete('/warehouse/delete/${id}', [WarehouseController::class, 'destroy'])->name('warehouse.delete');
    });
});
