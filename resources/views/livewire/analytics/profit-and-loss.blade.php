<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;

new class extends Component {
    public $startDate;
    public $endDate;
    public $report = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->generateReport();
    }

    public function updatedStartDate() { $this->generateReport(); }
    public function updatedEndDate() { $this->generateReport(); }

    public function generateReport()
    {
        $this->report = [
            'income' => [],
            'expenses' => [],
            'total_income' => 0,
            'total_expenses' => 0,
            'net_profit' => 0,
        ];

        // Income
        $incomeAccounts = Account::where('type', 'income')->orderBy('code')->get();
        foreach ($incomeAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                })
                ->get();
            
            // Income is Credit normal
            $balance = $items->sum('credit') - $items->sum('debit');
            
            if (abs($balance) > 0.001) {
                $this->report['income'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                $this->report['total_income'] += $balance;
            }
        }

        // Expenses
        $expenseAccounts = Account::where('type', 'expense')->orderBy('code')->get();
        foreach ($expenseAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                })
                ->get();
            
            // Expense is Debit normal
            $balance = $items->sum('debit') - $items->sum('credit');
            
            if (abs($balance) > 0.001) {
                $this->report['expenses'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                $this->report['total_expenses'] += $balance;
            }
        }

        $this->report['net_profit'] = $this->report['total_income'] - $this->report['total_expenses'];
    }
};
?>

<div class="max-w-7xl mx-auto p-6 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Profit & Loss</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor your business performance and financial health.</p>
        </div>
        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-1.5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <input wire:model.live="startDate" type="date" class="border-0 bg-transparent text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-0">
            <span class="text-gray-400">-</span>
            <input wire:model.live="endDate" type="date" class="border-0 bg-transparent text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-0">
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Income -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Income</span>
                    <i class="fas fa-chart-line text-emerald-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($report['total_income'], 0, ',', '.') }}
                </div>
                <div class="text-emerald-100 text-sm opacity-90">
                    Revenue generated
                </div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-3xl p-6 text-white shadow-lg shadow-rose-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Expenses</span>
                    <i class="fas fa-file-invoice-dollar text-rose-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($report['total_expenses'], 0, ',', '.') }}
                </div>
                <div class="text-rose-100 text-sm opacity-90">
                    Costs incurred
                </div>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Net Profit</span>
                    <i class="fas fa-wallet text-indigo-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($report['net_profit'], 0, ',', '.') }}
                </div>
                <div class="text-indigo-100 text-sm opacity-90">
                    {{ $report['net_profit'] >= 0 ? 'Profit' : 'Loss' }} for period
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Income Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/30">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <div class="w-2 h-8 bg-emerald-500 rounded-full"></div>
                    Income Details
                </h3>
                <span class="text-sm text-gray-500">{{ count($report['income']) }} Accounts</span>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/30 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-6 py-4">Account</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($report['income'] as $inc)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $inc['name'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $inc['code'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                    Rp. {{ number_format($inc['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-gray-500 text-sm">No income records found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-6 bg-emerald-50/50 dark:bg-emerald-900/10 border-t border-emerald-100 dark:border-emerald-800">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-emerald-900 dark:text-emerald-400">Total Income</span>
                    <span class="font-bold text-emerald-900 dark:text-emerald-400 text-xl">Rp. {{ number_format($report['total_income'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/30">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <div class="w-2 h-8 bg-rose-500 rounded-full"></div>
                    Expense Details
                </h3>
                <span class="text-sm text-gray-500">{{ count($report['expenses']) }} Accounts</span>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/30 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-6 py-4">Account</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($report['expenses'] as $exp)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $exp['name'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $exp['code'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                    Rp. {{ number_format($exp['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-gray-500 text-sm">No expense records found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-6 bg-rose-50/50 dark:bg-rose-900/10 border-t border-rose-100 dark:border-rose-800">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-rose-900 dark:text-rose-400">Total Expenses</span>
                    <span class="font-bold text-rose-900 dark:text-rose-400 text-xl">Rp. {{ number_format($report['total_expenses'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>