<?php

use App\Http\Controllers\Admin\ControlPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login', 'permission:admin'])->group(function () {
    Route::prefix('admin/control-panel')->group(function () {
        Route::get('/', [ControlPanelController::class, 'index'])->name('control_panel');
        Route::get('/datatable', [ControlPanelController::class, 'datatable'])->name('control_panel.datatable');

        Route::put('/toggle', [ControlPanelController::class, 'toggle'])->name('control_panel.toggle');
    });
});
