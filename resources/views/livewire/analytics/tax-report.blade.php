<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
}; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none mr-4 md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-semibold text-gray-800">{{ __('Tax Report') }}</h1>
            </div>

            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                <a href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-cash-register mr-2"></i>
                    {{ __('Open POS') }}
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Start Date') }}</label>
                            <input type="date" wire:model="startDate" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('End Date') }}</label>
                            <input type="date" wire:model="endDate" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative self-end">
                            <button wire:click="loadData" class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-download mr-2"></i> {{ __('Export') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tax Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ __('Total Sales Tax') }}</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">Rp. {{ number_format($this->getTotalTax(), 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ __('Taxable Sales') }}</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">Rp. {{ number_format($this->getTotalTaxable(), 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ __('Non-Taxable Sales') }}</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">Rp. {{ number_format($nonTaxableSales, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Tax Detail Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">{{ __('Tax Details') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">{{ __('Tax Name') }}</th>
                                <th class="px-6 py-4 font-semibold">{{ __('Rate') }}</th>
                                <th class="px-6 py-4 font-semibold text-right">{{ __('Taxable Amount') }}</th>
                                <th class="px-6 py-4 font-semibold text-right">{{ __('Tax Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($taxDetails as $tax)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $tax['name'] }}</td>
                                <td class="px-6 py-4">{{ $tax['rate'] > 0 ? $tax['rate'] . '%' : '-' }}</td>
                                <td class="px-6 py-4 text-right">Rp. {{ number_format($tax['taxable'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-medium text-indigo-600">Rp. {{ number_format($tax['tax'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">
                                    {{ __('No tax data found for the selected period.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-900">
                            <tr>
                                <td class="px-6 py-4" colspan="2">{{ __('Total') }}</td>
                                <td class="px-6 py-4 text-right">Rp. {{ number_format($this->getTotalTaxable(), 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp. {{ number_format($this->getTotalTax(), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>
