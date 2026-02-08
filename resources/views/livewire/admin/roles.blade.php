<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RolesExport;

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
        $permissions = Permission::all();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            return ucfirst($parts[0]);
        });

        return [
            'roles' => Role::withCount('users')->with('permissions')->get(),
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
        ];
    }

    public function exportExcel()
    {
        return Excel::download(new RolesExport, 'roles.xlsx');
    }

    public function exportPdf()
    {
        $roles = Role::withCount('users')->with('permissions')->get();
        $pdf = Pdf::loadView('pdf.roles', compact('roles'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'roles.pdf');
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
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 dark:bg-gray-900">

            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 tracking-tight dark:text-gray-100">{{ __('Roles & Permissions') }}</h2>
                    <p class="text-gray-500 mt-2 text-sm dark:text-gray-400">{{ __('Manage system access, roles, and security permissions.') }}</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="fas fa-file-export mr-2 text-gray-400"></i> {{ __('Export') }}
                            <i class="fas fa-chevron-down ml-2 text-xs opacity-50"></i>
                        </button>
                        <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl z-50 border border-gray-100 py-2 dark:bg-gray-800 dark:border-gray-700" style="display: none;"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95">
                            <button wire:click="exportExcel" @click="open = false" class="flex items-center w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors dark:text-gray-300 dark:hover:bg-gray-700">
                                <i class="fas fa-file-excel text-green-600 mr-3 w-4 text-center"></i> {{ __('Export Excel') }}
                            </button>
                            <button wire:click="exportPdf" @click="open = false" class="flex items-center w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors dark:text-gray-300 dark:hover:bg-gray-700">
                                <i class="fas fa-file-pdf text-red-600 mr-3 w-4 text-center"></i> {{ __('Export PDF') }}
                            </button>
                        </div>
                    </div>

                    <button wire:click="createPermission" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                        <i class="fas fa-key mr-2 text-indigo-500"></i> {{ __('New Permission') }}
                    </button>
                    <button wire:click="createRole" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5 dark:shadow-none">
                        <i class="fas fa-user-shield mr-2"></i> {{ __('New Role') }}
                    </button>
                </div>
            </div>

            <!-- Stats Overview Bento -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Total Roles') }}</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $roles->count() }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <i class="fas fa-user-shield text-xl"></i>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Total Permissions') }}</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $permissions->count() }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <i class="fas fa-key text-xl"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Users with Roles') }}</p>
                        <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $roles->sum('users_count') }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Roles Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                <!-- Add New Role Card (First in the grid for visibility) -->
                <button wire:click="createRole" class="group relative flex flex-col items-center justify-center p-6 rounded-3xl border-2 border-dashed border-gray-300 hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-300 h-full min-h-[180px] dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:bg-indigo-900/20">
                    <div class="w-12 h-12 rounded-full bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mb-3 transition-colors dark:bg-gray-700 dark:group-hover:bg-indigo-800">
                        <i class="fas fa-plus text-gray-400 group-hover:text-indigo-600 text-xl transition-colors dark:text-gray-300 dark:group-hover:text-indigo-300"></i>
                    </div>
                    <span class="font-semibold text-gray-600 group-hover:text-indigo-700 dark:text-gray-400 dark:group-hover:text-indigo-300">{{ __('Create New Role') }}</span>
                    <span class="text-xs text-gray-400 mt-1 dark:text-gray-500">{{ __('Define a new access level') }}</span>
                </button>

                @foreach($roles as $role)
                    <div class="group relative bg-white p-6 rounded-3xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 flex flex-col justify-between overflow-hidden {{ $role->name === 'Super Admin' ? 'md:col-span-2 bg-gradient-to-br from-indigo-600 to-purple-700 text-white border-transparent' : 'dark:bg-gray-800 dark:border-gray-700' }}">
                        
                        <!-- Decorative background blob for Super Admin -->
                        @if($role->name === 'Super Admin')
                            <div class="absolute -right-6 -top-6 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                            <div class="absolute -left-6 -bottom-6 w-24 h-24 bg-black opacity-10 rounded-full blur-xl"></div>
                        @endif

                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div class="p-3 {{ $role->name === 'Super Admin' ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400' }} rounded-2xl">
                                    <i class="fas {{ $role->name === 'Super Admin' ? 'fa-crown' : 'fa-user-shield' }} text-xl"></i>
                                </div>
                                @if($role->name !== 'Super Admin')
                                    <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button wire:click="editRole({{ $role->id }})" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors dark:hover:bg-gray-700 dark:hover:text-indigo-400">
                                            <i class="fas fa-pen text-sm"></i>
                                        </button>
                                        <button type="button" x-on:click="$dispatch('swal:confirm', {
                                            title: '{{ __('Delete Role?') }}',
                                            text: '{{ __('Are you sure you want to delete this role?') }}',
                                            icon: 'warning',
                                            method: 'deleteRole',
                                            params: [{{ $role->id }}],
                                            componentId: '{{ $this->getId() }}'
                                        })" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors dark:hover:bg-red-900/30 dark:hover:text-red-400">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="px-3 py-1 bg-white/20 rounded-full text-xs font-medium backdrop-blur-sm">
                                        {{ __('System Core') }}
                                    </div>
                                @endif
                            </div>
                            
                            <h3 class="font-bold text-lg {{ $role->name === 'Super Admin' ? 'text-white' : 'text-gray-800 dark:text-gray-100' }} mb-1">{{ $role->name }}</h3>
                            <p class="text-sm {{ $role->name === 'Super Admin' ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $role->permissions->count() }} {{ __('permissions granted') }}
                            </p>
                        </div>

                        <div class="relative z-10 mt-6 pt-4 {{ $role->name === 'Super Admin' ? 'border-t border-white/10' : 'border-t border-gray-50 dark:border-gray-700' }} flex justify-between items-center">
                            <div class="flex -space-x-2">
                                @if($role->users_count > 0)
                                    <div class="w-8 h-8 rounded-full border-2 {{ $role->name === 'Super Admin' ? 'border-indigo-600 bg-white text-indigo-600' : 'border-white bg-indigo-100 text-indigo-600 dark:border-gray-800 dark:bg-indigo-900/50 dark:text-indigo-300' }} flex items-center justify-center text-xs font-bold">
                                        {{ $role->users_count }}
                                    </div>
                                    <span class="ml-4 text-xs {{ $role->name === 'Super Admin' ? 'text-indigo-100' : 'text-gray-400 dark:text-gray-500' }} self-center">{{ __('users assigned') }}</span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('No users') }}</span>
                                @endif
                            </div>
                            
                            <button wire:click="configureRole({{ $role->id }})" class="flex items-center text-sm font-medium {{ $role->name === 'Super Admin' ? 'text-white hover:text-indigo-100' : 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300' }} transition-colors">
                                {{ __('Configure') }} <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Permissions List -->
            <div class="mt-10">
                <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Available Permissions') }}</h3>
                
                @if($groupedPermissions->isEmpty())
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 text-center text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                        {{ __('No permissions found.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($groupedPermissions as $group => $perms)
                            <div class="bg-white rounded-3xl shadow-sm hover:shadow-md border border-gray-100 overflow-hidden transition-all duration-300 flex flex-col h-full dark:bg-gray-800 dark:border-gray-700">
                                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center dark:bg-gray-700/50 dark:border-gray-700">
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200">{{ $group }}</h4>
                                    <span class="text-xs px-2.5 py-1 bg-white border border-gray-200 rounded-full text-gray-600 font-medium dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">{{ $perms->count() }}</span>
                                </div>
                                <div class="p-6 space-y-3 flex-1">
                                    @foreach($perms as $permission)
                                        <div class="flex items-center justify-between group p-2 hover:bg-gray-50 rounded-xl transition-colors -mx-2 dark:hover:bg-gray-700/50">
                                            <div class="flex items-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-gray-300 mr-3 group-hover:bg-indigo-500 transition-colors dark:bg-gray-600"></div>
                                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors dark:text-gray-400 dark:group-hover:text-gray-200">{{ $permission->name }}</span>
                                            </div>
                                            <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button wire:click="editPermission({{ $permission->id }})" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors dark:hover:bg-gray-600 dark:hover:text-indigo-400">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                                <button type="button" x-on:click="$dispatch('swal:confirm', {
                                                    title: '{{ __('Delete Permission?') }}',
                                                    text: '{{ __('Are you sure you want to delete this permission?') }}',
                                                    icon: 'warning',
                                                    method: 'deletePermission',
                                                    params: [{{ $permission->id }}],
                                                    componentId: '{{ $this->getId() }}'
                                                })" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:hover:bg-red-900/30 dark:hover:text-red-400">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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
        })" class="p-6 bg-white dark:bg-gray-800 rounded-3xl">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $editingRoleId ? __('Edit Role') : __('Create New Role') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="roleName" value="{{ __('Role Name') }}" class="dark:text-gray-300" />
                <x-text-input wire:model="roleName" id="roleName" class="block mt-1 w-full dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 rounded-xl" type="text" placeholder="{{ __('e.g. Store Manager') }}" />
                <x-input-error :messages="$errors->get('roleName')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-xl">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
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
        })" class="p-6 bg-white dark:bg-gray-800 rounded-3xl">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $editingPermissionId ? __('Edit Permission') : __('Create New Permission') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="permissionName" value="{{ __('Permission Name') }}" class="dark:text-gray-300" />
                <x-text-input wire:model="permissionName" id="permissionName" class="block mt-1 w-full dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 rounded-xl" type="text" placeholder="{{ __('e.g. edit_products') }}" />
                <x-input-error :messages="$errors->get('permissionName')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-xl">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
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
        })" class="p-6 bg-white dark:bg-gray-800 rounded-3xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ __('Configure Permissions') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Role:') }} <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $editingRoleName }}</span></p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 transition-colors dark:hover:text-gray-300">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="max-h-[65vh] overflow-y-auto p-1 custom-scrollbar">
                @if($groupedPermissions->isEmpty())
                    <div class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200 dark:bg-gray-900/50 dark:border-gray-700">
                        <i class="fas fa-folder-open text-gray-300 text-4xl mb-3 dark:text-gray-600"></i>
                        <p class="text-gray-500 dark:text-gray-400">{{ __('No permissions found. Create some permissions first.') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($groupedPermissions as $group => $perms)
                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 dark:bg-gray-900/50 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200/50 dark:border-gray-700">
                                    <h4 class="font-bold text-gray-800 flex items-center dark:text-gray-200">
                                        <div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div>
                                        {{ $group }}
                                    </h4>
                                    <span class="text-xs font-medium px-2 py-1 bg-white rounded-md text-gray-500 border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">{{ $perms->count() }}</span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($perms as $permission)
                                        <label class="group relative flex items-center p-3 rounded-xl border cursor-pointer transition-all duration-200 {{ in_array($permission->name, $selectedPermissions) ? 'bg-white border-indigo-200 shadow-sm ring-1 ring-indigo-500/20 dark:bg-gray-800 dark:border-indigo-500/50 dark:ring-indigo-500/40' : 'bg-white border-gray-200 hover:border-indigo-300 hover:shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:hover:border-indigo-500/50' }}">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-colors cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <span class="font-medium text-gray-700 group-hover:text-indigo-700 transition-colors block truncate dark:text-gray-300 dark:group-hover:text-indigo-400" title="{{ $permission->name }}">
                                                    {{ str_replace($group . '_', '', $permission->name) }}
                                                </span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <x-secondary-button x-on:click="$dispatch('close')" class="!rounded-xl !px-5 !py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3 !rounded-xl !px-5 !py-2.5 !bg-indigo-600 hover:!bg-indigo-700 shadow-lg shadow-indigo-100 dark:shadow-none dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    {{ __('Update Permissions') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
