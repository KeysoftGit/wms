<?php

use App\Http\Controllers\RevController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin'])->group(function (){
        Route::get('/rev', [RevController::class, 'index'])->name('rev');
        Route::get('/rev/detail', [RevController::class, 'getRev'])->name('rev.detail');
        Route::post('/rev/update', [RevController::class, 'update'])->name('rev.update');
    });

    Route::get('/rev/select', [RevController::class, 'selectData'])->name('rev.select');
});
