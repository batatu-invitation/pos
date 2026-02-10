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

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-300">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen">

        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 shadow-xl border-b border-gray-100 dark:border-gray-700 z-10 sticky top-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-400 focus:outline-none md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">{{ __('Cash Flow Statement') }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Track cash inflows and outflows') }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="relative" x-data="{ notificationOpen: false }">
                    <button @click="notificationOpen = !notificationOpen" class="relative p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full border border-white dark:border-gray-800"></span>
                    </button>
                </div>
                <a href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/30">
                    <i class="fas fa-cash-register mr-2"></i>
                    {{ __('Open POS') }}
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6 space-y-6">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Start Date') }}</label>
                            <input type="date" wire:model="startDate" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('End Date') }}</label>
                            <input type="date" wire:model="endDate" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative self-end">
                            <button wire:click="loadData" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/30">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    </div>
                    
                    <div x-data="{ open: false }" class="relative w-full md:w-auto">
                        <button @click="open = !open" @click.away="open = false" class="w-full md:w-auto px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors flex items-center justify-center shadow-sm">
                            <i class="fas fa-file-export mr-2"></i> {{ __('Export Report') }}
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl z-50 border border-gray-100 dark:border-gray-700 py-2" style="display: none;">
                            <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i> Export Excel
                            </button>
                            <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-file-pdf text-red-600 mr-2"></i> Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash Flow Statement -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-money-bill-wave"></i>
                        </span>
                        {{ __('Cash Flow Statement') }}
                    </h2>
                    <span class="px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    </span>
                </div>

                <div class="p-6 space-y-8">
                    <!-- Operating Activities -->
                    <div>
                        <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            {{ __('Operating Activities') }}
                        </h3>
                        <div class="space-y-1">
                            @foreach($operatingActivities as $activity)
                            <div class="flex justify-between items-center py-3 px-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $activity['name'] }}</span>
                                <span class="font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                </span>
                            </div>
                            @endforeach

                            <div class="flex justify-between items-center py-4 px-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl mt-4 border border-blue-100 dark:border-blue-800/30">
                                <span class="font-bold text-blue-900 dark:text-blue-300">{{ __('Net Cash from Operating Activities') }}</span>
                                @php $totalOperating = $this->calculateTotal($operatingActivities); @endphp
                                <span class="font-bold text-lg {{ $totalOperating < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-700 dark:text-blue-400' }}">
                                    {{ $totalOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalOperating), 0, ',', '.') }}{{ $totalOperating < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Investing Activities -->
                    <div>
                        <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            {{ __('Investing Activities') }}
                        </h3>
                        <div class="space-y-1">
                            @if(count($investingActivities) > 0)
                                @foreach($investingActivities as $activity)
                                <div class="flex justify-between items-center py-3 px-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $activity['name'] }}</span>
                                    <span class="font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 dark:text-gray-500 text-sm italic px-4 py-2">{{ __('No investing activities recorded.') }}</div>
                            @endif

                            <div class="flex justify-between items-center py-4 px-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl mt-4 border border-purple-100 dark:border-purple-800/30">
                                <span class="font-bold text-purple-900 dark:text-purple-300">{{ __('Net Cash from Investing Activities') }}</span>
                                @php $totalInvesting = $this->calculateTotal($investingActivities); @endphp
                                <span class="font-bold text-lg {{ $totalInvesting < 0 ? 'text-red-600 dark:text-red-400' : 'text-purple-700 dark:text-purple-400' }}">
                                    {{ $totalInvesting < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalInvesting), 0, ',', '.') }}{{ $totalInvesting < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financing Activities -->
                    <div>
                        <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                            {{ __('Financing Activities') }}
                        </h3>
                        <div class="space-y-1">
                            @if(count($financingActivities) > 0)
                                @foreach($financingActivities as $activity)
                                <div class="flex justify-between items-center py-3 px-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $activity['name'] }}</span>
                                    <span class="font-bold {{ $activity['type'] === 'negative' ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 dark:text-gray-500 text-sm italic px-4 py-2">{{ __('No financing activities recorded.') }}</div>
                            @endif

                            <div class="flex justify-between items-center py-4 px-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl mt-4 border border-orange-100 dark:border-orange-800/30">
                                <span class="font-bold text-orange-900 dark:text-orange-300">{{ __('Net Cash from Financing Activities') }}</span>
                                @php $totalFinancing = $this->calculateTotal($financingActivities); @endphp
                                <span class="font-bold text-lg {{ $totalFinancing < 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-700 dark:text-orange-400' }}">
                                    {{ $totalFinancing < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalFinancing), 0, ',', '.') }}{{ $totalFinancing < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Cash Flow -->
                    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-center py-5 px-6 bg-gray-900 dark:bg-black text-white rounded-2xl shadow-xl transform transition-transform hover:scale-[1.01]">
                            <span class="text-lg font-bold flex items-center gap-2">
                                <i class="fas fa-wallet text-indigo-400"></i>
                                {{ __('Net Increase (Decrease) in Cash') }}
                            </span>
                            @php $netCash = $totalOperating + $totalInvesting + $totalFinancing; @endphp
                            <span class="text-2xl font-bold {{ $netCash < 0 ? 'text-red-400' : 'text-emerald-400' }}">
                                {{ $netCash < 0 ? '(' : '' }}Rp. {{ number_format(abs($netCash), 0, ',', '.') }}{{ $netCash < 0 ? ')' : '' }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>
