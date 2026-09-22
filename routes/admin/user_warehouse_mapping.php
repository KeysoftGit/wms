<?php

use App\Http\Controllers\Admin\UserWarehouseMappingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])
    ->prefix('user-warehouse-mapping')
    ->name('user_warehouse_mapping.')
    ->group(function () {
        Route::middleware('permission:admin|user_warehouse_mapping.view')->group(function () {
            Route::get('/', [UserWarehouseMappingController::class, 'index'])->name('index');
        });

        Route::middleware('permission:admin|user_warehouse_mapping.add')->group(function () {
            Route::get('/add', [UserWarehouseMappingController::class, 'create'])->name('create');
            Route::post('/', [UserWarehouseMappingController::class, 'store'])->name('store');
            Route::get('/{userId}/edit', [UserWarehouseMappingController::class, 'edit'])->name('edit');
            Route::put('/{userId}', [UserWarehouseMappingController::class, 'update'])->name('update');
        });
    });
