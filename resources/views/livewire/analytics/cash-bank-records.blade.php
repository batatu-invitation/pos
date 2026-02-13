<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $accountId;
    public $search = '';
    public $startDate;
    public $endDate;
    public $typeFilter = 'all'; // all, in, out

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        // Default to first Cash/Bank account
        $firstAccount = Account::where('type', 'asset')
            ->where(function($q) {
                $q->where('name', 'like', '%Cash%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->orderBy('code')
            ->first();
            
        if ($firstAccount) {
            $this->accountId = $firstAccount->id;
        }
    }

    public function with()
    {
        $accounts = Account::where('type', 'asset')
            ->orderBy('code')
            ->get();

        $transactions = collect();
        $totalIn = 0;
        $totalOut = 0;

        if ($this->accountId) {
            // Calculate totals for the period
            $summaryQuery = JournalEntryItem::where('account_id', $this->accountId)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                });
                
            $totalIn = (clone $summaryQuery)->sum('debit');
            $totalOut = (clone $summaryQuery)->sum('credit');

            // Get Transactions
            $query = JournalEntryItem::select('journal_entry_items.*')
                ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                ->with(['journalEntry']) // Eager load for display
                ->where('journal_entry_items.account_id', $this->accountId)
                ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
                ->where('journal_entries.status', 'posted');

            if ($this->search) {
                $query->whereHas('journalEntry', function($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                      ->orWhere('reference', 'like', "%{$this->search}%");
                });
            }
                
            if ($this->typeFilter === 'in') {
                $query->where('journal_entry_items.debit', '>', 0);
            } elseif ($this->typeFilter === 'out') {
                $query->where('journal_entry_items.credit', '>', 0);
            }
            
            $transactions = $query->orderBy('journal_entries.date', 'desc')
                ->orderBy('journal_entries.created_at', 'desc')
                ->paginate(15);
        } else {
             $transactions = JournalEntryItem::where('id', null)->paginate(15); // Empty paginator
        }

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'summary' => [
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => $totalIn - $totalOut
            ],
            'selectedAccount' => $this->accountId ? Account::find($this->accountId) : null,
        ];
    }
};

?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Cash & Bank Records') }}</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Monitor cash flows and bank transactions.') }}</p>
        </div>
        
        <!-- Account Selector (Prominent) -->
        <div class="w-full md:w-72">
            <div class="relative">
                <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                <select wire:model.live="accountId" 
                    class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                    <option value="">{{ __('Select Account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($selectedAccount)
        <!-- Bento Grid Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Net Change -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Net Change') }}</span>
                        <i class="fas fa-chart-pie text-indigo-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($summary['net'], 0, ',', '.') }}
                    </div>
                    <div class="text-indigo-100 text-sm opacity-90">
                        {{ __('Net flow for selected period') }}
                    </div>
                </div>
            </div>

            <!-- Total In -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total In') }}</span>
                        <i class="fas fa-arrow-down text-emerald-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($summary['in'], 0, ',', '.') }}
                    </div>
                    <div class="text-emerald-100 text-sm opacity-90">
                        {{ __('Debit Transactions') }}
                    </div>
                </div>
            </div>

            <!-- Total Out -->
            <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-6 text-white shadow-lg shadow-rose-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Out') }}</span>
                        <i class="fas fa-arrow-up text-rose-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($summary['out'], 0, ',', '.') }}
                    </div>
                    <div class="text-rose-100 text-sm opacity-90">
                        {{ __('Credit Transactions') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Filters</h3>
                    
                    <div class="space-y-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Description or Ref..." 
                                    class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white transition-all text-sm">
                            </div>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                            <div class="relative">
                                <select wire:model.live="typeFilter" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm appearance-none pl-3 pr-8 py-2">
                                    <option value="all">{{ __('All Types') }}</option>
                                    <option value="in">{{ __('In (Debit)') }}</option>
                                    <option value="out">{{ __('Out (Credit)') }}</option>
                                    <option value="cash_in">Cash In</option>
                                    <option value="cash_out">Cash Out</option>
                                    <option value="bank_in">Bank In</option>
                                    <option value="bank_out">Bank Out</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period</label>
                            <div class="space-y-2">
                                <input wire:model.live="startDate" type="date" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm">
                                <input wire:model.live="endDate" type="date" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-medium text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">{{ __('Date') }}</th>
                                    <th class="px-6 py-4">{{ __('Ref / Description') }}</th>
                                    <th class="px-6 py-4 text-right">{{ __('In (Debit)') }}</th>
                                    <th class="px-6 py-4 text-right">{{ __('Out (Credit)') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($transactions as $item)
                                @if($item->journalEntry)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $item->journalEntry->date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $item->journalEntry->reference }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->journalEntry->description }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if($item->debit > 0)
                                            <span class="font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-lg border border-green-100 dark:border-green-800/30">
                                                Rp. {{ number_format($item->debit, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if($item->credit > 0)
                                            <span class="font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded-lg border border-red-100 dark:border-red-800/30">
                                                Rp. {{ number_format($item->credit, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                                <i class="fas fa-file-invoice text-gray-400 text-2xl"></i>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-base font-medium">{{ __('No records found') }}</p>
                                            <p class="text-gray-400 text-sm">{{ __('Try adjusting your date range or filters') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-university text-3xl text-indigo-500 dark:text-indigo-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('No Account Selected') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">{{ __('Please select a cash or bank account from the dropdown above to view records.') }}</p>
        </div>
    @endif
</div>
