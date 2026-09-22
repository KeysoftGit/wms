<?php

use App\Http\Controllers\Accounting\JournalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|journal.view'])->group(function () {
        Route::get('/journals/transaction-data/{transactionNo}', [JournalController::class, 'dataByTransactionNo'])
            ->where('transactionNo', '.*')
            ->name('accounting.journal.by_transaction.data');
    });
});
