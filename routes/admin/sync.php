<?php

use App\Http\Controllers\Admin\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'permission:admin', 'check_login'])->group(function () {
    Route::prefix('sync')->group(function () {
        Route::get('/', [SyncController::class, 'index'])->name('sync');
        Route::post('/wms', [SyncController::class, 'syncWms'])->name('sync.wms');
    });
});
