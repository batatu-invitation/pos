<?php

use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashFlowExport;
use Barryvdh\DomPDF\Facade\Pdf;

new
#[Layout('components.layouts.app')]
#[Title('Cash Flow - Modern POS')]
class extends Component
{
    public $operatingActivities = [];
    public $investingActivities = [];
    public $financingActivities = [];
    public $startDate;
    public $endDate;
    
    // Summary Metrics
    public $totalOperating = 0;
    public $totalInvesting = 0;
    public $totalFinancing = 0;
    public $netCashFlow = 0;

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

        // --- Operating Activities ---
        $this->operatingActivities = [];
        $this->totalOperating = 0;

        // 1. Cash from Sales (Inflow)
        // We use total_amount as that represents the actual cash/payment obligation
        $cashFromSales = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_amount');

        if ($cashFromSales > 0) {
            $this->operatingActivities[] = [
                'name' => __('Cash Receipts from Customers'),
                'amount' => $cashFromSales,
                'type' => 'positive'
            ];
        }

        // 2. Other Income (Inflow)
        $otherIncome = Transaction::where('type', 'income')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($otherIncome as $income) {
            $this->operatingActivities[] = [
                'name' => $income->category ?? __('Other Income'),
                'amount' => $income->total,
                'type' => 'positive'
            ];
        }

        // 3. Operational Expenses (Outflow)
        $expenses = Transaction::where('type', 'expense')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($expenses as $expense) {
            $this->operatingActivities[] = [
                'name' => $expense->category ?? __('Other Expense'),
                'amount' => $expense->total,
                'type' => 'negative'
            ];
        }
        
        $this->totalOperating = $this->calculateTotal($this->operatingActivities);

        // --- Investing Activities ---
        // Currently no dedicated model for assets/investments, so we leave empty or placeholder
        $this->investingActivities = [];
        $this->totalInvesting = $this->calculateTotal($this->investingActivities);

        // --- Financing Activities ---
        // Currently no dedicated model for loans/equity, so we leave empty or placeholder
        $this->financingActivities = [];
        $this->totalFinancing = $this->calculateTotal($this->financingActivities);
        
        $this->netCashFlow = $this->totalOperating + $this->totalInvesting + $this->totalFinancing;
    }

    public function calculateTotal($activities)
    {
        $total = 0;
        foreach ($activities as $activity) {
            if ($activity['type'] === 'positive') {
                $total += $activity['amount'];
            } else {
                $total -= $activity['amount'];
            }
        }
        return $total;
    }

    public function exportExcel()
    {
        return Excel::download(new CashFlowExport(
            $this->operatingActivities,
            $this->investingActivities,
            $this->financingActivities,
            $this->startDate,
            $this->endDate
        ), 'cash-flow.xlsx');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.cash-flow', [
            'operatingActivities' => $this->operatingActivities,
            'investingActivities' => $this->investingActivities,
            'financingActivities' => $this->financingActivities,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'cash-flow.pdf');
    }
}; ?>

