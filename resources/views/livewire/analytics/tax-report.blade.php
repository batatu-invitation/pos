<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TaxReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

new
#[Layout('components.layouts.app')]
#[Title('Tax Report')]
class extends Component
{
    public $taxDetails = [];
    public $startDate;
    public $endDate;
    public $nonTaxableSales = 0;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $user = auth()->user();
        $userId = $user->created_by ? $user->created_by : $user->id;

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $this->taxDetails = [];

        // 1. Get Sales with Tax (Grouped by Tax Type)
        // Using Join to get Tax Name and Rate
        $taxedSales = Sale::query()
            ->join('taxes', 'sales.tax_id', '=', 'taxes.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->select(
                'taxes.name as tax_name',
                'taxes.rate as tax_rate',
                DB::raw('SUM(sales.subtotal) as taxable_amount'),
                DB::raw('SUM(sales.tax) as tax_amount')
            )
            ->groupBy('taxes.id', 'taxes.name', 'taxes.rate')
            ->get();

        foreach ($taxedSales as $sale) {
            $this->taxDetails[] = [
                'name' => $sale->tax_name,
                'rate' => $sale->tax_rate,
                'taxable' => $sale->taxable_amount,
                'tax' => $sale->tax_amount
            ];
        }

        // 2. Handle Sales with Tax but No Tax ID (Legacy or Manual)
        $manualTaxSales = Sale::whereNull('tax_id')
            ->where('tax', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(subtotal) as taxable_amount'),
                DB::raw('SUM(tax) as tax_amount')
            )
            ->first();

        if ($manualTaxSales && $manualTaxSales->tax_amount > 0) {
            $this->taxDetails[] = [
                'name' => __('Other / Manual Tax'),
                'rate' => 0, // Rate unknown
                'taxable' => $manualTaxSales->taxable_amount,
                'tax' => $manualTaxSales->tax_amount
            ];
        }

        // 3. Non-Taxable Sales
        $this->nonTaxableSales = Sale::where('tax', 0)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('subtotal');
    }

    public function getTotalTaxable()
    {
        return array_sum(array_column($this->taxDetails, 'taxable'));
    }

    public function getTotalTax()
    {
        return array_sum(array_column($this->taxDetails, 'tax'));
    }

    public function exportExcel()
    {
        return Excel::download(new TaxReportExport($this->taxDetails), 'tax-report.xlsx');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.tax-report', [
            'details' => $this->taxDetails,
            'start' => $this->startDate,
            'end' => $this->endDate
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'tax-report.pdf');
    }
}; ?>

<div class="p-6 space-y-6 transition-colors duration-300">
    <div class="mx-auto space-y-6">
        
        <!-- Controls Section -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Tax Report') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Manage and track tax collections and reports') }}
                </p>
            </div>
            <div class="flex gap-3">
                 <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm">
                        <i class="fas fa-download mr-2"></i> {{ __('Export') }}
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 z-50 py-1" style="display: none;">
                        <button wire:click="exportExcel" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <i class="fas fa-file-excel mr-2 text-green-600 dark:text-green-400"></i> Excel
                        </button>
                        <button wire:click="exportPdf" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <i class="fas fa-file-pdf mr-2 text-red-600 dark:text-red-400"></i> PDF
                        </button>
                    </div>
                </div>
                <a href="{{ route('pos.visual') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/20">
                    <i class="fas fa-cash-register mr-2"></i>
                    {{ __('Open POS') }}
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Start Date') }}</label>
                    <input type="date" wire:model="startDate" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('End Date') }}</label>
                    <input type="date" wire:model="endDate" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="w-full md:w-auto">
                    <button wire:click="loadData" class="w-full md:w-auto px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                        {{ __('Apply Filter') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Sales Tax -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 group hover:border-indigo-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Sales Tax') }}</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            Rp. {{ number_format($this->getTotalTax(), 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-percent text-xl"></i>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: 70%"></div>
                </div>
            </div>

            <!-- Taxable Sales -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 group hover:border-blue-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Taxable Sales') }}</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            Rp. {{ number_format($this->getTotalTaxable(), 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-2xl text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-file-invoice-dollar text-xl"></i>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: 50%"></div>
                </div>
            </div>

            <!-- Non-Taxable Sales -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 group hover:border-green-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Non-Taxable Sales') }}</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            Rp. {{ number_format($nonTaxableSales, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-2xl text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-hand-holding-usd text-xl"></i>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                    <div class="bg-green-600 h-1.5 rounded-full" style="width: 30%"></div>
                </div>
            </div>
        </div>

        <!-- Tax Details Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Tax Details') }}</h3>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Tax Name') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Rate') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Taxable Amount') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Tax Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($taxDetails as $tax)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">{{ $tax['name'] }}</td>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $tax['rate'] > 0 ? $tax['rate'] . '%' : '-' }}</td>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                    Rp. {{ number_format($tax['taxable'], 0, ',', '.') }}
                                </td>
                                <td class="p-4 text-sm font-bold text-indigo-600 dark:text-indigo-400 text-right">
                                    Rp. {{ number_format($tax['tax'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-file-invoice text-gray-400 text-2xl"></i>
                                        </div>
                                        <p class="text-base font-medium">{{ __('No tax data found') }}</p>
                                        <p class="text-sm mt-1">{{ __('Try adjusting the date range') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50/50 dark:bg-gray-700/50 font-bold text-gray-900 dark:text-white border-t border-gray-100 dark:border-gray-700">
                        <tr>
                            <td class="p-4" colspan="2">{{ __('Total') }}</td>
                            <td class="p-4 text-right">Rp. {{ number_format($this->getTotalTaxable(), 0, ',', '.') }}</td>
                            <td class="p-4 text-right">Rp. {{ number_format($this->getTotalTax(), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
