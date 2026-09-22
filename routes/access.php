<?php

use App\Http\Controllers\Admin\AccessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|access.view'])->group(function () {
        Route::get('/access', [AccessController::class, 'index'])->name('access');
        Route::get('/access/menus', [AccessController::class, 'getMenus'])->name('access.menu');
        Route::post('/access/store', [AccessController::class, 'store'])->name('access.store');
    });
});
