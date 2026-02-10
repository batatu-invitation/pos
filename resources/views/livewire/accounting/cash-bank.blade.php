<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] #[Title('Cash & Bank Records')] class extends Component {
    use WithPagination;

    public $search = '';
    public $typeFilter = 'all'; // all, cash_in, cash_out, bank_in, bank_out
    
    // Filters
    public $accountId = '';
    public $startDate;
    public $endDate;

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
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->date = Carbon::now()->format('Y-m-d');
        
        // Default to first Cash/Bank account if not set
        if (empty($this->accountId)) {
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
    }

    public function with()
    {
        // 1. Get Accounts for Dropdown
        $accounts = Account::where('type', 'asset')
            ->orderBy('code')
            ->get();
            
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

        // 2. Prepare Data
        $entries = collect();
        $totalIn = 0;
        $totalOut = 0;
        $netChange = 0;

        if ($this->accountId) {
             // Summary Calculation
            $summaryQuery = JournalEntryItem::where('account_id', $this->accountId)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                });
                
            $totalIn = (clone $summaryQuery)->sum('debit');
            $totalOut = (clone $summaryQuery)->sum('credit');
            $netChange = $totalIn - $totalOut;

            // List Transactions
            $query = JournalEntryItem::select('journal_entry_items.*')
                ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                ->with(['journalEntry'])
                ->where('journal_entry_items.account_id', $this->accountId)
                ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
                ->where('journal_entries.status', 'posted');

            if ($this->search) {
                $query->whereHas('journalEntry', function($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                      ->orWhere('reference', 'like', "%{$this->search}%");
                });
            }

            if ($this->typeFilter !== 'all') {
                // Mapping typeFilter to journal entry types if needed, 
                // OR just filtering by debit/credit if the user wants "In" or "Out" generic
                // But the original code had 'cash_in', 'cash_out' etc.
                // Let's support both.
                
                if (in_array($this->typeFilter, ['in', 'out'])) {
                    if ($this->typeFilter === 'in') {
                        $query->where('journal_entry_items.debit', '>', 0);
                    } else {
                        $query->where('journal_entry_items.credit', '>', 0);
                    }
                } else {
                     $query->where('journal_entries.type', $this->typeFilter);
                }
            }

            $entries = $query->orderBy('journal_entries.date', 'desc')
                ->orderBy('journal_entries.created_at', 'desc')
                ->paginate(15);
        } else {
            $entries = JournalEntryItem::where('id', null)->paginate(15);
        }

        return [
            'entries' => $entries,
            'accounts' => $accounts,
            'cashBankAccounts' => $cashBankAccounts,
            'allAccounts' => $allAccounts,
            'summary' => [
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => $netChange
            ],
            'selectedAccount' => $this->accountId ? Account::find($this->accountId) : null,
        ];
    }

    public function create()
    {
        $this->reset(['transactionType', 'reference', 'description', 'mainAccountId', 'contraAccountId', 'amount']);
        $this->date = Carbon::now()->format('Y-m-d');
        $this->transactionType = 'cash_in';
        $this->mainAccountId = $this->accountId; // Default to currently selected view
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
                'status' => 'posted',
                'user_id' => auth()->id(),
            ]);

            $isReceipt = in_array($this->transactionType, ['cash_in', 'bank_in']);

            if ($isReceipt) {
                // Debit Main (Asset), Credit Contra
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->mainAccountId,
                    'debit' => $this->amount,
                    'credit' => 0,
                ]);
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->contraAccountId,
                    'debit' => 0,
                    'credit' => $this->amount,
                ]);
            } else {
                // Debit Contra (Expense), Credit Main
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->contraAccountId,
                    'debit' => $this->amount,
                    'credit' => 0,
                ]);
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->mainAccountId,
                    'debit' => 0,
                    'credit' => $this->amount,
                ]);
            }
        });

        $this->showModal = false;
        $this->dispatch('notify', title: 'Success', text: 'Transaction recorded successfully.', icon: 'success');
    }
}; ?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Cash & Bank Records</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage daily cash and bank transactions.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
             <!-- Account Selector -->
            <div class="relative w-full sm:w-64">
                <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                <select wire:model.live="accountId" 
                    class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                    <option value="">{{ __('Select Account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>

            <button wire:click="create" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2 shadow-sm font-medium">
                <i class="fas fa-plus"></i> 
                <span>New Transaction</span>
            </button>
        </div>
    </div>

    @if($selectedAccount)
        <!-- Bento Grid Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

            <!-- Total Out -->
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
                    <div class="flex flex-col sm:flex-row gap-4 w-full">
                         <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search transactions..." 
                                class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:text-white">
                        </div>

                         <!-- Date Range -->
                        <div class="flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl p-1">
                            <input wire:model.live="startDate" type="date" 
                                class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 p-1.5 cursor-pointer">
                            <span class="text-gray-400">-</span>
                            <input wire:model.live="endDate" type="date" 
                                class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 p-1.5 cursor-pointer">
                        </div>

                        <!-- Type Filter -->
                        <div class="relative w-full sm:w-40">
                            <select wire:model.live="typeFilter" 
                                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block pl-3 pr-8 py-2 w-full cursor-pointer appearance-none">
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
                            @forelse($entries as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $item->journalEntry->date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $item->journalEntry->reference }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->journalEntry->description }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1 w-fit
                                                @if(in_array($item->journalEntry->type, ['cash_in', 'bank_in'])) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                                {{ ucwords(str_replace('_', ' ', $item->journalEntry->type)) }}
                                            </span>
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
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                    {{ $entries->links() }}
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

    <!-- Create Modal -->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-gray-700 transform transition-all scale-100">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">New Transaction</h2>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type</label>
                    <select wire:model.live="transactionType" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                        <option value="cash_in">Cash In (Receipt)</option>
                        <option value="cash_out">Cash Out (Payment)</option>
                        <option value="bank_in">Bank In (Receipt)</option>
                        <option value="bank_out">Bank Out (Payment)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Date</label>
                    <input type="date" wire:model="date" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                    @error('date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        @if(in_array($transactionType, ['cash_in', 'cash_out'])) Cash Account @else Bank Account @endif
                    </label>
                    <select wire:model="mainAccountId" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                        <option value="">Select Account</option>
                        @foreach($cashBankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('mainAccountId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        @if(in_array($transactionType, ['cash_in', 'bank_in'])) From Account (Revenue/Payer) @else To Account (Expense/Payee) @endif
                    </label>
                    <select wire:model="contraAccountId" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                        <option value="">Select Account</option>
                        @foreach($allAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }} ({{ $account->type }})</option>
                        @endforeach
                    </select>
                    @error('contraAccountId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">Rp</span>
                        <input type="number" step="0.01" wire:model="amount" class="w-full pl-10 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                    </div>
                    @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reference</label>
                    <input type="text" wire:model="reference" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea wire:model="description" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white shadow-sm" rows="3"></textarea>
                    @error('description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="$set('showModal', false)" class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="save" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-medium shadow-sm transition-colors">
                    Save Transaction
                </button>
            </div>
        </div>
    </div>
    @endif
</div>