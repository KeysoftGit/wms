<?php

use App\Http\Controllers\Stock\ItemTransferReceiveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|transfer_receive.view'])->group(function (){
        Route::get('/transfer-receive', [ItemTransferReceiveController::class, 'index'])->name('transfer_receive');
        Route::get('/transfer-receive/datatable', [ItemTransferReceiveController::class, 'datatable'])->name('transfer_receive.datatable');
        Route::get('/transfer-receive/show/{id}', [ItemTransferReceiveController::class, 'show'])->name('transfer_receive.show');
    });

    Route::middleware(['permission:admin|transfer_receive.add'])->group(function (){
        Route::get('/transfer-receive/add', [ItemTransferReceiveController::class, 'add'])->name('transfer_receive.add');
        Route::post('/transfer-receive/store', [ItemTransferReceiveController::class, 'store'])->name('transfer_receive.store');
        Route::get('/transfer-receive/execute-details', [ItemTransferReceiveController::class, 'getExecuteDetails'])->name('transfer_receive.execute_details');
    });

    Route::middleware(['permission:admin|transfer_receive.edit'])->group(function () {
        Route::get('/transfer-receive/edit/{id}', [ItemTransferReceiveController::class, 'edit'])->name('transfer_receive.edit');
        Route::post('/transfer-receive/update', [ItemTransferReceiveController::class, 'update'])->name('transfer_receive.update');
    });

    Route::middleware(['permission:admin|transfer_receive.delete'])->group(function (){
        Route::delete('/transfer-receive/delete/{id}', [ItemTransferReceiveController::class, 'destroy'])->name('transfer_receive.delete');
    });
});
