<?php

use App\Http\Controllers\Stock\ItemTransferExecuteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|transfer_execute.view'])->group(function (){
        Route::get('/transfer-execute', [ItemTransferExecuteController::class, 'index'])->name('transfer_execute');
        Route::get('/transfer-execute/datatable', [ItemTransferExecuteController::class, 'datatable'])->name('transfer_execute.datatable');
        Route::get('/transfer-execute/show/{id}', [ItemTransferExecuteController::class, 'show'])->name('transfer_execute.show');
    });

    Route::middleware(['permission:admin|transfer_execute.add'])->group(function (){
        Route::get('/transfer-execute/add', [ItemTransferExecuteController::class, 'add'])->name('transfer_execute.add');
        Route::post('/transfer-execute/store', [ItemTransferExecuteController::class, 'store'])->name('transfer_execute.store');
        Route::get('/transfer-execute/request-details', [ItemTransferExecuteController::class, 'getRequestDetails'])->name('transfer_execute.request_details');
    });

    Route::middleware(['permission:admin|transfer_execute.edit'])->group(function () {
        Route::get('/transfer-execute/edit/{id}', [ItemTransferExecuteController::class, 'edit'])->name('transfer_execute.edit');
        Route::post('/transfer-execute/update', [ItemTransferExecuteController::class, 'update'])->name('transfer_execute.update');
    });

    Route::middleware(['permission:admin|transfer_execute.delete'])->group(function (){
        Route::delete('/transfer-execute/delete/{id}', [ItemTransferExecuteController::class, 'destroy'])->name('transfer_execute.delete');
    });
});
