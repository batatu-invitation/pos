<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Tax;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new #[Layout('components.layouts.app')]
#[Title('Tax Settings - Modern POS')]
class extends Component
{
    use WithPagination;

    public $name = '';
    public $rate = '';
    public $is_active = false;
    public $editingTaxId = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean'
        ];
    }

    public function with()
    {
        return [
            'taxes' => Tax::orderBy('created_at', 'desc')->paginate(10)
        ];
    }

    public function create()
    {
        $this->reset(['name', 'rate', 'is_active', 'editingTaxId']);
        $this->is_active = false;
        $this->dispatch('open-modal', 'tax-modal');
    }

    public function edit($id)
    {
        $tax = Tax::findOrFail($id);
        $this->editingTaxId = $tax->id;
        $this->name = $tax->name;
        $this->rate = $tax->rate;
        $this->is_active = (bool) $tax->is_active;
        $this->dispatch('open-modal', 'tax-modal');
    }

    public function save()
    {
        $this->validate();

        // Handle Active Tax Logic
        if ($this->is_active) {
            $query = Tax::where('is_active', true);
            if ($this->editingTaxId) {
                $query->where('id', '!=', $this->editingTaxId);
            }
            $query->update(['is_active' => false]);
        }

        // Handle user_id (auth or first user fallback)
        $userId = auth()->id();
        if (!$userId) {
            $user = \App\Models\User::first();
            $userId = $user ? $user->id : null;
        }

        $data = [
            'name' => $this->name,
            'rate' => $this->rate,
            'is_active' => $this->is_active,
            'user_id' => $userId,
        ];

        if ($this->editingTaxId) {
            Tax::findOrFail($this->editingTaxId)->update($data);
            $message = 'Tax updated successfully!';
        } else {
            Tax::create($data);
            $message = 'Tax created successfully!';
        }

        $this->dispatch('close-modal', 'tax-modal');
        $this->reset(['name', 'rate', 'is_active', 'editingTaxId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Tax::findOrFail($id)->delete();
        $this->dispatch('notify', 'Tax deleted successfully.');
    }

    public function setStatus($id, $status)
    {
        $tax = Tax::findOrFail($id);
        $isActive = $status === 'active';

        if ($isActive) {
            Tax::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $tax->update(['is_active' => $isActive]);
        $this->dispatch('notify', 'Tax status updated.');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Tax Settings</h2>
                <p class="mt-1 text-sm text-gray-500">Manage your tax rates and configurations.</p>
            </div>
            <button wire:click="create" class="mt-4 md:mt-0 px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-sm flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Tax Rate
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($taxes as $tax)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 overflow-hidden group">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 rounded-lg {{ $tax->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-400' }}">
                                <i class="fas fa-percent text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $tax->name }}</h3>
                                <p class="text-sm text-gray-500">Created {{ $tax->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="relative">
                            <select
                                x-on:change="
                                    const taxId = '{{ $tax->id }}';
                                    const selectEl = $el;
                                    const newStatus = selectEl.value;

                                    // Reset select to previous value immediately to wait for confirmation
                                    const prevValue = newStatus === 'active' ? 'inactive' : 'active';

                                    Swal.fire({
                                        title: 'Update Status?',
                                        text: `Are you sure you want to change this tax to ${newStatus}?`,
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonColor: '#4f46e5',
                                        cancelButtonColor: '#d33',
                                        confirmButtonText: 'Yes, update it!'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $wire.setStatus(taxId, newStatus);
                                        } else {
                                            selectEl.value = prevValue;
                                        }
                                    })
                                "
                                class="block w-32 pl-3 pr-10 py-1.5 text-xs font-medium border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-full {{ $tax->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}"
                            >
                                <option value="active" {{ $tax->is_active ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ !$tax->is_active ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">{{ number_format($tax->rate, 2) }}</span>
                        <span class="ml-1 text-gray-500 font-medium">%</span>
                    </div>

                    <div class="mt-6 flex items-center justify-end space-x-3 pt-4 border-t border-gray-50">
                        <button wire:click="edit('{{ $tax->id }}')" class="text-gray-400 hover:text-indigo-600 transition-colors p-2 rounded-full hover:bg-indigo-50" title="Edit">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button"
                            x-on:click="$dispatch('swal:confirm', {
                                title: 'Delete Tax?',
                                text: 'Are you sure you want to delete this tax rate?',
                                icon: 'warning',
                                method: 'delete',
                                params: ['{{ $tax->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })"
                            class="text-gray-400 hover:text-red-600 transition-colors p-2 rounded-full hover:bg-red-50" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full">
                <div class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-200">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                        <i class="fas fa-percent text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No tax rates found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new tax rate.</p>
                </div>
            </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $taxes->links() }}
        </div>
    </div>

    <!-- Tax Modal -->
    <x-modal name="tax-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingTaxId ? 'Update Tax?' : 'Create Tax?' }}',
            text: '{{ $editingTaxId ? 'Are you sure you want to update this tax rate?' : 'Are you sure you want to create this new tax rate?' }}',
            icon: 'question',
            confirmButtonText: '{{ $editingTaxId ? 'Yes, update it!' : 'Yes, create it!' }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingTaxId ? 'Edit Tax Rate' : 'Create New Tax Rate' }}
            </h2>

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. VAT, GST" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Rate -->
                <div>
                    <x-input-label for="rate" value="Rate (%)" />
                    <x-text-input wire:model="rate" id="rate" class="block mt-1 w-full" type="number" step="0.01"
                        placeholder="0.00" />
                    <x-input-error :messages="$errors->get('rate')" class="mt-2" />
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="is_active" value="Status" />
                    <select wire:model="is_active" id="is_active"
                        class="block w-full px-4 py-3 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="0">Inactive</option>
                        <option value="1">Active</option>
                    </select>
                    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingTaxId ? 'Update Tax' : 'Create Tax' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- SweetAlert2 Script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (message) => {
                const msg = Array.isArray(message) ? message[0] : message;
                Swal.fire({
                    title: 'Success!',
                    text: msg,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            Livewire.on('swal:confirm', (data) => {
                const options = Array.isArray(data) ? data[0] : data;

                Swal.fire({
                    title: options.title,
                    text: options.text,
                    icon: options.icon,
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: options.confirmButtonText || 'Yes, proceed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (options.componentId) {
                            Livewire.find(options.componentId).call(options.method, ...options.params);
                        } else {
                            Livewire.dispatch(options.method, {
                                id: options.params
                            });
                        }
                    }
                });
            });
        });
    </script>
</div>
