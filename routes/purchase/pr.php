<?php

use App\Http\Controllers\Purchase\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|pr.view'])->group(function (){
        Route::get('/pr', [PurchaseReturnController::class, 'index'])->name('pr');
        Route::get('/pr/datatable', [PurchaseReturnController::class, 'datatable'])->name('pr.datatable');
        Route::get('/pr/show/${id}', [PurchaseReturnController::class, 'show'])->name('pr.show');
    });

    Route::middleware(['permission:admin|pr.add'])->group(function (){
        Route::get('/pr/add', [PurchaseReturnController::class, 'add'])->name('pr.add');
        Route::post('/pr/store', [PurchaseReturnController::class, 'store'])->name('pr.store');
    });

    Route::middleware(['permission:admin|pr.edit'])->group(function () {
        Route::get('/pr/edit/${id}', [PurchaseReturnController::class, 'edit'])->name('pr.edit');
        Route::post('/pr/update', [PurchaseReturnController::class, 'update'])->name('pr.update');
    });

    Route::middleware(['permission:admin|pr.delete'])->group(function (){
        Route::delete('/pr/delete/${id}', [PurchaseReturnController::class, 'destroy'])->name('pr.delete');
    });

    Route::get('/pr/gr', [PurchaseReturnController::class, 'getGR'])->name('pr.gr');
    Route::get('/pr/gr/detail', [PurchaseReturnController::class, 'getGRDetail'])->name('pr.gr.detail');
    Route::get('/pr/dp', [PurchaseReturnController::class, 'getDP'])->name('pr.dp');
    Route::get('/pr/dp/detail', [PurchaseReturnController::class, 'getDPDetail'])->name('pr.dp.detail');

});
