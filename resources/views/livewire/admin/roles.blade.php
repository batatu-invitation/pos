<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new
#[Layout('components.layouts.app', ['header' => 'Roles & Permissions'])]
#[Title('Roles & Permissions - Modern POS')]
class extends Component
{
    public $roleName = '';
    public $permissionName = '';
    public $editingRoleId = null;
    public $editingPermissionId = null;
    public $editingRoleName = '';
    public $selectedPermissions = [];
    public $showRoleModal = false;
    public $showPermissionModal = false;
    public $showConfigureModal = false;

    // Validation rules
    protected function rules()
    {
        return [
            'roleName' => 'required|min:3|unique:roles,name,' . $this->editingRoleId,
            'permissionName' => 'required|min:3|unique:permissions,name,' . $this->editingPermissionId,
        ];
    }

    public function with()
    {
        return [
            'roles' => Role::withCount('users')->with('permissions')->get(),
            'permissions' => Permission::all(),
        ];
    }

    // Role Management
    public function createRole()
    {
        $this->reset('roleName', 'editingRoleId');
        $this->dispatch('open-modal', 'role-modal');
    }

    public function editRole(Role $role)
    {
        $this->roleName = $role->name;
        $this->editingRoleId = $role->id;
        $this->dispatch('open-modal', 'role-modal');
    }

    public function saveRole()
    {
        $this->validate([
            'roleName' => 'required|min:3|unique:roles,name,' . $this->editingRoleId,
        ]);

        if ($this->editingRoleId) {
            $role = Role::find($this->editingRoleId);
            $role->update(['name' => $this->roleName]);
        } else {
            Role::create(['name' => $this->roleName]);
        }

        $this->dispatch('close-modal', 'role-modal');
        $this->reset('roleName', 'editingRoleId');
        $this->dispatch('notify', __('Role saved successfully!'));
    }

    public function deleteRole($id)
    {
        $role = Role::find($id);
        if ($role->name === 'Super Admin') {
            return; // Prevent deleting Super Admin
        }
        $role->delete();
        $this->dispatch('notify', __('Role deleted successfully!'));
    }

    // Permission Management
    public function createPermission()
    {
        $this->reset('permissionName', 'editingPermissionId');
        $this->dispatch('open-modal', 'permission-modal');
    }

    public function editPermission(Permission $permission)
    {
        $this->editingPermissionId = $permission->id;
        $this->permissionName = $permission->name;
        $this->dispatch('open-modal', 'permission-modal');
    }

    public function savePermission()
    {
        $this->validate([
            'permissionName' => 'required|min:3|unique:permissions,name,' . $this->editingPermissionId,
        ]);

        if ($this->editingPermissionId) {
            $permission = Permission::find($this->editingPermissionId);
            $permission->update(['name' => $this->permissionName]);
            $message = __('Permission updated successfully!');
        } else {
            Permission::create(['name' => $this->permissionName]);
            $message = __('Permission created successfully!');
        }

        $this->dispatch('close-modal', 'permission-modal');
        $this->reset('permissionName', 'editingPermissionId');
        $this->dispatch('notify', $message);
    }

    public function deletePermission($id)
    {
        Permission::find($id)->delete();
        $this->dispatch('notify', __('Permission deleted successfully!'));
    }

    // Configure (Assign Permissions)
    public function configureRole(Role $role)
    {
        $this->editingRoleId = $role->id;
        $this->editingRoleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->dispatch('open-modal', 'configure-role-modal');
    }

    public function updateRolePermissions()
    {
        $role = Role::find($this->editingRoleId);
        // Prevent modifying Super Admin permissions if needed, but usually Super Admin has all via Gate
        if ($role->name === 'Super Admin') {
             // Optional: prevent modifying Super Admin
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('close-modal', 'configure-role-modal');
        $this->dispatch('notify', __('Role permissions updated successfully!'));
    }
}
?>

<div class="flex h-screen overflow-hidden">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">


        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

