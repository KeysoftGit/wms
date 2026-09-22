<?php

use App\Http\Controllers\Stock\ItemTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|transfer.view'])->group(function (){
        Route::get('/transfer', [ItemTransferController::class, 'index'])->name('transfer');
        Route::get('/transfer/datatable', [ItemTransferController::class, 'datatable'])->name('transfer.datatable');
        Route::get('/transfer/show/${id}', [ItemTransferController::class, 'show'])->name('transfer.show');
    });

    Route::middleware(['permission:admin|transfer.add'])->group(function (){
        Route::get('/transfer/add', [ItemTransferController::class, 'add'])->name('transfer.add');
        Route::post('/transfer/store', [ItemTransferController::class, 'store'])->name('transfer.store');
    });

    Route::middleware(['permission:admin|transfer.edit'])->group(function () {
        Route::get('/transfer/edit/${id}', [ItemTransferController::class, 'edit'])->name('transfer.edit');
        Route::post('/transfer/update', [ItemTransferController::class, 'update'])->name('transfer.update');
    });

    Route::middleware(['permission:admin|transfer.add|transfer.edit'])->group(function () {
        Route::get('/transfer/stock', [ItemTransferController::class, 'getStock'])->name('transfer.stock');
        Route::get('/transfer/stock-options', [ItemTransferController::class, 'getStockOptions'])->name('transfer.stock_options');
    });

    Route::middleware(['permission:admin|transfer.delete'])->group(function (){
        Route::delete('/transfer/delete/${id}', [ItemTransferController::class, 'destroy'])->name('transfer.delete');
    });
});
