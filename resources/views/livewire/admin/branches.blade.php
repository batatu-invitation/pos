<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\User;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Branches - Modern POS')]
class extends Component
{
    use WithPagination;

    public $name = '';
    public $code = '';
    public $type = 'Retail Store';
    public $initial = '';
    public $initial_color = 'indigo';
    public $location = '';
    public $manager = '';
    public $phone = '';
    public $email = '';
    public $domain = '';
    public $status = 'Active';

    public $editingBranchId = null;

    // Colors for the initial avatar
    public $colors = ['indigo', 'purple', 'yellow', 'blue', 'red', 'green', 'orange', 'pink', 'teal', 'gray'];

    protected function rules()
    {
        return [
            'name' => 'required|min:3',
            'code' => 'required|unique:tenants,code,' . ($this->editingBranchId ?? 'NULL'),
            'type' => 'required',
            'location' => 'required',
            'manager' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'domain' => 'required|alpha_dash|unique:domains,domain',
            'status' => 'required',
            'initial' => 'required|max:2',
            'initial_color' => 'required',
        ];
    }

    public function with()
    {
        return [
            'branches' => Tenant::with('domains')->paginate(10),
            'managers' => User::role('Manager')->where('status', 'Active')->get(),
        ];
    }

    public function create()
    {
        $this->reset(['name', 'code', 'type', 'initial', 'initial_color', 'location', 'manager', 'phone', 'email', 'status', 'domain', 'editingBranchId']);
        $this->initial_color = $this->colors[array_rand($this->colors)]; // Random color default
        $this->dispatch('open-modal', 'branch-modal');
        $this->dispatch('clear-manager-select');
    }

    public function edit($id)
    {
        $tenant = Tenant::find($id);
        $this->editingBranchId = $tenant->id;
        $this->name = $tenant->name;
        $this->code = $tenant->code;
        $this->type = $tenant->type;
        $this->initial = $tenant->initial;
        $this->initial_color = $tenant->initial_color;
        $this->location = $tenant->location;
        $this->domain = $tenant->domains->first()?->domain;
        $this->manager = $tenant->manager;
        $this->phone = $tenant->phone;
        $this->email = $tenant->email;
        $this->status = $tenant->status;

        $this->dispatch('open-modal', 'branch-modal');
        $this->dispatch('set-manager-select', manager: $this->manager);
    }

    public function save()
    {
        $this->domain = str_replace('.localhost', '', $this->domain);
        $this->validate();

        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'initial' => strtoupper($this->initial),
            'initial_color' => $this->initial_color,
            'location' => $this->location,
            'manager' => $this->manager,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'status_color' => $this->status === 'Active' ? 'green' : 'gray',
        ];

        if ($this->editingBranchId) {
            $tenant = Tenant::find($this->editingBranchId);
            $tenant->update($data);
            Domain::updateOrCreate(['tenant_id' => $tenant->id], ['domain' => $this->domain.'.localhost']);
            $message = __('Branch updated successfully!');
        } else {
            $tenant = Tenant::create($data);
            Domain::updateOrCreate(['tenant_id' => $tenant->id], ['domain' => $this->domain.'.localhost']);
            $message = __('Branch created successfully!');
        }

