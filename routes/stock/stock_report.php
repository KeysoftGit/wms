<?php

use App\Http\Controllers\Stock\StockReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function () {
    Route::middleware(['permission:admin|stock_report.view'])->group(function () {
        Route::get('/stock_report', [StockReportController::class, 'index'])->name('stock_report');
        Route::get('/stock_report/datatable', [StockReportController::class, 'datatable'])->name('stock_report.datatable');
        Route::get('/stock_report/summary', [StockReportController::class, 'summary'])->name('stock_report.summary');
        Route::get('/stock_report/summary/datatable', [StockReportController::class, 'summaryDatatable'])->name('stock_report.summary.datatable');
        Route::get('/stock_report/export', [StockReportController::class, 'export'])->name('stock_report.export');
        Route::get('/stock_report/summary/export', [StockReportController::class, 'summaryExport'])->name('stock_report.summary.export');
    });

    Route::middleware(['permission:admin|stock_report.add'])->group(function () {
        Route::get('/stock_report/add', [StockReportController::class, 'add'])->name('stock_report.add');
        Route::post('/stock_report/store', [StockReportController::class, 'store'])->name('stock_report.store');
    });

    Route::middleware(['permission:admin|stock_report.edit'])->group(function () {
        Route::get('/stock_report/edit/${id}', [StockReportController::class, 'edit'])->name('stock_report.edit');
        Route::post('/stock_report/update/${id}', [StockReportController::class, 'update'])->name('stock_report.update');
    });

    Route::middleware(['permission:admin|stock_report.delete'])->group(function () {
        Route::delete('/stock_report/delete/${id}', [StockReportController::class, 'destroy'])->name('stock_report.delete');
    });
});
