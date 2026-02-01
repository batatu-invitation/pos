<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Customer;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new #[Layout('components.layouts.app')]
#[Title('Customers - Modern POS')]
class extends Component
{
    use WithPagination;

    public $name = '';
    public $email = '';
    public $phone = '';
    public $editingCustomerId = null;
    public $search = '';

    protected function rules()
    {
        return [
            'name' => 'required|min:2',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ];
    }

    public function with()
    {
        return [
            'customers' => Customer::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
        ];
    }

    public function create()
    {
        $this->reset(['name', 'email', 'phone', 'editingCustomerId']);
        $this->dispatch('open-modal', 'customer-modal');
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;

        $this->dispatch('open-modal', 'customer-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_id' => auth()->id(),
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random',
        ];

        if ($this->editingCustomerId) {
            Customer::findOrFail($this->editingCustomerId)->update($data);
            $message = 'Customer updated successfully!';
        } else {
            Customer::create($data);
            $message = 'Customer created successfully!';
        }

        $this->dispatch('close-modal', 'customer-modal');
        $this->reset(['name', 'email', 'phone', 'editingCustomerId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Customer::findOrFail($id)->delete();
        $this->dispatch('notify', 'Customer deleted successfully!');
    }

    public function exportExcel()
    {
        return Excel::download(new CustomersExport($this->search), 'customers.xlsx');
    }

    public function exportPdf()
    {
        $customers = Customer::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->orWhere('phone', 'like', '%' . $this->search . '%'))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('pdf.customers', ['customers' => $customers]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'customers.pdf');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Customers</h2>
        <div class="flex space-x-3">
             <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-file-export mr-2"></i> Export
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50 border border-gray-100" style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-green-600">
                        <i class="fas fa-file-excel mr-2"></i> Export to Excel
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-red-600">
                        <i class="fas fa-file-pdf mr-2"></i> Export to PDF
                    </button>
                </div>
            </div>
            <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Add Customer
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <div class="relative max-w-sm w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-search text-gray-400"></i>
                </span>
                <input wire:model.live="search" type="text" class="w-full py-2 pl-10 pr-4 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500" placeholder="Search customers...">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 flex items-center">
                            <img src="{{ $customer->avatar }}" class="w-8 h-8 rounded-full mr-3" alt="Avatar">
                            {{ $customer->name }}
                        </td>
                        <td class="px-6 py-4">{{ $customer->email ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $customer->phone ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit('{{ $customer->id }}')" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                            <button type="button"
                                x-on:click="$dispatch('swal:confirm', {
                                    title: 'Delete Customer?',
                                    text: 'Are you sure you want to delete this customer?',
                                    icon: 'warning',
                                    method: 'delete',
                                    params: ['{{ $customer->id }}'],
                                    componentId: '{{ $this->getId() }}'
                                })"
                                class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No customers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Customer Modal -->
    <x-modal name="customer-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
                title: '{{ $editingCustomerId ? 'Update Customer?' : 'Create Customer?' }}',
                text: '{{ $editingCustomerId ? 'Are you sure you want to update this customer?' : 'Are you sure you want to create this new customer?' }}',
                icon: 'question',
                confirmButtonText: '{{ $editingCustomerId ? 'Yes, update it!' : 'Yes, create it!' }}',
                method: 'save',
                params: [],
                componentId: '{{ $this->getId() }}'
            })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingCustomerId ? 'Edit Customer' : 'Create New Customer' }}
            </h2>

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" placeholder="e.g. John Doe" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" placeholder="e.g. john@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" placeholder="e.g. +1 234 567 890" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingCustomerId ? 'Update Customer' : 'Create Customer' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
