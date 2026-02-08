<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Balance Sheet')]
class extends Component
{
    public $date;
    public $assets = [];
    public $liabilities = [];
    public $equity = [];
    public $totalAssets = 0;
    public $totalLiabilities = 0;
    public $totalEquity = 0;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $asOfDate = Carbon::parse($this->date)->endOfDay();

        // --- ASSETS ---
        $this->assets = [];

        // 1. Cash & Equivalents
        // Cash from Sales (Inflow)
        $cashInSales = Sale::where('status', 'completed')
            ->where('created_at', '<=', $asOfDate)
            ->sum('total_amount');

        // Cash from Income Transactions (Inflow)
        $cashInTrans = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        // Cash Out (Expense Transactions)
        $cashOutTrans = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        // Net Cash
        $cashOnHand = ($cashInSales + $cashInTrans) - $cashOutTrans;

        $this->assets['Current Assets'][] = [
            'name' => __('Cash & Bank'),
            'amount' => $cashOnHand
        ];

        // 2. Accounts Receivable (Pending Sales)
        $receivables = Sale::where('status', 'pending')
            ->where('created_at', '<=', $asOfDate)
            ->sum('total_amount');

        if ($receivables > 0) {
            $this->assets['Current Assets'][] = [
                'name' => __('Accounts Receivable'),
                'amount' => $receivables
            ];
        }

        // 3. Inventory Value
        // Note: Using current snapshot as proxy
        $inventoryValue = Product::where('status', 'active')->sum(DB::raw('cost * stock'));

        if ($inventoryValue > 0) {
            $this->assets['Current Assets'][] = [
                'name' => __('Inventory Asset'),
                'amount' => $inventoryValue
            ];
        }

        // Calculate Total Assets
        $this->totalAssets = 0;
        foreach ($this->assets as $group) {
            foreach ($group as $item) {
                $this->totalAssets += $item['amount'];
            }
        }

        // --- LIABILITIES ---
        $this->liabilities = [];

        // 1. Accounts Payable (Pending Expenses)
        $payables = Transaction::where('type', 'expense')
            ->where('status', 'pending')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        if ($payables > 0) {
            $this->liabilities['Current Liabilities'][] = [
                'name' => __('Accounts Payable'),
                'amount' => $payables
            ];
        }

        // Calculate Total Liabilities
        $this->totalLiabilities = 0;
        foreach ($this->liabilities as $group) {
            foreach ($group as $item) {
                $this->totalLiabilities += $item['amount'];
            }
        }

        // --- EQUITY ---
        $this->equity = [];

        // Retained Earnings = Assets - Liabilities
        $retainedEarnings = $this->totalAssets - $this->totalLiabilities;

        $this->equity['Equity'][] = [
            'name' => __('Retained Earnings'),
            'amount' => $retainedEarnings
        ];

        $this->totalEquity = $retainedEarnings;
    }

    public function exportExcel()
    {
        return Excel::download(new BalanceSheetExport(
            $this->assets,
            $this->liabilities,
            $this->equity,
            $this->totalAssets,
            $this->totalLiabilities,
            $this->totalEquity,
            $this->date
        ), 'balance-sheet.xlsx');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.balance-sheet', [
            'assets' => $this->assets,
            'liabilities' => $this->liabilities,
            'equity' => $this->equity,
            'totalAssets' => $this->totalAssets,
            'totalLiabilities' => $this->totalLiabilities,
            'totalEquity' => $this->totalEquity,
            'date' => $this->date
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'balance-sheet.pdf');
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
                    <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">{{ __('Balance Sheet') }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Financial position snapshot') }}</p>
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
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('As of Date') }}</label>
                            <input type="date" wire:model="date" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Assets Side -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <i class="fas fa-coins"></i>
                                </span>
                                {{ __('Assets') }}
                            </h3>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex-1 space-y-6">
                                @forelse($assets as $category => $items)
                                    <div class="last:mb-0">
                                        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ $category }}
                                        </h4>
                                        <div class="space-y-2">
                                            @foreach($items as $item)
                                                <div class="flex justify-between items-center p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $item['name'] }}</span>
                                                    <span class="font-bold text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-box-open text-gray-400 text-2xl"></i>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No assets recorded.') }}</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-center py-4 px-6 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                                    <span class="font-bold text-emerald-900 dark:text-emerald-300">{{ __('Total Assets') }}</span>
                                    <span class="font-bold text-xl text-emerald-700 dark:text-emerald-400">Rp. {{ number_format($totalAssets, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liabilities & Equity Side -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </span>
                                {{ __('Liabilities & Equity') }}
                            </h3>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex-1 space-y-8">
                                <!-- Liabilities -->
                                <div>
                                    @if(!empty($liabilities))
                                        @foreach($liabilities as $category => $items)
                                            <div class="mb-2">
                                                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    {{ $category }}
                                                </h4>
                                                <div class="space-y-2">
                                                    @foreach($items as $item)
                                                        <div class="flex justify-between items-center p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                                            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $item['name'] }}</span>
                                                            <span class="font-bold text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="flex justify-between items-center mt-3 px-3 py-2 bg-rose-50 dark:bg-rose-900/10 rounded-lg text-sm font-medium text-rose-800 dark:text-rose-300">
                                                    <span>{{ __('Total Liabilities') }}</span>
                                                    <span>Rp. {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                         <div class="mb-2">
                                            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                {{ __('Liabilities') }}
                                            </h4>
                                            <p class="text-sm text-gray-400 dark:text-gray-500 italic p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl">{{ __('No liabilities recorded.') }}</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Equity -->
                                <div>
                                     @foreach($equity as $category => $items)
                                        <div class="mb-2">
                                            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                {{ $category }}
                                            </h4>
                                            <div class="space-y-2">
                                                @foreach($items as $item)
                                                    <div class="flex justify-between items-center p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $item['name'] }}</span>
                                                        <span class="font-bold text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-center py-4 px-6 bg-gray-900 dark:bg-black text-white rounded-2xl shadow-lg transform transition-transform hover:scale-[1.01]">
                                    <span class="font-bold flex items-center gap-2">
                                        <i class="fas fa-balance-scale"></i>
                                        {{ __('Total Liabilities & Equity') }}
                                    </span>
                                    <span class="font-bold text-xl text-indigo-400">Rp. {{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
