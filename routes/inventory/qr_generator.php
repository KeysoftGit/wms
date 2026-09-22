<?php

use App\Http\Controllers\Inventory\QRGeneratorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|qr_generator.view'])->group(function () {
        Route::get('/inventory/qr-generator', [QRGeneratorController::class, 'index'])->name('inventory.qr_generator');
        Route::get('/inventory/qr-generator/datatable', [QRGeneratorController::class, 'datatable'])->name('inventory.qr_generator.datatable');
        Route::get('/inventory/qr-generator/show/{id}', [QRGeneratorController::class, 'show'])->name('inventory.qr_generator.show');
    });

    Route::middleware(['permission:admin|qr_generator.add'])->group(function () {
        Route::get('/inventory/qr-generator/add', [QRGeneratorController::class, 'add'])->name('inventory.qr_generator.add');
        Route::post('/inventory/qr-generator/store', [QRGeneratorController::class, 'store'])->name('inventory.qr_generator.store');
    });

    Route::middleware(['permission:admin|qr_generator.edit'])->group(function () {
        Route::get('/inventory/qr-generator/edit/{id}', [QRGeneratorController::class, 'edit'])->name('inventory.qr_generator.edit');
        Route::post('/inventory/qr-generator/update', [QRGeneratorController::class, 'update'])->name('inventory.qr_generator.update');
    });

    Route::middleware(['permission:admin|qr_generator.delete'])->group(function () {
        Route::delete('/inventory/qr-generator/delete/{id}', [QRGeneratorController::class, 'destroy'])->name('inventory.qr_generator.delete');
    });
});
