<?php

use App\Http\Controllers\Sales\DeliveryOrderExecuteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|do_execute.view'])->group(function () {
        Route::get('/do-execute', [DeliveryOrderExecuteController::class, 'index'])->name('do_execute');
        Route::get('/do-execute/datatable', [DeliveryOrderExecuteController::class, 'datatable'])->name('do_execute.datatable');
        Route::get('/do-execute/show/{id}', [DeliveryOrderExecuteController::class, 'show'])->name('do_execute.show');
    });

    Route::middleware(['permission:admin|do_execute.add'])->group(function () {
        Route::post('/do-execute/execute/{id}', [DeliveryOrderExecuteController::class, 'execute'])->name('do_execute.execute');
        Route::delete('/do-execute/delete/{id}', [DeliveryOrderExecuteController::class, 'deleteExecution'])->name('do_execute.delete');
    });
});
