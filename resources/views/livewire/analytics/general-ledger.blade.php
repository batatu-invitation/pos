<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

new class extends Component {
    public $accountId;
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function with()
    {
        $accounts = Account::orderBy('code')->get();
        
        $openingBalance = 0;
        $ledgerItems = [];
        $selectedAccount = null;

        if ($this->accountId) {
            $selectedAccount = Account::find($this->accountId);
            
            if ($selectedAccount) {
                // Determine account normal balance
                $isDebitNormal = in_array($selectedAccount->type, ['asset', 'expense']);

                // Calculate Opening Balance (only posted entries)
                $prevItems = JournalEntryItem::where('account_id', $this->accountId)
                    ->whereHas('journalEntry', function($q) {
                        $q->where('date', '<', $this->startDate)
                          ->where('status', 'posted');
                    })
                    ->get();

                $prevDebits = $prevItems->sum('debit');
                $prevCredits = $prevItems->sum('credit');

                if ($isDebitNormal) {
                    $openingBalance = $prevDebits - $prevCredits;
                } else {
                    $openingBalance = $prevCredits - $prevDebits;
                }

                // Get Period Items
                $periodItems = JournalEntryItem::with('journalEntry')
                    ->where('account_id', $this->accountId)
                    ->whereHas('journalEntry', function($q) {
                        $q->whereBetween('date', [$this->startDate, $this->endDate])
                           ->where('status', 'posted');
                    })
                    ->get()
                    ->sortBy(function($item) {
                        return $item->journalEntry->date->format('Y-m-d') . '-' . $item->created_at->format('H:i:s');
                    });

                // Calculate Running Balances
                $runningBalance = $openingBalance;
                
                foreach ($periodItems as $item) {
                    if ($isDebitNormal) {
                        $runningBalance += ($item->debit - $item->credit);
                    } else {
                        $runningBalance += ($item->credit - $item->debit);
                    }
                    
                    $ledgerItems[] = [
                        'date' => $item->journalEntry->date,
                        'reference' => $item->journalEntry->reference,
                        'description' => $item->journalEntry->description,
                        'debit' => $item->debit,
                        'credit' => $item->credit,
                        'balance' => $runningBalance,
                    ];
                }
            }
        }

        return [
            'accounts' => $accounts,
            'ledgerItems' => $ledgerItems,
            'openingBalance' => $openingBalance,
            'selectedAccount' => $selectedAccount,
        ];
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">General Ledger</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Summary of movements in each financial account.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account</label>
            <select wire:model.live="accountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Select Account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
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

    @if($selectedAccount)
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $selectedAccount->code }} - {{ $selectedAccount->name }}
                </h3>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    {{ ucfirst($selectedAccount->type) }}
                </span>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Date') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Reference') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Description') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Debit') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Credit') }}</th>
                            <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr class="bg-gray-50/30 dark:bg-gray-800/30 font-medium">
                            <td class="p-4 text-sm text-gray-900 dark:text-white" colspan="5">{{ __('Opening Balance') }}</td>
                            <td class="p-4 text-sm text-right text-gray-900 dark:text-white">
                                Rp. {{ number_format($openingBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                        @forelse($ledgerItems as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $item['date']->format('d M Y') }}</td>
                                <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">{{ $item['reference'] }}</td>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $item['description'] }}</td>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                    {{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                    {{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="p-4 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    Rp. {{ number_format($item['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('No transactions found in this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('Select an Account') }}</h3>
            <p class="text-gray-500 dark:text-gray-400">{{ __('Please select an account above to view its general ledger.') }}</p>
        </div>
    @endif
</div>
