<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use App\Models\JournalEntry;
use Carbon\Carbon;

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
        
        $entries = collect();
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
}; ?>

<div class="p-6">
    <div class="mb-6 flex flex-col md:flex-row md:items-end gap-4 bg-white p-4 rounded-lg shadow">
        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
            <select wire:model.live="accountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select Account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">
                        {{ $account->code }} - {{ $account->name }} ({{ ucfirst($account->type) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-1/4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" wire:model.live="startDate" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="w-full md:w-1/4">
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" wire:model.live="endDate" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    @if($selectedAccount)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h2>
                    <p class="text-sm text-gray-500">{{ ucfirst($selectedAccount->type) }} | {{ ucfirst($selectedAccount->subtype ?? 'General') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Opening Balance</p>
                    <p class="text-lg font-bold {{ $openingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                        Rp. {{ number_format($openingBalance, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Opening Balance Row -->
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Opening Balance</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">Rp. {{ number_format($openingBalance, 0, ',', '.') }}</td>
                        </tr>

                        @forelse($ledgerItems as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['date']->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['reference'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $item['description'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ $item['debit'] > 0 ? 'Rp. ' . number_format($item['debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ $item['credit'] > 0 ? 'Rp. ' . number_format($item['credit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">
                                    Rp. {{ number_format($item['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">No transactions found in this period.</td>
                            </tr>
                        @endforelse
                        
                        <!-- Closing Balance Row -->
                        @if(count($ledgerItems) > 0)
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="3" class="px-6 py-4 text-right text-gray-900">Totals</td>
                                <td class="px-6 py-4 text-right text-gray-900">Rp. {{ number_format(collect($ledgerItems)->sum('debit'), 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-gray-900">Rp. {{ number_format(collect($ledgerItems)->sum('credit'), 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-gray-900">Rp. {{ number_format(end($ledgerItems)['balance'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No Account Selected</h3>
            <p class="mt-1 text-sm text-gray-500">Select an account to view its general ledger.</p>
        </div>
    @endif
</div>