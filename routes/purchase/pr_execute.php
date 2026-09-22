<?php

use App\Http\Controllers\Purchase\PurchaseReturnExecuteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|pr_execute.view'])->group(function () {
        Route::get('/pr-execute', [PurchaseReturnExecuteController::class, 'index'])->name('pr_execute');
        Route::get('/pr-execute/datatable', [PurchaseReturnExecuteController::class, 'datatable'])->name('pr_execute.datatable');
        Route::get('/pr-execute/show/{id}', [PurchaseReturnExecuteController::class, 'show'])->name('pr_execute.show');
    });

    Route::middleware(['permission:admin|pr_execute.add'])->group(function () {
        Route::post('/pr-execute/execute/{id}', [PurchaseReturnExecuteController::class, 'execute'])->name('pr_execute.execute');
        Route::delete('/pr-execute/delete/{id}', [PurchaseReturnExecuteController::class, 'deleteExecution'])->name('pr_execute.delete');
    });
});