            <div class="flex justify-between items-center mb-6">
                 <div>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Manage Access') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Control user access and permissions across the system.') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="createPermission" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 shadow-sm transition-colors">
                        <i class="fas fa-key mr-2"></i> {{ __('Create Permission') }}
                    </button>
                    <button wire:click="createRole" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                        <i class="fas fa-plus mr-2"></i> {{ __('Create New Role') }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($roles as $role)
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow relative group">
                         @if($role->name !== 'Super Admin')
                            <button type="button" x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Role?') }}',
                                text: '{{ __('Are you sure you want to delete this role? This action cannot be undone.') }}',
                                icon: 'warning',
                                method: 'deleteRole',
                                params: [{{ $role->id }}],
                                componentId: '{{ $this->getId() }}'
                            })" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif

                        <div class="flex justify-between items-start mb-4">
                            <h3 class="font-bold text-lg">{{ $role->name }}</h3>
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i class="fas fa-user-tag text-indigo-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-6 h-10">
                            {{ __(':role role with :count associated permissions.', ['role' => $role->name, 'count' => $role->permissions->count()]) }}
                        </p>
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                            <div class="flex -space-x-2">
                                @if($role->users_count > 0)
                                    <div class="w-8 h-8 rounded-full border-2 border-white bg-indigo-100 flex items-center justify-center text-xs text-indigo-600 font-bold">
                                        {{ $role->users_count }}
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('No users') }}</span>
                                @endif
                            </div>
                            <div class="flex space-x-3">
                                <button wire:click="editRole({{ $role->id }})" class="text-gray-600 text-sm font-medium hover:text-indigo-600">{{ __('Edit') }}</button>
                                <button wire:click="configureRole({{ $role->id }})" class="text-indigo-600 text-sm font-medium hover:text-indigo-800">{{ __('Configure') }} <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Permissions List -->
            <div class="mt-10">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Available Permissions') }}</h3>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($permissions as $permission)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 group hover:border-indigo-200 transition-colors">
                                <span class="text-sm font-medium text-gray-700">{{ $permission->name }}</span>
                                <div class="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="editPermission({{ $permission->id }})" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" x-on:click="$dispatch('swal:confirm', {
                                        title: '{{ __('Delete Permission?') }}',
                                        text: '{{ __('Are you sure you want to delete this permission?') }}',
                                        icon: 'warning',
                                        method: 'deletePermission',
                                        params: [{{ $permission->id }}],
                                        componentId: '{{ $this->getId() }}'
                                    })" class="text-gray-400 hover:text-red-600 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        @if($permissions->isEmpty())
                            <div class="col-span-full text-center py-4 text-gray-500 text-sm">
                                {{ __('No permissions found.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Role Modal -->
    <x-modal name="role-modal" focusable>
        <form x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingRoleId ? __('Update Role?') : __('Create Role?') }}',
            text: '{{ $editingRoleId ? __('Are you sure you want to update this role?') : __('Are you sure you want to create this new role?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingRoleId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'saveRole',
            params: [],
            componentId: '{{ $this->getId() }}'
        })" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $editingRoleId ? __('Edit Role') : __('Create New Role') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="roleName" value="{{ __('Role Name') }}" />
                <x-text-input wire:model="roleName" id="roleName" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. Store Manager') }}" />
                <x-input-error :messages="$errors->get('roleName')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ __('Save Role') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Permission Modal -->
    <x-modal name="permission-modal" focusable>
        <form x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingPermissionId ? __('Update Permission?') : __('Create Permission?') }}',
            text: '{{ $editingPermissionId ? __('Are you sure you want to update this permission?') : __('Are you sure you want to create this new permission?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingPermissionId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'savePermission',
            params: [],
            componentId: '{{ $this->getId() }}'
        })" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $editingPermissionId ? __('Edit Permission') : __('Create New Permission') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="permissionName" value="{{ __('Permission Name') }}" />
                <x-text-input wire:model="permissionName" id="permissionName" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. edit_products') }}" />
                <x-input-error :messages="$errors->get('permissionName')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingPermissionId ? __('Update Permission') : __('Save Permission') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Configure Role Modal -->
    <x-modal name="configure-role-modal" maxWidth="4xl">
        <form x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ __('Update Permissions?') }}',
            text: '{{ __('Are you sure you want to update the permissions for this role?') }}',
            icon: 'question',
            confirmButtonText: '{{ __('Yes, update permissions!') }}',
            method: 'updateRolePermissions',
            params: [],
            componentId: '{{ $this->getId() }}'
        })" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ __('Configure Permissions') }}: <span class="text-indigo-600">{{ $editingRoleName }}</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto p-2">
                @foreach($permissions as $permission)
                    <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($permission->name, $selectedPermissions) ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200' }}">
                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $permission->name }}</span>
                    </label>
                @endforeach

                @if($permissions->isEmpty())
                    <div class="col-span-full text-center py-8 text-gray-500">
                        {{ __('No permissions found. Create some permissions first.') }}
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end border-t pt-4">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ __('Update Permissions') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
