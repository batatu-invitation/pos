<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Cash & Bank Records')] class extends Component {
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    
    // Modal state
    public $showModal = false;
    public $viewingEntry = null;

    // Form fields
    public $transactionType = 'cash_in'; // cash_in, cash_out, bank_in, bank_out
    public $date;
    public $reference = '';
    public $description = '';
    public $mainAccountId = ''; // The Cash/Bank Account
    public $contraAccountId = ''; // The Revenue/Expense Account
    public $amount = 0;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function with()
    {
        $entries = JournalEntry::with(['items.account'])
            ->whereIn('type', ['cash_in', 'cash_out', 'bank_in', 'bank_out'])
            ->when($this->search, function($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%");
            })
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->orderBy('date', 'desc')
            ->paginate(15);

        // Filter accounts for dropdowns
        $cashBankAccounts = Account::where('type', 'asset')
            ->where(function($q) {
                $q->where('subtype', 'like', '%Cash%')
                  ->orWhere('subtype', 'like', '%Bank%')
                  ->orWhere('name', 'like', '%Cash%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->orderBy('name')
            ->get();

        $allAccounts = Account::orderBy('code')->orderBy('name')->get();

        return [
            'entries' => $entries,
            'cashBankAccounts' => $cashBankAccounts,
            'allAccounts' => $allAccounts,
        ];
    }

    public function create()
    {
        $this->reset(['transactionType', 'reference', 'description', 'mainAccountId', 'contraAccountId', 'amount']);
        $this->date = Carbon::now()->format('Y-m-d');
        $this->transactionType = 'cash_in';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'transactionType' => 'required|in:cash_in,cash_out,bank_in,bank_out',
            'date' => 'required|date',
            'mainAccountId' => 'required|exists:accounts,id',
            'contraAccountId' => 'required|exists:accounts,id|different:mainAccountId',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
        ]);

        DB::transaction(function () {
            $entry = JournalEntry::create([
                'date' => $this->date,
                'reference' => $this->reference,
                'description' => $this->description,
                'type' => $this->transactionType,
                'status' => 'posted', // Auto-post cash transactions? usually yes.
                'user_id' => auth()->id(),
            ]);

            // Determine Debit/Credit based on Type
            // Cash In / Bank In: Debit Main (Asset), Credit Contra (Revenue/Liability)
            // Cash Out / Bank Out: Credit Main (Asset), Debit Contra (Expense/Liability)

            $isReceipt = in_array($this->transactionType, ['cash_in', 'bank_in']);

            if ($isReceipt) {
                // Debit Main Account
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->mainAccountId,
                    'debit' => $this->amount,
                    'credit' => 0,
                ]);
                // Credit Contra Account
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->contraAccountId,
                    'debit' => 0,
                    'credit' => $this->amount,
                ]);
            } else {
                // Debit Contra Account (Expense)
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->contraAccountId,
                    'debit' => $this->amount,
                    'credit' => 0,
                ]);
                // Credit Main Account (Asset)
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->mainAccountId,
                    'debit' => 0,
                    'credit' => $this->amount,
                ]);
            }
        });

        $this->showModal = false;
        session()->flash('message', 'Transaction recorded successfully.');
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Cash & Bank Records</h1>
            <p class="text-gray-500 dark:text-gray-400">Manage daily cash and bank transactions</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> New Transaction
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 flex gap-4">
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="fas fa-search text-gray-400"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search transactions..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <select wire:model.live="typeFilter" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Types</option>
            <option value="cash_in">Cash In</option>
            <option value="cash_out">Cash Out</option>
            <option value="bank_in">Bank In</option>
            <option value="bank_out">Bank Out</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $entry->date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if(in_array($entry->type, ['cash_in', 'bank_in'])) bg-green-100 text-green-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucwords(str_replace('_', ' ', $entry->type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $entry->reference }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $entry->description }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                            {{ number_format($entry->items->sum('debit') / 2 + $entry->items->sum('credit') / 2, 2) }}
                            <!-- Approximation since total debit = total credit -->
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $entries->links() }}
        </div>
    </div>

    <!-- Create Modal -->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">New Transaction</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="transactionType" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="cash_in">Cash In (Receipt)</option>
                        <option value="cash_out">Cash Out (Payment)</option>
                        <option value="bank_in">Bank In (Receipt)</option>
                        <option value="bank_out">Bank Out (Payment)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" wire:model="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        @if(in_array($transactionType, ['cash_in', 'cash_out'])) Cash Account @else Bank Account @endif
                    </label>
                    <select wire:model="mainAccountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Account</option>
                        @foreach($cashBankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('mainAccountId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        @if(in_array($transactionType, ['cash_in', 'bank_in'])) From Account (Revenue/Payer) @else To Account (Expense/Payee) @endif
                    </label>
                    <select wire:model="contraAccountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Account</option>
                        @foreach($allAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }} ({{ $account->type }})</option>
                        @endforeach
                    </select>
                    @error('contraAccountId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" step="0.01" wire:model="amount" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" wire:model="reference" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button wire:click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Save Transaction
                </button>
            </div>
        </div>
    </div>
    @endif
</div>