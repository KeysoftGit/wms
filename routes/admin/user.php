<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'permission:admin', 'check_login'])->group(function (){
    Route::get('/admin/user', [UserController::class, 'index'])->name('user');
    Route::get('/admin/user/datatable', [UserController::class, 'datatable'])->name('user.datatable');
    Route::get('/admin/user/add', [UserController::class, 'add'])->name('user.add');
    Route::post('/admin/user/store', [UserController::class, 'store'])->name('user.store');
    Route::get('/admin/user/edit/${id}', [UserController::class, 'edit'])->name('user.edit');
    Route::post('/admin/user/update', [UserController::class, 'update'])->name('user.update');
    Route::post('/admin/user/disable/{id}', [UserController::class, 'disable'])->name('user.disable');
    Route::post('/admin/user/enable/{id}', [UserController::class, 'enable'])->name('user.enable');
});
