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
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'selectedAccount' => $this->accountId ? Account::find($this->accountId) : null,
        ];
    }
};

?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Cash & Bank Records</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Monitor cash flows and bank transactions.</p>
        </div>
        
        @if($selectedAccount)
        <div class="flex gap-4">
            <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                <span class="text-sm font-medium text-green-600 dark:text-green-300">Total In (Debit)</span>
                <div class="text-xl font-bold text-green-800 dark:text-green-100">
                    {{ number_format($totalIn, 2) }}
                </div>
            </div>
            <div class="bg-red-100 dark:bg-red-900 p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
                <span class="text-sm font-medium text-red-600 dark:text-red-300">Total Out (Credit)</span>
                <div class="text-xl font-bold text-red-800 dark:text-red-100">
                    {{ number_format($totalOut, 2) }}
                </div>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800">
                <span class="text-sm font-medium text-blue-600 dark:text-blue-300">Net Change</span>
                <div class="text-xl font-bold text-blue-800 dark:text-blue-100">
                    {{ number_format($totalIn - $totalOut, 2) }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Account</label>
            <select wire:model.live="accountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Select Account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
            <select wire:model.live="typeFilter" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="all">All Transactions</option>
                <option value="in">Cash/Bank In</option>
                <option value="out">Cash/Bank Out</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
            <input wire:model.live="startDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
            <input wire:model.live="endDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ref / Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">In (Debit)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Out (Credit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $item->journalEntry->date->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->journalEntry->reference }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->journalEntry->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium {{ $item->debit > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                {{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium {{ $item->credit > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                                {{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No records found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
