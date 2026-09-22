<?php

use App\Http\Controllers\Stock\ItemTransferRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|transfer_request.view'])->group(function (){
        Route::get('/transfer-request', [ItemTransferRequestController::class, 'index'])->name('transfer_request');
        Route::get('/transfer-request/datatable', [ItemTransferRequestController::class, 'datatable'])->name('transfer_request.datatable');
        Route::get('/transfer-request/show/{id}', [ItemTransferRequestController::class, 'show'])->name('transfer_request.show');
    });

    Route::middleware(['permission:admin|transfer_request.add'])->group(function (){
        Route::get('/transfer-request/add', [ItemTransferRequestController::class, 'add'])->name('transfer_request.add');
        Route::post('/transfer-request/store', [ItemTransferRequestController::class, 'store'])->name('transfer_request.store');
    });

    Route::middleware(['permission:admin|transfer_request.edit'])->group(function () {
        Route::get('/transfer-request/edit/{id}', [ItemTransferRequestController::class, 'edit'])->name('transfer_request.edit');
        Route::post('/transfer-request/update', [ItemTransferRequestController::class, 'update'])->name('transfer_request.update');
    });

    Route::middleware(['permission:admin|transfer_request.delete'])->group(function (){
        Route::delete('/transfer-request/delete/{id}', [ItemTransferRequestController::class, 'destroy'])->name('transfer_request.delete');
    });
});