<div class="mx-auto p-6 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Cash Flow Statement') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Track cash inflows and outflows analysis.') }}</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 items-center">
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <input type="date" wire:model="startDate" class="border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-200 p-2 w-32">
                <span class="text-gray-400">-</span>
                <input type="date" wire:model="endDate" class="border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-200 p-2 w-32">
                <button wire:click="loadData" class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/20 text-sm font-medium">
                    <i class="fas fa-file-export mr-2"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 z-50 py-1" style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <i class="fas fa-file-excel mr-2 text-green-600 dark:text-green-400"></i> Export Excel
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <i class="fas fa-file-pdf mr-2 text-red-600 dark:text-red-400"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Net Cash Flow -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Net Cash Flow') }}</span>
                            <i class="fas fa-wallet text-emerald-100 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            {{ $netCashFlow < 0 ? '(' : '' }}Rp. {{ number_format(abs($netCashFlow), 0, ',', '.') }}{{ $netCashFlow < 0 ? ')' : '' }}
                        </div>
                        <div class="text-emerald-100 text-sm opacity-90">
                            {{ __('Total increase/decrease') }}
                        </div>
                    </div>
                </div>

                <!-- Operating Cash -->
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Operating') }}</span>
                            <i class="fas fa-briefcase text-blue-100 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            {{ $totalOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalOperating), 0, ',', '.') }}{{ $totalOperating < 0 ? ')' : '' }}
                        </div>
                        <div class="text-blue-100 text-sm opacity-90">
                            {{ __('From core activities') }}
                        </div>
                    </div>
                </div>

                <!-- Non-Operating (Investing + Financing) -->
                <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-3xl p-6 text-white shadow-lg shadow-purple-200 dark:shadow-none relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Non-Operating') }}</span>
                            <i class="fas fa-chart-line text-purple-100 text-xl"></i>
                        </div>
                        @php $nonOperating = $totalInvesting + $totalFinancing; @endphp
                        <div class="text-3xl font-bold mb-1">
                            {{ $nonOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($nonOperating), 0, ',', '.') }}{{ $nonOperating < 0 ? ')' : '' }}
                        </div>
                        <div class="text-purple-100 text-sm opacity-90">
                            {{ __('Investing & Financing') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Operating Activities (Left Column) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-6 bg-blue-500 rounded-full"></span>
                            {{ __('Operating Activities') }}
                        </h3>
                    </div>
                    <div class="p-6 flex-1">
                        <div class="space-y-3">
                            @foreach($operatingActivities as $activity)
                            <div class="flex justify-between items-center py-3 px-4 rounded-xl bg-gray-50/50 dark:bg-gray-700/30 hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600 group">
                                <span class="text-gray-700 dark:text-gray-300 font-medium group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $activity['name'] }}</span>
                                <span class="font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 px-6 py-4 border-t border-blue-100 dark:border-blue-800/30">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-blue-900 dark:text-blue-300">{{ __('Net Cash from Operating') }}</span>
                            <span class="font-bold text-xl {{ $totalOperating < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-700 dark:text-blue-400' }}">
                                {{ $totalOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalOperating), 0, ',', '.') }}{{ $totalOperating < 0 ? ')' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Investing & Financing (Right Column) -->
                <div class="space-y-6">
                    <!-- Investing -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-6 bg-purple-500 rounded-full"></span>
                                {{ __('Investing Activities') }}
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @forelse($investingActivities as $activity)
                                <div class="flex justify-between items-center py-2 px-3 rounded-lg hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $activity['name'] }}</span>
                                    <span class="text-sm font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @empty
                                <div class="text-gray-400 dark:text-gray-500 text-sm italic text-center py-2">{{ __('No investing activities') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 px-6 py-3 border-t border-purple-100 dark:border-purple-800/30">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sm text-purple-900 dark:text-purple-300">{{ __('Net Investing') }}</span>
                                <span class="font-bold text-lg {{ $totalInvesting < 0 ? 'text-red-600 dark:text-red-400' : 'text-purple-700 dark:text-purple-400' }}">
                                    {{ $totalInvesting < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalInvesting), 0, ',', '.') }}{{ $totalInvesting < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financing -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-6 bg-orange-500 rounded-full"></span>
                                {{ __('Financing Activities') }}
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @forelse($financingActivities as $activity)
                                <div class="flex justify-between items-center py-2 px-3 rounded-lg hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $activity['name'] }}</span>
                                    <span class="text-sm font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @empty
                                <div class="text-gray-400 dark:text-gray-500 text-sm italic text-center py-2">{{ __('No financing activities') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 px-6 py-3 border-t border-orange-100 dark:border-orange-800/30">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sm text-orange-900 dark:text-orange-300">{{ __('Net Financing') }}</span>
                                <span class="font-bold text-lg {{ $totalFinancing < 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-700 dark:text-orange-400' }}">
                                    {{ $totalFinancing < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalFinancing), 0, ',', '.') }}{{ $totalFinancing < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>
