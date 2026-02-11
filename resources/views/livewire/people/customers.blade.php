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
        $totalCustomers = Customer::count();
        $newCustomersThisMonth = Customer::where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())->count();

        return [
            'customers' => Customer::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
            'totalCustomers' => $totalCustomers,
            'newCustomersThisMonth' => $newCustomersThisMonth,
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

<div class="p-6 space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Customers') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Manage your customer base and view their details.') }}</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
             <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-lg shadow-green-600/20">
                    <i class="fas fa-file-export mr-2"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl z-50 border border-gray-100 dark:border-gray-700 py-1" style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-green-600">
                        <i class="fas fa-file-excel mr-2 text-green-600 dark:text-green-400"></i> Export to Excel
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-red-600">
                        <i class="fas fa-file-pdf mr-2 text-red-600 dark:text-red-400"></i> Export to PDF
                    </button>
                </div>
            </div>
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Customer') }}
            </button>
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Total Customers -->
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Customers') }}</span>
                    <i class="fas fa-users text-blue-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    {{ number_format($totalCustomers) }}
                </div>
                <div class="text-blue-100 text-sm opacity-90">
                    {{ __('Registered customers') }}
                </div>
            </div>
        </div>

        <!-- New Customers This Month -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('New This Month') }}</span>
                    <i class="fas fa-user-plus text-emerald-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    {{ number_format($newCustomersThisMonth) }}
                </div>
                <div class="text-emerald-100 text-sm opacity-90">
                    {{ __('Added this month') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Section -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-md">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input wire:model.live="search" type="text" class="w-full py-2.5 pl-10 pr-4 bg-gray-50 dark:bg-gray-700/50 border-0 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20" placeholder="{{ __('Search customers...') }}">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400 tracking-wider">
                    <tr>
                        <th class="px-6 py-4">{{ __('Name') }}</th>
                        <th class="px-6 py-4">{{ __('Email') }}</th>
                        <th class="px-6 py-4">{{ __('Phone') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors group">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white flex items-center">
                            <img src="{{ $customer->avatar }}" class="w-8 h-8 rounded-full mr-3 ring-2 ring-white dark:ring-gray-800 shadow-sm" alt="Avatar">
                            {{ $customer->name }}
                        </td>
                        <td class="px-6 py-4">{{ $customer->email ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $customer->phone ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="edit('{{ $customer->id }}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                    x-on:click="$dispatch('swal:confirm', {
                                        title: 'Delete Customer?',
                                        text: 'Are you sure you want to delete this customer?',
                                        icon: 'warning',
                                        method: 'delete',
                                        params: ['{{ $customer->id }}'],
                                        componentId: '{{ $this->getId() }}'
                                    })"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg dark:text-red-400 dark:hover:bg-red-900/30 transition-colors" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-users text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>{{ __('No customers found.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
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
            class="bg-white dark:bg-gray-800 p-6 rounded-3xl">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                {{ $editingCustomerId ? 'Edit Customer' : 'Create New Customer' }}
            </h2>

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" value="Name" class="dark:text-gray-300" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="text" placeholder="e.g. John Doe" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2 dark:text-red-400" />
                </div>

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Email" class="dark:text-gray-300" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="email" placeholder="e.g. john@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 dark:text-red-400" />
                </div>

                <!-- Phone -->
                <div>
                    <x-input-label for="phone" value="Phone" class="dark:text-gray-300" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="text" placeholder="e.g. +1 234 567 890" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2 dark:text-red-400" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="rounded-xl dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="rounded-xl">
                    {{ $editingCustomerId ? 'Update Customer' : 'Create Customer' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
