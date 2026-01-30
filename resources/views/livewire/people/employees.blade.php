<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] #[Title('Employees - Modern POS')] class extends Component {
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $password = '';
    public $phone = '';
    public $role = '';
    public $status = 'active';
    public $editingUserId = null;

    public function with()
    {
        if (auth()->user()->hasRole('Super Admin')) {
            return [
                'employees' => User::with('roles')
                    ->where('id', '!=', auth()->id())
                    ->latest()
                    ->get(),
                'availableRoles' => Role::whereNotIn('name', ['Super Admin', 'Manager', 'Customer Support'])->get(),
            ];
        }
        $availableRoles = Role::whereNotIn('name', ['Super Admin', 'Manager', 'Customer Support'])
            ->withCount('users')
            ->get()
            ->filter(function ($role) {
                return $role->users_count < 3;
            });
        return [
            'employees' => User::where('created_by', auth()->id())
                ->with('roles')
                ->latest()
                ->get(),
            'availableRoles' => $availableRoles,
        ];
    }

    public function createEmployee()
    {
        $this->reset('firstName', 'lastName', 'email', 'password', 'phone', 'role', 'status', 'editingUserId');
        $this->dispatch('open-modal', 'employee-modal');
    }

    public function editEmployee(User $user)
    {
        $this->editingUserId = $user->id;
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->status = $user->status;
        $this->role = $user->roles->first()?->name ?? $user->role;
        $this->password = '';
        $this->dispatch('open-modal', 'employee-modal');
    }

    public function saveEmployee()
    {
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingUserId)],
            'role' => 'required|exists:roles,name',
            'status' => 'required|in:active,inactive',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $this->validate($rules);

        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
        ];

        // Only set created_by on creation to avoid overwriting if transferred (though logic here is strict ownership)
        if (!$this->editingUserId) {
            $data['created_by'] = auth()->id();
        }

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::find($this->editingUserId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        $user->syncRoles([$this->role]);

        $this->dispatch('close-modal', 'employee-modal');
        $this->reset('firstName', 'lastName', 'email', 'password', 'phone', 'role', 'status', 'editingUserId');
        $this->dispatch('notify', __('Employee saved successfully!'));
    }

    public function deleteEmployee($id)
    {
        $user = User::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();
        if ($user) {
            $user->delete();
            $this->dispatch('notify', __('Employee deleted successfully!'));
        }
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ __('Employees') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('Manage your team members and their access.') }}</p>
        </div>
        <button wire:click="createEmployee"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
            <i class="fas fa-plus mr-2"></i> {{ __('Add Employee') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">{{ __('Name') }}</th>
                        <th class="px-6 py-4">{{ __('Role') }}</th>
                        <th class="px-6 py-4">{{ __('Email') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 flex items-center">
                                @if ($employee->avatar)
                                    <img src="{{ $employee->avatar }}" class="w-8 h-8 rounded-full mr-3 object-cover"
                                        alt="Avatar">
                                @else
                                    <div
                                        class="w-8 h-8 rounded-full mr-3 bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-800">{{ $employee->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $employee->phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @foreach ($employee->roles as $role)
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4">{{ $employee->email }}</td>
                            <td class="px-6 py-4">
                                @if ($employee->status === 'active' || $employee->status === 'Active')
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editEmployee('{{ $employee->id }}')"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                    x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Employee?') }}',
                                text: '{{ __('Are you sure you want to delete this employee? This action cannot be undone.') }}',
                                icon: 'warning',
                                method: 'deleteEmployee',
                                params: ['{{ $employee->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })"
                                    class="text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                    <p>{{ __('No employees found.') }}</p>
                                    <p class="text-xs mt-1">{{ __('Start by adding a new employee to your team.') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employee Modal -->
    <x-modal name="employee-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingUserId ? __('Update Employee?') : __('Create Employee?') }}',
            text: '{{ $editingUserId ? __('Are you sure you want to update this employee?') : __('Are you sure you want to create this new employee?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingUserId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'saveEmployee',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ $editingUserId ? __('Edit Employee') : __('Create New Employee') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name -->
                <div>
                    <x-input-label for="firstName" value="{{ __('First Name') }}" />
                    <x-text-input wire:model="firstName" id="firstName" class="block mt-1 w-full" type="text"
                        required />
                    <x-input-error :messages="$errors->get('firstName')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="lastName" value="{{ __('Last Name') }}" />
                    <x-text-input wire:model="lastName" id="lastName" class="block mt-1 w-full" type="text"
                        required />
                    <x-input-error :messages="$errors->get('lastName')" class="mt-2" />
                </div>

                <!-- Contact -->
                <div class="md:col-span-2">
                    <x-input-label for="email" value="{{ __('Email Address') }}" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="phone" value="{{ __('Phone Number') }}" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Role & Status -->
                <div>
                    <x-input-label for="role" value="{{ __('Role') }}" />
                    <select wire:model="role" id="role"
                        class="border border-gray-300 focus:border-indigo-500 p-4 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">{{ __('Select Role') }}</option>
                        @foreach ($availableRoles as $roleOption)
                            <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" value="{{ __('Status') }}" />
                    <select wire:model="status" id="status"
                        class="border border-gray-300 p-4 focus:border-indigo-500 p-4 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="md:col-span-2">
                    <x-input-label for="password"
                        value="{{ $editingUserId ? __('Password (leave blank to keep current)') : __('Password') }}" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingUserId ? __('Update Employee') : __('Create Employee') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
