<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $accountId;
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
            <!-- Card 1: Net Change -->
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

            <!-- Card 2: Total In -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-green-200 dark:hover:border-green-800 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase">{{ __('Total In') }}</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    Rp. {{ number_format($summary['in'], 0, ',', '.') }}
                </div>
                <div class="text-sm text-green-600 dark:text-green-400 flex items-center">
                    <i class="fas fa-level-up-alt mr-1"></i> {{ __('Debit Transactions') }}
                </div>
            </div>

            <!-- Card 3: Total Out -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-red-200 dark:hover:border-red-800 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase">{{ __('Total Out') }}</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    Rp. {{ number_format($summary['out'], 0, ',', '.') }}
                </div>
                <div class="text-sm text-red-600 dark:text-red-400 flex items-center">
                    <i class="fas fa-level-down-alt mr-1"></i> {{ __('Credit Transactions') }}
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                <!-- Toolbar -->
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row gap-4 justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                    <div class="flex gap-4 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0">
                         <!-- Date Range -->
                        <div class="flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl p-1">
                            <input wire:model.live="startDate" type="date" 
                                class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 p-1.5 cursor-pointer">
                            <span class="text-gray-400">-</span>
                            <input wire:model.live="endDate" type="date" 
                                class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 p-1.5 cursor-pointer">
                        </div>

                        <!-- Type Filter -->
                        <div class="relative">
                            <select wire:model.live="typeFilter" 
                                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block pl-3 pr-8 py-2 w-full cursor-pointer appearance-none">
                                <option value="all">{{ __('All Types') }}</option>
                                <option value="in">{{ __('Cash In') }}</option>
                                <option value="out">{{ __('Cash Out') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/80 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-medium text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">{{ __('Date') }}</th>
                                <th class="px-6 py-4">{{ __('Ref / Description') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('In (Debit)') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Out (Credit)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($transactions as $item)
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
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-file-invoice text-2xl opacity-50"></i>
                                            </div>
                                            <p class="text-lg font-medium text-gray-500 dark:text-gray-400">{{ __('No records found') }}</p>
                                            <p class="text-sm mt-1">{{ __('Try adjusting your date range or filters') }}</p>
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
