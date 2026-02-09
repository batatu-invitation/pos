<?php

use Livewire\Volt\Component;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Account;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] #[Title('Journal Entries')] class extends Component {
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    
    // Modal state
    public $showModal = false;
    public $viewingEntry = null;

    // Form fields
    public $date = '';
    public $reference = '';
    public $description = '';
    public $type = 'general';
    public $status = 'draft';
    public $items = []; // [['account_id' => '', 'debit' => 0, 'credit' => 0, 'description' => '']]

    public $accounts = [];

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->accounts = Account::where('is_active', true)->orderBy('code')->get();
        $this->addItem(); // Add initial two rows
        $this->addItem();
    }

    public function addItem()
    {
        $this->items[] = [
            'account_id' => '',
            'debit' => 0,
            'credit' => 0,
        ];
    }

    public function removeItem($index)
    {
        if (count($this->items) > 2) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getTotalDebitProperty()
    {
        return collect($this->items)->sum('debit');
    }

    public function getTotalCreditProperty()
    {
        return collect($this->items)->sum('credit');
    }

    public function getIsBalancedProperty()
    {
        return abs($this->totalDebit - $this->totalCredit) < 0.01;
    }

    public function with()
    {
        return [
            'entries' => JournalEntry::with(['items.account'])
                ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%"))
                ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
                ->latest('date')
                ->paginate(15),
        ];
    }

    public function create()
    {
        $this->reset(['date', 'reference', 'description', 'status', 'type']);
        $this->date = date('Y-m-d');
        $this->type = 'general';
        $this->items = [];
        $this->addItem();
        $this->addItem();
        $this->showModal = true;
        $this->viewingEntry = null;
    }

    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:general,adjustment,opening_balance,cash_in,cash_out,bank_in,bank_out',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ]);

        if (!$this->isBalanced) {
            $this->addError('items', 'Journal entry must be balanced (Total Debit must equal Total Credit).');
            return;
        }

        DB::transaction(function () {
            $entry = JournalEntry::create([
                'date' => $this->date,
                'reference' => $this->reference,
                'description' => $this->description,
                'type' => $this->type,
                'status' => 'draft', // Manual entries start as draft
                'user_id' => auth()->id(),
            ]);

            foreach ($this->items as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item['account_id'],
                    'debit' => $item['debit'],
                    'credit' => $item['credit'],
                ]);
            }
        });

        $this->showModal = false;
        session()->flash('message', 'Journal entry created successfully.');
        $this->reset(['date', 'reference', 'description', 'status', 'type', 'items']);
        $this->addItem();
        $this->addItem();
    }

    public function view($id)
    {
        $this->viewingEntry = JournalEntry::with('items.account')->find($id);
        $this->showModal = true;
    }

    public function post($id)
    {
        $entry = JournalEntry::find($id);
        if ($entry && $entry->status === 'draft') {
            $entry->update(['status' => 'posted']);
            session()->flash('message', 'Journal entry posted successfully.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Journal Entries</h1>
            <p class="text-gray-500 dark:text-gray-400">Record and manage daily financial transactions</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> New Journal Entry
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
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search reference or description..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <select wire:model.live="typeFilter" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Types</option>
            <option value="general">General</option>
            <option value="adjustment">Adjustment (Memo)</option>
            <option value="opening_balance">Opening Balance</option>
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
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $entry->date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $entry->reference }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $entry->description }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $entry->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                Rp. {{ number_format($entry->items->sum('debit'), 0, ',', '.') }}
                            </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="view('{{ $entry->id }}')" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </button>
                            @if($entry->status === 'draft')
                                <button wire:confirm="Are you sure you want to post this entry? This cannot be undone." wire:click="post('{{ $entry->id }}')" class="text-green-600 hover:text-green-900">
                                    Post
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">No journal entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $entries->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto">
            @if($viewingEntry)
                <!-- View Mode -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Journal Entry #{{ $viewingEntry->reference ?? 'N/A' }}</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $viewingEntry->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($viewingEntry->status) }}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium">{{ $viewingEntry->date->format('Y-m-d') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Type</p>
                        <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $viewingEntry->type)) }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="font-medium">{{ $viewingEntry->description }}</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 mb-6">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($viewingEntry->items as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $item->account->code }} - {{ $item->account->name }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ $item->debit > 0 ? 'Rp. ' . number_format($item->debit, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ $item->credit > 0 ? 'Rp. ' . number_format($item->credit, 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-bold">
                            <td class="px-4 py-2 text-right">Totals</td>
                            <td class="px-4 py-2 text-right">Rp. {{ number_format($viewingEntry->items->sum('debit'), 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp. {{ number_format($viewingEntry->items->sum('credit'), 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Close
                    </button>
                </div>

            @else
                <!-- Create Mode -->
                <h2 class="text-xl font-bold mb-4">New Journal Entry</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" wire:model="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reference</label>
                        <input type="text" wire:model="reference" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="general">General</option>
                            <option value="adjustment">Adjustment (Memo)</option>
                            <option value="opening_balance">Opening Balance</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="border rounded-md overflow-hidden mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Debit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Credit</th>
                                <th class="px-4 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="px-4 py-2">
                                        <select wire:model="items.{{ $index }}.account_id" class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("items.{$index}.account_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.debit" class="w-full text-sm text-right border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.credit" class="w-full text-sm text-right border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td class="px-4 py-2">
                                    <button wire:click="addItem" class="text-indigo-600 text-sm font-medium hover:text-indigo-900">
                                        + Add Line
                                    </button>
                                </td>
                                <td class="px-4 py-2 text-right {{ !$this->isBalanced ? 'text-red-600' : 'text-green-600' }}">
                                    Rp. {{ number_format($this->totalDebit, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-right {{ !$this->isBalanced ? 'text-red-600' : 'text-green-600' }}">
                                    Rp. {{ number_format($this->totalCredit, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    @error('items') <p class="text-red-500 text-sm px-4 py-2">{{ $message }}</p> @enderror
                    @if(!$this->isBalanced)
                        <p class="text-red-500 text-sm px-4 py-2">Entries must be balanced (Difference: Rp. {{ number_format(abs($this->totalDebit - $this->totalCredit), 0, ',', '.') }})</p>
                    @endif
                </div>

                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed" @if(!$this->isBalanced) disabled @endif>
                        Save as Draft
                    </button>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>