<?php

use App\Http\Controllers\Purchase\DirectPurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|dp.view'])->group(function (){
        Route::get('/dp', [DirectPurchaseController::class, 'index'])->name('dp');
        Route::get('/dp/datatable', [DirectPurchaseController::class, 'datatable'])->name('dp.datatable');
        Route::get('/dp/show/${id}', [DirectPurchaseController::class, 'show'])->name('dp.show');
    });

    Route::middleware(['permission:admin|dp.add'])->group(function (){
        Route::get('/dp/add', [DirectPurchaseController::class, 'add'])->name('dp.add');
        Route::post('/dp/store', [DirectPurchaseController::class, 'store'])->name('dp.store');
    });

    Route::middleware(['permission:admin|dp.edit'])->group(function () {
        Route::get('/dp/edit/${id}', [DirectPurchaseController::class, 'edit'])->name('dp.edit');
        Route::post('/dp/update', [DirectPurchaseController::class, 'update'])->name('dp.update');
    });

    Route::middleware(['permission:admin|dp.delete'])->group(function (){
        Route::delete('/dp/delete/${id}', [DirectPurchaseController::class, 'destroy'])->name('dp.delete');
    });
});
