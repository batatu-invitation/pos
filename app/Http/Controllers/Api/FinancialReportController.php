<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinancialSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    protected FinancialSyncService $financialSyncService;

    public function __construct(FinancialSyncService $financialSyncService)
    {
        $this->financialSyncService = $financialSyncService;
    }

    /**
     * Get unified financial report
     */
    public function unifiedReport(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));

        $report = $this->financialSyncService->getUnifiedReport($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1,
            ]
        ]);
    }

    /**
     * Get sales-only financial summary
     */
    public function salesSummary(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));

        $summary = $this->financialSyncService->getSalesFinancialSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Get manual transactions summary
     */
    public function manualTransactionsSummary(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));

        $summary = $this->financialSyncService->getManualTransactionsSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Sync sales to transactions
     */
    public function syncSales(Request $request)
    {
        $dryRun = $request->boolean('dry_run', false);

        if ($dryRun) {
            return response()->json([
                'success' => true,
                'message' => 'Dry run mode - no changes will be made',
                'data' => [
                    'message' => 'Use dry_run=false to execute the sync'
                ]
            ]);
        }

        $result = $this->financialSyncService->syncAllExistingSales();

        return response()->json([
            'success' => true,
            'message' => 'Sales sync completed',
            'data' => $result
        ]);
    }
}