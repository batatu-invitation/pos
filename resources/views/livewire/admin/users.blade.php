<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] #[Title(__('User Management'))] class extends Component {
    use WithPagination;

    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $role = 'Cashier';
    public $status = 'Active';
    public $password = '';
    public $editingUserId = null;
    public $search = '';
    public $roleFilter = '';

    public function with()
    {
        return [
            'users' => User::query()
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->when($this->roleFilter && $this->roleFilter !== 'All Roles', fn($q) => $q->role($this->roleFilter))
                ->latest()
                ->paginate(10),
            'roles' => Role::all(),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['first_name', 'last_name', 'email', 'phone', 'role', 'status', 'password', 'editingUserId']);
        $this->dispatch('open-modal', 'user-modal');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->role = $user->roles->first()?->name ?? $user->role;
        $this->status = $user->status;
        $this->password = ''; // Don't show password

        $this->dispatch('open-modal', 'user-modal');
    }

    public function save()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingUserId)],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string',
            'status' => 'required|string',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $this->validate($rules);

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update($data);
            $user->syncRoles($this->role);
            $message = __('User updated successfully!');
        } else {
            $user = User::create($data);
            $user->assignRole($this->role);
            $message = __('User created successfully!');
        }

        $this->dispatch('close-modal', 'user-modal');
        $this->reset(['first_name', 'last_name', 'email', 'phone', 'role', 'status', 'password', 'editingUserId']);
        $this->dispatch('notify', $message);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        $this->dispatch('notify', __('User deleted successfully!'));
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('User Management') }}</h2>
        <button wire:click="create"
            class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
            <i class="fas fa-plus mr-2"></i> {{ __('Add New User') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-64"
                    placeholder="{{ __('Search users...') }}">
            </div>
            <div class="flex gap-2">
                <select
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                    <option>{{ __('All Roles') }}</option>
                    @foreach ($roles as $roleItem)
                        <option value="{{ $roleItem->name }}">{{ $roleItem->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 font-semibold text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">{{ __('Name') }}</th>
                        <th class="px-6 py-3">{{ __('Role') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3">{{ __('Joined') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-full mr-3 border border-gray-200"
                                        src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random"
                                        alt="{{ $user->name }}">
                                    <div>
                                        <p>{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $colors = [
                                        'Super Admin' => 'purple',
                                        'Store Manager' => 'blue',
                                        'Cashier' => 'green',
                                        'Inventory Manager' => 'yellow',
                                        'Accountant' => 'indigo',
                                        'HR' => 'pink',
                                        'Security' => 'slate',
                                    ];
                                    $color = $colors[$user->role] ?? 'gray';
                                @endphp
                                <span
                                    class="px-2 py-1 bg-{{ $color }}-100 text-{{ $color }}-700 rounded-full text-xs font-semibold">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 bg-{{ $user->status === 'Active' ? 'green' : 'gray' }}-100 text-{{ $user->status === 'Active' ? 'green' : 'gray' }}-700 rounded-full text-xs font-semibold">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $user->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="edit({{ $user->id }})"
                                    class="text-gray-400 hover:text-indigo-600 transition-colors"><i
                                        class="fas fa-edit"></i></button>
                                <button type="button"
                                    x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete User?') }}',
                                text: '{{ __('Are you sure you want to delete this user?') }}',
                                icon: 'warning',
                                method: 'delete',
                                params: [{{ $user->id }}],
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
                                {{ __('No users found. Click "Add New User" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $users->links() }}
        </div>
    </div>

    <!-- User Modal -->
    <x-modal name="user-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingUserId ? __('Update User?') : __('Create User?') }}',
            text: '{{ $editingUserId ? __('Are you sure you want to update this user?') : __('Are you sure you want to create this new user?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingUserId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingUserId ? __('Edit User') : __('Create New User') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <x-input-label for="first_name" value="{{ __('First Name') }}" />
                    <x-text-input wire:model="first_name" id="first_name" class="block mt-1 w-full" type="text"
                        placeholder="John" />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                </div>

                <!-- Last Name -->
                <div>
                    <x-input-label for="last_name" value="{{ __('Last Name') }}" />
                    <x-text-input wire:model="last_name" id="last_name" class="block mt-1 w-full" type="text"
                        placeholder="Doe" />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="email" value="{{ __('Email Address') }}" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email"
                        placeholder="john@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <x-input-label for="phone" value="{{ __('Phone Number') }}" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text"
                        placeholder="+1 234 567 890" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Role -->
                <div>
                    <x-input-label for="role" value="{{ __('Role') }}" />
                    <select wire:model="role" id="role"
                        class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        @foreach ($roles as $roleItem)
                            <option value="{{ $roleItem->name }}">{{ $roleItem->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="status" value="{{ __('Status') }}" />
                    <select wire:model="status" id="status"
                        class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Active">{{ __('Active') }}</option>
                        <option value="Inactive">{{ __('Inactive') }}</option>
                        <option value="Suspended">{{ __('Suspended') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="col-span-1 md:col-span-2">
                    <x-input-label for="password"
                        value="{{ $editingUserId ? __('New Password (Optional)') : __('Password') }}" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password"
                        placeholder="********" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" x-on:click="$dispatch('close-modal', 'user-modal')"
                    class="mr-3 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    {{ $editingUserId ? __('Update User') : __('Create User') }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
