<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Services\FinancialSyncService;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $startDate;
    public $endDate;
    public $search = '';
    
    // Financial summary data
    public $financialSummary = [];
    public $showFinancialSummary = true;

    /**
     * Initialize financial summary with default structure
     */
    protected function initializeFinancialSummary()
    {
        $this->financialSummary = [
            'totals' => [
                'sales_income' => 0,
                'manual_income' => 0,
                'total_income' => 0,
                'expenses' => 0,
                'net_profit' => 0,
            ],
            'breakdown' => [
                'auto_generated_count' => 0,
                'manual_count' => 0,
                'payment_methods' => collect(),
                'income_by_category' => collect(),
                'expenses_by_category' => collect(),
            ],
            'counts' => [
                'total_transactions' => 0,
                'auto_generated' => 0,
                'manual' => 0,
            ],
        ];
    }

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->initializeFinancialSummary(); // Initialize with default structure
        $this->loadFinancialSummary();
    }

    /**
     * Load financial summary from unified reporting system
     */
    public function loadFinancialSummary()
    {
        try {
            $syncService = new FinancialSyncService();
            $report = $syncService->getUnifiedReport(
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate)
            );
            
            // Ensure all required keys exist
            if (!isset($report['totals']) || !isset($report['breakdown'])) {
                $this->initializeFinancialSummary();
            } else {
                $this->financialSummary = $report;
            }
        } catch (\Exception $e) {
            $this->initializeFinancialSummary();
        }
    }

    /**
     * Update financial summary when date range changes
     */
    public function updatedStartDate()
    {
        $this->loadFinancialSummary();
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->loadFinancialSummary();
        $this->resetPage();
    }

    public function with()
    {
        // Get journal entries
        $query = JournalEntry::with(['items.account', 'user'])
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Get unified transactions for journal view
        $transactions = Transaction::whereBetween('date', [$this->startDate, $this->endDate])
            ->with(['user', 'customer'])
            ->when($this->search, function($q) {
                $q->where(function($sub) {
                    $sub->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                        ->orWhere('category', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('date')
            ->latest('created_at')
            ->paginate(10);

        return [
            'entries' => $query->latest('date')->latest('created_at')->paginate(10),
            'transactions' => $transactions,
        ];
    }

    /**
     * Sync sales to transactions
     */
    public function syncSales()
    {
        try {
            $syncService = new FinancialSyncService();
            $result = $syncService->syncAllExistingSales();
            
            $this->dispatch('notify', 
                "Synced {$result['synced']} sales to transactions. " .
                ($result['failed'] > 0 ? "{$result['failed']} failed." : "")
            );
            
            $this->loadFinancialSummary();
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Error syncing sales: ' . $e->getMessage());
        }
    }
}; ?>

<div class="p-6 space-y-6 transition-colors duration-300">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ __('Journal & Financial Analytics') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Unified view of journal entries and financial transactions.') }}
            </p>
        </div>
        <div class="flex gap-3">
            <button wire:click="syncSales" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors text-sm font-medium">
                <i class="fas fa-sync mr-2"></i>{{ __('Sync Sales') }}
            </button>
            <button wire:click="$set('showFinancialSummary', !{{ $showFinancialSummary }})"
                    class="px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition-colors text-sm font-medium">
                <i class="fas fa-chart-bar mr-2"></i>{{ __('Toggle Summary') }}
            </button>
        </div>
    </div>

    <!-- Financial Summary Bento Grid -->
    @if($showFinancialSummary)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Net Balance Card -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Net Balance') }}</span>
                    <i class="fas fa-wallet text-indigo-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($financialSummary['totals']['net_profit'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-indigo-100 text-sm opacity-90">
                    {{ __('Total available balance') }}
                </div>
                <div class="mt-4 text-xs text-indigo-100">
                    <div class="flex justify-between">
                        <span>{{ __('Sales:') }}</span>
                        <span>Rp. {{ number_format($financialSummary['totals']['sales_income'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span>{{ __('Manual:') }}</span>
                        <span>Rp. {{ number_format(($financialSummary['totals']['manual_income'] ?? 0) - ($financialSummary['totals']['expenses'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Income') }}</span>
                    <i class="fas fa-arrow-down text-emerald-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($financialSummary['totals']['total_income'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-emerald-100 text-sm opacity-90">
                    {{ __('Total earnings') }}
                </div>
                <div class="mt-4 text-xs text-emerald-100">
                    <div class="flex justify-between">
                        <span>{{ __('Sales:') }}</span>
                        <span>Rp. {{ number_format($financialSummary['totals']['sales_income'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span>{{ __('Manual:') }}</span>
                        <span>Rp. {{ number_format($financialSummary['totals']['manual_income'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Card -->
        <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-3xl p-6 text-white shadow-lg shadow-red-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Expenses') }}</span>
                    <i class="fas fa-arrow-up text-red-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($financialSummary['totals']['expenses'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-red-100 text-sm opacity-90">
                    {{ __('Total spending') }}
                </div>
                <div class="mt-4 text-xs text-red-100">
                    <div class="flex justify-between">
                        <span>{{ __('Manual:') }}</span>
                        <span>Rp. {{ number_format($financialSummary['totals']['expenses'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-xs mt-1 opacity-75">
                        {{ $financialSummary['breakdown']['auto_generated_count'] ?? 0 }} {{ __('auto') }} • {{ $financialSummary['breakdown']['manual_count'] ?? 0 }} {{ __('manual') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters Section -->
    <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Search') }}</label>
                <input wire:model.live="search" type="text" placeholder="{{ __('Search reference or description...') }}" 
                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Start Date') }}</label>
                <input wire:model.live="startDate" type="date" 
                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('End Date') }}</label>
                <input wire:model.live="endDate" type="date" 
                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
        </div>
    </div>

    <!-- Tabs for Journal Entries vs Transactions -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('Journal Entries') }}
            </a>
            <a href="#" class="border-indigo-500 text-indigo-600 dark:text-indigo-400 border-b-2 whitespace-nowrap py-4 px-1 font-medium text-sm">
                {{ __('Transactions') }}
            </a>
        </nav>
    </div>

    <!-- Unified Financial Data Display -->
    <div class="space-y-6">
        <!-- Transactions List (from unified system) -->
        @forelse($transactions as $transaction)
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-shadow hover:shadow-md">
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">
                            {{ $transaction->reference_number ?? 'TRX-' . $transaction->id }}
                        </span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $transaction->date->format('M d, Y') }}</span>
                        @if($transaction->isAutoGenerated())
                            <span class="ml-2 px-2 py-1 text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-full">{{ __('Auto') }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $transaction->type === 'income' ? '+' : '-' }} Rp. {{ number_format($transaction->amount, 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($transaction->status) }}</div>
                    </div>
                </div>
                <div class="px-6 py-6">
                    <p class="text-gray-700 dark:text-gray-300 mb-4 font-medium">{{ $transaction->description }}</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Type') }}</span>
                            <div class="font-medium {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ ucfirst($transaction->type) }}
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Category') }}</span>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $transaction->category ?? '-' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Payment') }}</span>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $transaction->payment_method ?? '-' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Source') }}</span>
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $transaction->isAutoGenerated() ? __('System') : __('Manual') }}
                            </div>
                        </div>
                    </div>
                    
                    @if($transaction->customer)
                        <div class="mt-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Customer') }}</span>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $transaction->customer->name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('No Financial Transactions') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ __('No transactions found for the selected period.') }}</p>
            </div>
        @endforelse

        <div class="pt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>