        $this->dispatch('close-modal', 'branch-modal');
        $this->reset(['name', 'code', 'type', 'initial', 'initial_color', 'location', 'manager', 'phone', 'email', 'status', 'editingBranchId']);
        $this->dispatch('notify', $message);
    }

    public function delete($id)
    {
        $tenant = Tenant::find($id);
        if ($tenant) {
            $tenant->delete();
            $this->dispatch('notify', __('Branch deleted successfully!'));
        }
    }

    // Auto-generate initial from name if empty
    public function updatedName($value)
    {
        if (empty($this->initial) && !empty($value)) {
            $words = explode(' ', $value);
            $initial = '';
            foreach ($words as $word) {
                $initial .= strtoupper(substr($word, 0, 1));
            }
            $this->initial = substr($initial, 0, 2);
        }
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <!-- Load Select2 and jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Branch Management') }}</h2>
        <button wire:click="create" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i> {{ __('Add New Branch') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-semibold">{{ __('Branch Name') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Code') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Domain') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Location') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Manager') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Contact') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-{{ $branch->initial_color }}-100 flex items-center justify-center text-{{ $branch->initial_color }}-600 font-bold mr-3">
                                    {{ $branch->initial }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $branch->name }}</p>
                                    <p class="text-xs text-gray-500">{{ __($branch->type) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-gray-600">{{ $branch->code }}</td>
                        <td class="px-6 py-4 text-gray-600"><a href="http://{{ $branch->domains->first()?->domain }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">{{ $branch->domains->first()?->domain }}</a></td>
                        <td class="px-6 py-4 text-gray-600">
                            <i class="fas fa-map-marker-alt text-red-400 mr-1"></i> {{ $branch->location }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($branch->manager) }}&background=random" class="w-6 h-6 rounded-full mr-2">
                                <span class="text-gray-700">{{ $branch->manager }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <div><i class="fas fa-phone text-xs text-gray-400 mr-1"></i> {{ $branch->phone }}</div>
                            <div><i class="fas fa-envelope text-xs text-gray-400 mr-1"></i> {{ $branch->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-{{ $branch->status_color ?? 'green' }}-100 text-{{ $branch->status_color ?? 'green' }}-700 rounded-full text-xs font-semibold">{{ __($branch->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="edit('{{ $branch->id }}')" class="text-gray-400 hover:text-indigo-600 transition-colors"><i class="fas fa-edit"></i></button>
                            <button type="button" x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Branch?') }}',
                                text: '{{ __('Are you sure you want to delete this branch?') }}',
                                icon: 'warning',
                                method: 'delete',
                                params: ['{{ $branch->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })" class="text-gray-400 hover:text-red-600 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            {{ __('No branches found. Click "Add New Branch" to create one.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $branches->links() }}
        </div>
    </div>

    <!-- Branch Modal -->
    <x-modal name="branch-modal" focusable>
        <form x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingBranchId ? __('Update Branch?') : __('Create Branch?') }}',
            text: '{{ $editingBranchId ? __('Are you sure you want to update this branch?') : __('Are you sure you want to create this new branch?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingBranchId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingBranchId ? __('Edit Branch') : __('Create New Branch') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="name" value="{{ __('Branch Name') }}" />
                    <x-text-input wire:model.live="name" id="name" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. Main Headquarters') }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Code -->
                <div>
                    <x-input-label for="code" value="{{ __('Branch Code') }}" />
                    <x-text-input wire:model="code" id="code" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. BR-001') }}" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <!-- Domain -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="domain" value="{{ __('Domain') }}" />
                    <div class="flex mt-1">
                        <x-text-input wire:model="domain" id="domain" class="block w-full rounded-r-none" type="text" placeholder="{{ __('e.g. branch1') }}" />
                        <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            .localhost
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ __('The unique subdomain for this branch.') }}</p>
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>

                <!-- Type -->
                <div>
                    <x-input-label for="type" value="{{ __('Branch Type') }}" />
                    <select wire:model="type" id="type" class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Retail Store">{{ __('Retail Store') }}</option>
                        <option value="Warehouse">{{ __('Warehouse') }}</option>
                        <option value="Central Office">{{ __('Central Office') }}</option>
                        <option value="Outlet Store">{{ __('Outlet Store') }}</option>
                        <option value="Shopping Mall">{{ __('Shopping Mall') }}</option>
                        <option value="Kiosk">{{ __('Kiosk') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <!-- Manager -->
                <div wire:ignore
                     x-data="{
                        select: null,
                        isProgrammatic: false,
                        init() {
                            this.select = $(this.$refs.select);
                            this.select.select2({
                                placeholder: '{{ __('Select Manager') }}',
                                allowClear: true,
                                width: '100%'
                            });

                            this.select.on('change', () => {
                                if (this.isProgrammatic) return;

                                let val = this.select.val();
                                $wire.set('manager', val);

                                if (val) {
                                    let element = this.select.find(':selected');
                                    let email = element.data('email');
                                    let phone = element.data('phone');
                                    if(email) $wire.set('email', email);
                                    if(phone) $wire.set('phone', phone);
                                }
                            });
                        },
                        setValue(val) {
                            this.isProgrammatic = true;
                            this.select.val(val).trigger('change');
                            this.isProgrammatic = false;
                        }
                     }"
                     x-on:set-manager-select.window="setValue($event.detail.manager)"
                     x-on:clear-manager-select.window="setValue(null)"
                >
                    <x-input-label for="manager" value="{{ __('Manager Name') }}" />
                    <select x-ref="select" id="manager-select" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">{{ __('Select Manager') }}</option>
                        @foreach($managers as $m)
                            <option value="{{ $m->name }}" data-email="{{ $m->email }}" data-phone="{{ $m->phone }}">
                                {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <x-input-error :messages="$errors->get('manager')" class="mt-2" />

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="{{ __('Email Address') }}" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" placeholder="{{ __('e.g. branch@example.com') }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <x-input-label for="phone" value="{{ __('Phone Number') }}" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. +1 234-567-8900') }}" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Location -->
                <div>
                    <x-input-label for="location" value="{{ __('Location') }}" />
                    <x-text-input wire:model="location" id="location" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. New York, NY') }}" />
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="status" value="{{ __('Status') }}" />
                    <select wire:model="status" id="status" class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Active">{{ __('Active') }}</option>
                        <option value="Maintenance">{{ __('Maintenance') }}</option>
                        <option value="Closed">{{ __('Closed') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <!-- Initial & Color -->
                <div class="flex space-x-4">
                    <div class="w-1/3">
                        <x-input-label for="initial" value="{{ __('Initial') }}" />
                        <x-text-input wire:model="initial" id="initial" class="block mt-1 w-full uppercase" type="text" maxlength="2" />
                        <x-input-error :messages="$errors->get('initial')" class="mt-2" />
                    </div>
                    <div class="w-2/3">
                        <x-input-label for="initial_color" value="{{ __('Color Theme') }}" />
                        <select wire:model="initial_color" id="initial_color" class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach($colors as $color)
                                <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('initial_color')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingBranchId ? __('Update Branch') : __('Create Branch') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
