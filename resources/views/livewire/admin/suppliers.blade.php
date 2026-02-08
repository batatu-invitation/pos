<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SuppliersExport;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 'Active')->count();

        return [
            'suppliers' => Supplier::query()
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_person', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->latest()
                ->paginate(9),
            'totalSuppliers' => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
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

    public function exportExcel()
    {
        return Excel::download(new SuppliersExport, 'suppliers.xlsx');
    }

    public function exportPdf()
    {
        $suppliers = Supplier::latest()->get();
        $pdf = Pdf::loadView('pdf.suppliers', compact('suppliers'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'suppliers.pdf');
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">{{ __('Suppliers') }}</h2>
            <p class="text-gray-500 mt-2 text-sm">{{ __('Manage your product suppliers and contact information.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-file-export mr-2 text-gray-400"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs text-gray-400"></i>
                </button>
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-50 py-2 border border-gray-100" 
                     style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-file-excel mr-2 text-green-500"></i> {{ __('Export Excel') }}
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-file-pdf mr-2 text-red-500"></i> {{ __('Export PDF') }}
                    </button>
                </div>
            </div>
            <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Supplier') }}
            </button>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow duration-300">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('Total Suppliers') }}</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $totalSuppliers }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i class="fas fa-truck text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow duration-300">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('Active Suppliers') }}</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $activeSuppliers }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-600">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 sm:text-sm" placeholder="{{ __('Search suppliers...') }}">
        </div>
    </div>

    <!-- Suppliers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($suppliers as $supplier)
            <div class="group bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex flex-col hover:shadow-lg transition-all duration-300 relative">
                
                <!-- Status & Actions -->
                <div class="flex justify-between items-start mb-6">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $supplier->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} border {{ $supplier->status === 'Active' ? 'border-green-200' : 'border-gray-200' }}">
                        {{ $supplier->status }}
                    </span>
                    
                    <div class="flex space-x-2">
                        <button wire:click="edit('{{ $supplier->id }}')" class="p-2 bg-gray-50 text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors" title="{{ __('Edit') }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" x-on:click="$dispatch('swal:confirm', {
                                    title: '{{ __('Delete Supplier?') }}',
                                    text: '{{ __('Are you sure you want to delete this supplier?') }}',
                                    icon: 'warning',
                                    method: 'delete',
                                    params: ['{{ $supplier->id }}'],
                                    componentId: '{{ $this->getId() }}'
                                })" class="p-2 bg-gray-50 text-red-500 rounded-lg hover:bg-red-50 transition-colors" title="{{ __('Delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Main Info -->
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-2xl mr-4 flex-shrink-0">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg line-clamp-1" title="{{ $supplier->name }}">{{ $supplier->name }}</h3>
                        <div class="flex items-center text-gray-500 text-sm mt-1">
                            <i class="fas fa-user mr-2 text-gray-400"></i>
                            <span class="line-clamp-1">{{ $supplier->contact_person ?? __('No Contact Person') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="space-y-3 mb-4 flex-grow">
                    <div class="flex items-start">
                        <div class="w-8 flex-shrink-0 text-center text-gray-400"><i class="fas fa-envelope"></i></div>
                        <span class="text-sm text-gray-600 break-all">{{ $supplier->email ?? '-' }}</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-8 flex-shrink-0 text-center text-gray-400"><i class="fas fa-phone"></i></div>
                        <span class="text-sm text-gray-600">{{ $supplier->phone ?? '-' }}</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-8 flex-shrink-0 text-center text-gray-400"><i class="fas fa-map-marker-alt"></i></div>
                        <span class="text-sm text-gray-600 line-clamp-2">{{ $supplier->address ?? '-' }}</span>
                    </div>
                </div>

                <!-- Bottom Decorative Line -->
                <div class="w-full h-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full mt-4 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i class="fas fa-truck text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('No suppliers found') }}</h3>
                    <p class="text-gray-500 mb-6">{{ __('Get started by creating a new supplier.') }}</p>
                    <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i> {{ __('Add Supplier') }}
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $suppliers->links() }}
    </div>

    <!-- Supplier Modal -->
    <x-modal name="supplier-modal" focusable>
        <div class="bg-white rounded-3xl overflow-hidden">
             <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 class="text-xl font-bold text-gray-800">
                    {{ $editingId ? __('Edit Supplier') : __('Create New Supplier') }}
                </h2>
                <button x-on:click="$dispatch('close-modal', 'supplier-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form x-on:submit.prevent="$dispatch('swal:confirm', {
                title: '{{ $editingId ? __('Update Supplier?') : __('Create Supplier?') }}',
                text: '{{ $editingId ? __('Are you sure you want to update this supplier?') : __('Are you sure you want to create this new supplier?') }}',
                icon: 'question',
                confirmButtonText: '{{ $editingId ? __('Yes, update it!') : __('Yes, create it!') }}',
                method: 'save',
                params: [],
                componentId: '{{ $this->getId() }}'
            })" class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="col-span-1 md:col-span-2">
                        <x-input-label for="name" :value="__('Company Name')" class="text-gray-700 font-medium mb-1" />
                        <x-text-input wire:model="name" id="name" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                            placeholder="PT. Example" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Contact Person -->
                    <div>
                        <x-input-label for="contact_person" :value="__('Contact Person')" class="text-gray-700 font-medium mb-1" />
                        <x-text-input wire:model="contact_person" id="contact_person" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                            placeholder="John Doe" />
                        <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                    </div>

                    <!-- Phone -->
                    <div>
                        <x-input-label for="phone" :value="__('Phone Number')" class="text-gray-700 font-medium mb-1" />
                        <x-text-input wire:model="phone" id="phone" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                            placeholder="+62 ..." />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Email -->
                    <div class="col-span-1 md:col-span-2">
                        <x-input-label for="email" :value="__('Email Address')" class="text-gray-700 font-medium mb-1" />
                        <x-text-input wire:model="email" id="email" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="email"
                            placeholder="email@example.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Address -->
                    <div class="col-span-1 md:col-span-2">
                        <x-input-label for="address" :value="__('Address')" class="text-gray-700 font-medium mb-1" />
                        <textarea wire:model="address" id="address" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 shadow-sm" rows="3" placeholder="Full address..."></textarea>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <!-- Status -->
                    <div class="col-span-1 md:col-span-2">
                        <x-input-label for="status" :value="__('Status')" class="text-gray-700 font-medium mb-1" />
                        <div class="relative">
                            <select wire:model="status" id="status"
                                class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700">
                                <option value="Active">{{ __('Active') }}</option>
                                <option value="Inactive">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'supplier-modal')"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 font-medium text-sm">
                        {{ $editingId ? __('Update Supplier') : __('Create Supplier') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>