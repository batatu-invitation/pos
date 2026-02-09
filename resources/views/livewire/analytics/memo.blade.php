<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    // List Properties
    public $startDate;
    public $endDate;
    public $search = '';

    // Modal Properties
    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form Properties
    public $date;
    public $reference;
    public $description;
    public $lines = []; 

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->date = Carbon::now()->format('Y-m-d');
        $this->initLines();
    }

    public function initLines()
    {
        $this->lines = [
            ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
            ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ];
    }

    public function addLine()
    {
        $this->lines[] = ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''];
    }

    public function removeLine($index)
    {
        if (count($this->lines) > 2) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    public function create()
    {
        $this->reset(['isEditing', 'editingId', 'reference', 'description']);
        $this->date = Carbon::now()->format('Y-m-d');
        $this->reference = 'MEMO-' . strtoupper(Str::random(8));
        $this->initLines();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $entry = JournalEntry::with('items')->find($id);
        if (!$entry) return;

        $this->isEditing = true;
        $this->editingId = $entry->id;
        $this->date = $entry->date->format('Y-m-d');
        $this->reference = $entry->reference;
        $this->description = $entry->description;
        
        $this->lines = [];
        foreach ($entry->items as $item) {
            $this->lines[] = [
                'account_id' => $item->account_id,
                'debit' => $item->debit > 0 ? $item->debit : '',
                'credit' => $item->credit > 0 ? $item->credit : '',
                'description' => $item->description ?? '',
            ];
        }

        // Ensure at least 2 lines
        while (count($this->lines) < 2) {
            $this->lines[] = ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''];
        }

        $this->showModal = true;
    }

    public function delete($id)
    {
        $entry = JournalEntry::find($id);
        if ($entry) {
            $entry->items()->delete();
            $entry->delete();
            $this->dispatch('notify', title: 'Deleted', text: 'Memo deleted successfully.', icon: 'success');
        }
    }

    public function getAccountsProperty()
    {
        return Account::where('is_active', true)->orderBy('code')->get();
    }

    public function getTotalDebitProperty()
    {
        return collect($this->lines)->sum(fn($line) => (float)($line['debit'] ?: 0));
    }

    public function getTotalCreditProperty()
    {
        return collect($this->lines)->sum(fn($line) => (float)($line['credit'] ?: 0));
    }

    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'reference' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        // Validate Balance
        $totalDebit = $this->totalDebit;
        $totalCredit = $this->totalCredit;

        if (abs($totalDebit - $totalCredit) > 0.01) {
             $this->dispatch('notify-error', title: 'Unbalanced Entry', text: 'Total Debit must equal Total Credit.');
             return;
        }

        if ($totalDebit == 0) {
            $this->dispatch('notify-error', title: 'Zero Value', text: 'Entry must have a value.');
            return;
        }

        DB::transaction(function () {
            if ($this->isEditing) {
                $entry = JournalEntry::find($this->editingId);
                $entry->update([
                    'date' => $this->date,
                    'reference' => $this->reference,
                    'description' => $this->description,
                ]);
                
                $entry->items()->delete();
            } else {
                $entry = JournalEntry::create([
                    'date' => $this->date,
                    'reference' => $this->reference,
                    'description' => $this->description,
                    'type' => 'adjustment',
                    'status' => 'posted',
                    'user_id' => auth()->id() ?? null,
                ]);
            }

            foreach ($this->lines as $line) {
                $debit = (float)($line['debit'] ?: 0);
                $credit = (float)($line['credit'] ?: 0);

                if ($debit > 0 || $credit > 0) {
                    JournalEntryItem::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $line['account_id'],
                        'debit' => $debit,
                        'credit' => $credit,
                    ]);
                }
            }
        });

        $this->showModal = false;
        $this->dispatch('notify', title: 'Success', text: 'Memo saved successfully.', icon: 'success');
        $this->reset(['isEditing', 'editingId', 'reference', 'description', 'lines']);
        $this->initLines();
    }
    
    public function with()
    {
        $query = JournalEntry::with(['items.account', 'user'])
            ->where('type', 'adjustment')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'entries' => $query->latest('date')->latest('created_at')->paginate(10),
            'accounts' => $this->accounts,
        ];
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Memos & Adjustments</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Manage internal adjustments and manual journal entries.</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Memo
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input wire:model.live="search" type="text" placeholder="Search reference or description..." 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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

    <!-- List -->
    <div class="space-y-4">
        @forelse($entries as $entry)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">{{ $entry->reference }}</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $entry->date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                         <button wire:click="edit('{{ $entry->id }}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Edit</button>
                         <button wire:click="delete('{{ $entry->id }}')" 
                            wire:confirm="Are you sure you want to delete this memo?"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $entry->description }}</p>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Debit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($entry->items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            {{ $item->account->code }} - {{ $item->account->name }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                            {{ $item->debit > 0 ? number_format($item->debit, 2) : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                            {{ $item->credit > 0 ? number_format($item->credit, 2) : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
                No memos found for the selected period.
            </div>
        @endforelse

        <div class="pt-4">
            {{ $entries->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    {{ $isEditing ? 'Edit Memo' : 'New Memo' }}
                                </h3>
                                
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                        <input type="date" wire:model="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reference</label>
                                        <input type="text" wire:model="reference" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('reference') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                        <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-gray-200">Journal Lines</h4>
                                        <button type="button" wire:click="addLine" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">+ Add Line</button>
                                    </div>
                                    
                                    <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2">
                                        @foreach($lines as $index => $line)
                                            <div class="flex flex-col md:flex-row gap-2 items-start border-b border-gray-100 dark:border-gray-700 pb-2 mb-2">
                                                <div class="flex-1 w-full md:w-auto">
                                                    <select wire:model="lines.{{ $index }}.account_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                                        <option value="">Select Account</option>
                                                        @foreach($this->accounts as $account)
                                                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('lines.'.$index.'.account_id') <span class="text-red-500 text-xs">Required</span> @enderror
                                                </div>
                                                <div class="w-full md:w-32">
                                                    <input type="number" step="0.01" placeholder="Debit" wire:model="lines.{{ $index }}.debit" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm text-right">
                                                </div>
                                                <div class="w-full md:w-32">
                                                    <input type="number" step="0.01" placeholder="Credit" wire:model="lines.{{ $index }}.credit" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm text-right">
                                                </div>
                                                <button type="button" wire:click="removeLine({{ $index }})" class="text-red-500 hover:text-red-700 p-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex justify-end mt-4 gap-8 font-bold text-sm">
                                        <div class="text-gray-600 dark:text-gray-400">Total Debit: <span class="{{ $this->totalDebit != $this->totalCredit ? 'text-red-500' : 'text-green-600' }}">{{ number_format($this->totalDebit, 2) }}</span></div>
                                        <div class="text-gray-600 dark:text-gray-400">Total Credit: <span class="{{ $this->totalDebit != $this->totalCredit ? 'text-red-500' : 'text-green-600' }}">{{ number_format($this->totalCredit, 2) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="save" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Memo
                        </button>
                        <button type="button" wire:click="$set('showModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
