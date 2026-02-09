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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $selectedAccount->code }} - {{ $selectedAccount->name }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Type: {{ ucfirst($selectedAccount->type) }} 
                @if($selectedAccount->subtype) | {{ $selectedAccount->subtype }} @endif
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Credit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="bg-gray-50 dark:bg-gray-800 font-medium">
                        <td colspan="5" class="px-6 py-4 text-gray-900 dark:text-white text-right">Opening Balance</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">Rp. {{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>
                    @forelse($ledgerItems as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $item['date']->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $item['reference'] }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{Str::limit($item['description'], 50)}}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                {{ $item['debit'] > 0 ? 'Rp. ' . number_format($item['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                {{ $item['credit'] > 0 ? 'Rp. ' . number_format($item['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-white">
                                Rp. {{ number_format($item['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No transactions found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
        Please select an account to view the ledger.
    </div>
    @endif
</div>
