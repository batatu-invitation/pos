<?php

use Livewire\Volt\Component;
use App\Models\Account;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Chart of Accounts')] class extends Component {
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    
    // Modal state
    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form fields
    public $code = '';
    public $name = '';
    public $type = 'asset';
    public $subtype = '';
    public $description = '';
    public $is_active = true;

    public function rules()
    {
        return [
            'code' => 'required|string|unique:accounts,code,' . $this->editingId,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function with()
    {
        return [
            'accounts' => Account::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
                ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
                ->orderBy('code')
                ->paginate(15),
        ];
    }

    public function create()
    {
        $this->reset(['code', 'name', 'type', 'subtype', 'description', 'is_active', 'editingId']);
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(Account $account)
    {
        $this->editingId = $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->subtype = $account->subtype;
        $this->description = $account->description;
        $this->is_active = $account->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing) {
            $account = Account::find($this->editingId);
            $account->update([
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'subtype' => $this->subtype,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Account updated successfully.');
        } else {
            Account::create([
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'subtype' => $this->subtype,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Account created successfully.');
        }

        $this->showModal = false;
        $this->reset(['code', 'name', 'type', 'subtype', 'description', 'is_active', 'editingId']);
    }

    public function delete($id)
    {
        try {
            Account::find($id)->delete();
            session()->flash('message', 'Account deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete account because it has related transactions.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Chart of Accounts</h1>
            <p class="text-gray-500 dark:text-gray-400">Manage your financial accounts structure</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> New Account
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 flex gap-4">
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="fas fa-search text-gray-400"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search accounts..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
        </div>
        <select wire:model.live="typeFilter" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="">All Types</option>
            <option value="asset">Asset</option>
            <option value="liability">Liability</option>
            <option value="equity">Equity</option>
            <option value="revenue">Revenue</option>
            <option value="expense">Expense</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Code</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Type</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Subtype</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $account->code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $account->name }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($account->type === 'asset') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($account->type === 'liability') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($account->type === 'equity') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif($account->type === 'revenue') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 @endif">
                                {{ ucfirst($account->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $account->subtype }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $account->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="edit('{{ $account->id }}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button wire:confirm="Are you sure you want to delete this account?" wire:click="delete('{{ $account->id }}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-book text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>No accounts found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $accounts->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-gray-800">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-gray-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ $isEditing ? 'Edit Account' : 'New Account' }}
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                                            <input type="text" wire:model="code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                            @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                            <select wire:model="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                                <option value="asset">Asset</option>
                                                <option value="liability">Liability</option>
                                                <option value="equity">Equity</option>
                                                <option value="revenue">Revenue</option>
                                                <option value="expense">Expense</option>
                                            </select>
                                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                        <input type="text" wire:model="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtype (Optional)</label>
                                        <input type="text" wire:model="subtype" placeholder="e.g. Current Asset" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                        <textarea wire:model="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"></textarea>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700/50">
                        <button type="button" wire:click="save" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
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
