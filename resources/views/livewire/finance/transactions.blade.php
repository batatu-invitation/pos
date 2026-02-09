<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Transaction;
use Livewire\WithPagination;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\TransactionsExport;

new #[Layout('components.layouts.app')] #[Title('Financial Transactions')] class extends Component {
    use WithPagination;

    public $type = 'income';
    public $amount = '';
    public $category = '';
    public $description = '';
    public $date = '';
    public $payment_method = 'Cash';
    public $status = 'completed';
    public $editingId = null;
    public $search = '';
    public $typeFilter = '';
    public $dateFilter = '';

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function with()
    {
        return [
            'transactions' => Transaction::query()
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('description', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%')
                        ->orWhere('reference_number', 'like', '%'.$this->search.'%');
                }))
                ->when($this->typeFilter && $this->typeFilter !== 'All Types', fn($q) => $q->where('type', $this->typeFilter))
                ->latest('date')
                ->latest('created_at')
                ->paginate(10),
            'categories' => $this->getCategories(),
        ];
    }

    public function getCategories()
    {
        // Ideally fetch from DB or config
        if ($this->type === 'income') {
            return ['Sales', 'Service', 'Investment', 'Other'];
        }
        return ['Rent', 'Utilities', 'Inventory', 'Salary', 'Marketing', 'Maintenance', 'Other'];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function exportExcel()
    {
        return Excel::download(new TransactionsExport($this->search, $this->typeFilter), 'transactions.xlsx');
    }

    public function exportPdf()
    {
        $transactions = Transaction::query()
            ->when($this->search, fn($q) => $q->where(function($sub) {
                $sub->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('category', 'like', '%'.$this->search.'%')
                    ->orWhere('reference_number', 'like', '%'.$this->search.'%');
            }))
            ->when($this->typeFilter && $this->typeFilter !== 'All Types', fn($q) => $q->where('type', $this->typeFilter))
            ->latest('date')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.transactions', compact('transactions'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'transactions.pdf');
    }

    public function create()
    {
        $this->reset(['type', 'amount', 'category', 'description', 'date', 'payment_method', 'status', 'editingId']);
        $this->date = Carbon::now()->format('Y-m-d');
        $this->dispatch('open-modal', 'transaction-modal');
    }

    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);
        $this->editingId = $transaction->id;
        $this->type = $transaction->type;
        $this->amount = $transaction->amount;
        $this->category = $transaction->category;
        $this->description = $transaction->description;
        $this->date = $transaction->date->format('Y-m-d');
        $this->payment_method = $transaction->payment_method;
        $this->status = $transaction->status;

        $this->dispatch('open-modal', 'transaction-modal');
    }

    public function save()
    {
        $rules = [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_method' => 'nullable|string',
            'status' => 'required|string',
        ];

        $this->validate($rules);

        $data = [
            'type' => $this->type,
            'amount' => $this->amount,
            'category' => $this->category,
            'description' => $this->description,
            'date' => $this->date,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $transaction = Transaction::findOrFail($this->editingId);
            $transaction->update($data);
            $message = __('Transaction updated successfully!');
        } else {
            Transaction::create($data);
            $message = __('Transaction created successfully!');
        }

        $this->dispatch('close-modal', 'transaction-modal');
        $this->reset(['type', 'amount', 'category', 'description', 'payment_method', 'status', 'editingId']);
        $this->date = Carbon::now()->format('Y-m-d'); // Reset date to today
        $this->dispatch('notify', $message);
    }

    public function delete($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        $this->dispatch('notify', __('Transaction deleted successfully!'));
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Financial Transactions') }}</h2>
        <div class="flex gap-2">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" class="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700 transition-colors shadow-sm">
                    <i class="fas fa-file-export"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-50 border border-gray-100 dark:border-gray-700 py-1" style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i> {{ __('Export Excel') }}
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i> {{ __('Export PDF') }}
                    </button>
                </div>
            </div>
            <button wire:click="create"
                class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Transaction') }}
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-64 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                    placeholder="{{ __('Search transactions...') }}">
            </div>
            <div class="flex gap-2">
                <select wire:model.live="typeFilter"
                    class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                    <option>{{ __('All Types') }}</option>
                    <option value="income">{{ __('Income') }}</option>
                    <option value="expense">{{ __('Expense') }}</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-semibold text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">{{ __('Date') }}</th>
                        <th class="px-6 py-3">{{ __('Type') }}</th>
                        <th class="px-6 py-3">{{ __('Category') }}</th>
                        <th class="px-6 py-3">{{ __('Description') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ $transaction->date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 bg-{{ $transaction->type === 'income' ? 'green' : 'red' }}-100 dark:bg-{{ $transaction->type === 'income' ? 'green' : 'red' }}-900/30 text-{{ $transaction->type === 'income' ? 'green' : 'red' }}-700 dark:text-{{ $transaction->type === 'income' ? 'green' : 'red' }}-400 rounded-full text-xs font-semibold uppercase">
                                    {{ $transaction->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $transaction->category ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $transaction->description ?? '-' }}
                                @if($transaction->payment_method)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 block">{{ $transaction->payment_method }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }} Rp. {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="edit('{{ $transaction->id }}')"
                                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i
                                        class="fas fa-edit"></i></button>
                                <button type="button"
                                    x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Transaction?') }}',
                                text: '{{ __('Are you sure you want to delete this transaction?') }}',
                                icon: 'warning',
                                method: 'delete',
                                params: ['{{ $transaction->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })"
                                    class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No transactions found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Transaction Modal -->
    <x-modal name="transaction-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingId ? __('Update Transaction?') : __('Create Transaction?') }}',
            text: '{{ $editingId ? __('Are you sure you want to update this transaction?') : __('Are you sure you want to create this new transaction?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                {{ $editingId ? __('Edit Transaction') : __('Create New Transaction') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Type -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="type" value="{{ __('Transaction Type') }}" />
                    <div class="mt-2 flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="type" value="income" class="form-radio text-indigo-600 dark:bg-gray-900 dark:border-gray-600">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">{{ __('Income') }}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="type" value="expense" class="form-radio text-red-600 dark:bg-gray-900 dark:border-gray-600">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">{{ __('Expense') }}</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <!-- Date -->
                <div>
                    <x-input-label for="date" value="{{ __('Date') }}" />
                    <x-text-input wire:model="date" id="date" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <!-- Amount -->
                <div>
                    <x-input-label for="amount" value="{{ __('Amount (Rp)') }}" />
                    <x-text-input wire:model="amount" id="amount" class="block mt-1 w-full" type="number" step="0.01"
                        placeholder="0" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>

                <!-- Category -->
                <div>
                    <x-input-label for="category" value="{{ __('Category') }}" />
                    <select wire:model="category" id="category"
                        class="block w-full mt-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm p-2">
                        <option value="">{{ __('Select Category') }}</option>
                        @foreach($this->getCategories() as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <!-- Payment Method -->
                <div>
                    <x-input-label for="payment_method" value="{{ __('Payment Method') }}" />
                    <select wire:model="payment_method" id="payment_method"
                        class="block w-full mt-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm p-2">
                        <option value="Cash">{{ __('Cash') }}</option>
                        <option value="Bank Transfer">{{ __('Bank Transfer') }}</option>
                        <option value="Credit Card">{{ __('Credit Card') }}</option>
                        <option value="QRIS">{{ __('QRIS') }}</option>
                        <option value="Check">{{ __('Check') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                </div>

                <!-- Description -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="description" value="{{ __('Description') }}" />
                    <textarea wire:model="description" id="description" class="block mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="2" placeholder="Details..."></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" x-on:click="$dispatch('close-modal', 'transaction-modal')"
                    class="mr-3 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    {{ $editingId ? __('Update Transaction') : __('Create Transaction') }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
