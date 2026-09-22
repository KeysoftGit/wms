<?php

use App\Http\Controllers\Purchase\GoodsReceivingController;
use App\Http\Controllers\Purchase\GoodsReceivingAsnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::prefix('gr')->group(function () {
        Route::middleware(['permission:admin|gr.view'])->group(function () {
            Route::get('/', [GoodsReceivingController::class, 'index'])->name('gr');
            Route::get('/datatable', [GoodsReceivingController::class, 'datatable'])->name('gr.datatable');
            Route::get('/show/{id}', [GoodsReceivingController::class, 'show'])->name('gr.show');
            Route::get('/asn/show/{id}', [GoodsReceivingAsnController::class, 'show'])->name('gr.asn.show');
        });

        Route::middleware(['permission:admin|gr.add'])->group(function () {
            Route::get('/add', [GoodsReceivingController::class, 'add'])->name('gr.add');
            Route::post('/store', [GoodsReceivingController::class, 'store'])->name('gr.store');
            Route::get('/asn/add', [GoodsReceivingAsnController::class, 'add'])->name('gr.asn.add');
            Route::post('/asn/store', [GoodsReceivingAsnController::class, 'store'])->name('gr.asn.store');
        });

        Route::middleware(['permission:admin|gr.edit'])->group(function () {
            Route::get('/edit/{id}', [GoodsReceivingController::class, 'edit'])->name('gr.edit');
            Route::post('/update', [GoodsReceivingController::class, 'update'])->name('gr.update');
            Route::get('/asn/edit/{id}', [GoodsReceivingAsnController::class, 'edit'])->name('gr.asn.edit');
            Route::post('/asn/update', [GoodsReceivingAsnController::class, 'update'])->name('gr.asn.update');
        });

        Route::middleware(['permission:admin|gr.delete'])->group(function () {
            Route::delete('/delete/{id}', [GoodsReceivingController::class, 'destroy'])->name('gr.delete');
            Route::delete('/asn/delete/{id}', [GoodsReceivingAsnController::class, 'destroy'])->name('gr.asn.delete');
        });

        Route::middleware(['permission:admin|gr.add|gr.edit'])->group(function () {
            Route::get('/po', [GoodsReceivingController::class, 'loadPO'])->name('gr.po');
            Route::get('/po/detail', [GoodsReceivingController::class, 'getPODetail'])->name('gr.po.detail');
            Route::get('/asn/options', [GoodsReceivingAsnController::class, 'loadASN'])->name('gr.asn.options');
            Route::get('/asn/detail', [GoodsReceivingAsnController::class, 'getASNDetail'])->name('gr.asn.detail');
        });
    });
});
