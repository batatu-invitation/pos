<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Supplier;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] #[Title('Supplier Management')] class extends Component {
    use WithPagination;

    public $name = '';
    public $contact_person = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $status = 'Active';
    public $editingId = null;
    public $search = '';

    public function with()
    {
        return [
            'suppliers' => Supplier::query()
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_person', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->latest()
                ->paginate(10),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['name', 'contact_person', 'email', 'phone', 'address', 'status', 'editingId']);
        $this->dispatch('open-modal', 'supplier-modal');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->status = $supplier->status;

        $this->dispatch('open-modal', 'supplier-modal');
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('suppliers')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|string',
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $supplier->update($data);
            $message = __('Supplier updated successfully!');
        } else {
            Supplier::create($data);
            $message = __('Supplier created successfully!');
        }

        $this->dispatch('close-modal', 'supplier-modal');
        $this->reset(['name', 'contact_person', 'email', 'phone', 'address', 'status', 'editingId']);
        $this->dispatch('notify', $message);
    }

    public function delete($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        $this->dispatch('notify', __('Supplier deleted successfully!'));
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Supplier Management') }}</h2>
        <button wire:click="create"
            class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
            <i class="fas fa-plus mr-2"></i> {{ __('Add New Supplier') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-64"
                    placeholder="{{ __('Search suppliers...') }}">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 font-semibold text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">{{ __('Name') }}</th>
                        <th class="px-6 py-3">{{ __('Contact Person') }}</th>
                        <th class="px-6 py-3">{{ __('Contact Info') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $supplier->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $supplier->contact_person ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500"><i class="fas fa-envelope mr-1"></i> {{ $supplier->email ?? '-' }}</span>
                                    <span class="text-xs text-gray-500"><i class="fas fa-phone mr-1"></i> {{ $supplier->phone ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 bg-{{ $supplier->status === 'Active' ? 'green' : 'gray' }}-100 text-{{ $supplier->status === 'Active' ? 'green' : 'gray' }}-700 rounded-full text-xs font-semibold">
                                    {{ $supplier->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="edit('{{ $supplier->id }}')"
                                    class="text-gray-400 hover:text-indigo-600 transition-colors"><i
                                        class="fas fa-edit"></i></button>
                                <button type="button"
                                    x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Supplier?') }}',
                                text: '{{ __('Are you sure you want to delete this supplier?') }}',
                                icon: 'warning',
                                method: 'delete',
                                params: ['{{ $supplier->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })"
                                    class="text-gray-400 hover:text-red-600 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                {{ __('No suppliers found. Click "Add New Supplier" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $suppliers->links() }}
        </div>
    </div>

    <!-- Supplier Modal -->
    <x-modal name="supplier-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingId ? __('Update Supplier?') : __('Create Supplier?') }}',
            text: '{{ $editingId ? __('Are you sure you want to update this supplier?') : __('Are you sure you want to create this new supplier?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingId ? __('Edit Supplier') : __('Create New Supplier') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="name" value="{{ __('Company Name') }}" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="PT. Example" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Contact Person -->
                <div>
                    <x-input-label for="contact_person" value="{{ __('Contact Person') }}" />
                    <x-text-input wire:model="contact_person" id="contact_person" class="block mt-1 w-full" type="text"
                        placeholder="John Doe" />
                    <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <x-input-label for="phone" value="{{ __('Phone Number') }}" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text"
                        placeholder="+62 ..." />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Email -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="email" value="{{ __('Email Address') }}" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email"
                        placeholder="email@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Address -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="address" value="{{ __('Address') }}" />
                    <textarea wire:model="address" id="address" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Full address..."></textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <!-- Status -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="status" value="{{ __('Status') }}" />
                    <select wire:model="status" id="status"
                        class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Active">{{ __('Active') }}</option>
                        <option value="Inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" x-on:click="$dispatch('close-modal', 'supplier-modal')"
                    class="mr-3 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    {{ $editingId ? __('Update Supplier') : __('Create Supplier') }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
