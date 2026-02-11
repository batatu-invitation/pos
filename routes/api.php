<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FinancialReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Financial Reporting API Routes
    Route::prefix('financial')->group(function () {
        Route::get('/unified-report', [FinancialReportController::class, 'unifiedReport'])
            ->name('api.financial.unified-report');
            
        Route::get('/sales-summary', [FinancialReportController::class, 'salesSummary'])
            ->name('api.financial.sales-summary');
            
        Route::get('/manual-transactions-summary', [FinancialReportController::class, 'manualTransactionsSummary'])
            ->name('api.financial.manual-transactions-summary');
            
        Route::post('/sync-sales', [FinancialReportController::class, 'syncSales'])
            ->name('api.financial.sync-sales');
    });
});