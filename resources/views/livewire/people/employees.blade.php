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
    public $search = '';

    public function with()
    {
        $user = auth()->user();
        $userId = $user->created_by ? $user->created_by : $user->id;
        $isSuperAdmin = $user->hasRole('Super Admin');

        // Base query for employees listing
        $query = User::query();
        
        if ($isSuperAdmin) {
            $query->where('id', '!=', $user->id);
        } else {
            $query->where('created_by', $userId);
        }

        // Apply Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        // Stats query (independent of search)
        $statsQuery = User::query();
        if ($isSuperAdmin) {
            $statsQuery->where('id', '!=', $user->id);
        } else {
            $statsQuery->where('created_by', $userId);
        }
        
        $totalEmployees = $statsQuery->count();
        $activeEmployees = (clone $statsQuery)->where('status', 'active')->count();
        $newEmployeesThisMonth = (clone $statsQuery)->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())->count();

        // Available Roles logic
        $availableRolesQuery = Role::whereNotIn('name', ['Super Admin', 'Manager', 'Customer Support']);
        if (!$isSuperAdmin) {
            $availableRolesQuery->withCount('users');
        }
        $availableRoles = $availableRolesQuery->get();
        
        if (!$isSuperAdmin) {
            $availableRoles = $availableRoles->filter(function ($role) {
                return $role->users_count < 3;
            });
        }

        return [
            'employees' => $query->with('roles')->latest()->paginate(10),
            'availableRoles' => $availableRoles,
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'newEmployeesThisMonth' => $newEmployeesThisMonth,
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

        $user = auth()->user();
        $userId = $user->created_by ? $user->created_by : $user->id;

        // Only set created_by on creation to avoid overwriting if transferred (though logic here is strict ownership)
        if (!$this->editingUserId) {
            $data['created_by'] = $userId;
            $data['input_id'] = $user->id;
        }

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $employee = User::find($this->editingUserId);
            $employee->update($data);
        } else {
            $employee = User::create($data);
        }

        $employee->syncRoles([$this->role]);

        $this->dispatch('close-modal', 'employee-modal');
        $this->reset('firstName', 'lastName', 'email', 'password', 'phone', 'role', 'status', 'editingUserId');
        $this->dispatch('notify', __('Employee saved successfully!'));
    }

    public function deleteEmployee($id)
    {
        $user = auth()->user();
        $userId = $user->created_by ? $user->created_by : $user->id;

        $employee = User::where('id', $id)
            ->where('created_by', $userId)
            ->first();
        if ($employee) {
            $employee->delete();
            $this->dispatch('notify', __('Employee deleted successfully!'));
        }
    }

    public function exportExcel()
    {
        return Excel::download(new EmployeesExport, 'employees.xlsx');
    }

    public function exportPdf()
    {
        $user = auth()->user();
        $userId = $user->created_by ? $user->created_by : $user->id;

        if ($user->hasRole('Super Admin')) {
            $employees = User::with('roles')
                ->where('id', '!=', $user->id)
                ->latest()
                ->get();
        } else {
            $employees = User::where('created_by', $userId)
                ->with('roles')
                ->latest()
                ->get();
        }

        $pdf = Pdf::loadView('pdf.employees', compact('employees'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'employees.pdf');
    }
};
?>

<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6 space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Employees') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Manage your team members and their roles.') }}</p>
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
            <button wire:click="createEmployee" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Employee') }}
            </button>
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Employees -->
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Employees') }}</span>
                    <i class="fas fa-users text-blue-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    {{ number_format($totalEmployees) }}
                </div>
                <div class="text-blue-100 text-sm opacity-90">
                    {{ __('Team members') }}
                </div>
            </div>
        </div>

        <!-- Active Employees -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Active') }}</span>
                    <i class="fas fa-user-check text-emerald-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    {{ number_format($activeEmployees) }}
                </div>
                <div class="text-emerald-100 text-sm opacity-90">
                    {{ __('Currently active') }}
                </div>
            </div>
        </div>
        
        <!-- New This Month -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between hover:shadow-md transition-shadow duration-300 relative overflow-hidden">
             <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('New This Month') }}</p>
                <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($newEmployeesThisMonth) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                <i class="fas fa-calendar-plus text-xl"></i>
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
                    <input wire:model.live="search" type="text" class="w-full py-2.5 pl-10 pr-4 bg-gray-50 dark:bg-gray-700/50 border-0 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20" placeholder="{{ __('Search employees...') }}">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400 tracking-wider">
                    <tr>
                        <th class="px-6 py-4">{{ __('Name') }}</th>
                        <th class="px-6 py-4">{{ __('Role') }}</th>
                        <th class="px-6 py-4">{{ __('Email') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors group">
                            <td class="px-6 py-4 flex items-center">
                                @if ($employee->avatar)
                                    <img src="{{ $employee->avatar }}" class="w-8 h-8 rounded-full mr-3 object-cover ring-2 ring-white dark:ring-gray-800 shadow-sm" alt="Avatar">
                                @else
                                    <div class="w-8 h-8 rounded-full mr-3 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-bold text-xs ring-2 ring-white dark:ring-gray-800 shadow-sm">
                                        {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $employee->phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @foreach ($employee->roles as $role)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4">{{ $employee->email }}</td>
                            <td class="px-6 py-4">
                                @if ($employee->status === 'active' || $employee->status === 'Active')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="editEmployee('{{ $employee->id }}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors" title="Edit">
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
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg dark:text-red-400 dark:hover:bg-red-900/30 transition-colors" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-users text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                    <p>{{ __('No employees found.') }}</p>
                                    <p class="text-xs mt-1">{{ __('Start by adding a new employee to your team.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            {{ $employees->links() }}
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
            class="bg-white dark:bg-gray-800 p-6 rounded-3xl">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                {{ $editingUserId ? __('Edit Employee') : __('Create New Employee') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <x-input-label for="firstName" value="{{ __('First Name') }}" class="dark:text-gray-300" />
                    <x-text-input wire:model="firstName" id="firstName" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="text"
                        required />
                    <x-input-error :messages="$errors->get('firstName')" class="mt-2 dark:text-red-400" />
                </div>
                <div>
                    <x-input-label for="lastName" value="{{ __('Last Name') }}" class="dark:text-gray-300" />
                    <x-text-input wire:model="lastName" id="lastName" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="text"
                        required />
                    <x-input-error :messages="$errors->get('lastName')" class="mt-2 dark:text-red-400" />
                </div>

                <!-- Contact -->
                <div class="md:col-span-2">
                    <x-input-label for="email" value="{{ __('Email Address') }}" class="dark:text-gray-300" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="email" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 dark:text-red-400" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="phone" value="{{ __('Phone Number') }}" class="dark:text-gray-300" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="text" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2 dark:text-red-400" />
                </div>

                <!-- Role & Status -->
                <div>
                    <x-input-label for="role" value="{{ __('Role') }}" class="dark:text-gray-300" />
                    <select wire:model="role" id="role"
                        class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm block mt-1 w-full p-2.5">
                        <option value="">{{ __('Select Role') }}</option>
                        @foreach ($availableRoles as $roleOption)
                            <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2 dark:text-red-400" />
                </div>
                <div>
                    <x-input-label for="status" value="{{ __('Status') }}" class="dark:text-gray-300" />
                    <select wire:model="status" id="status"
                        class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm block mt-1 w-full p-2.5">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2 dark:text-red-400" />
                </div>

                <!-- Password -->
                <div class="md:col-span-2">
                    <x-input-label for="password"
                        value="{{ $editingUserId ? __('Password (leave blank to keep current)') : __('Password') }}" class="dark:text-gray-300" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2 dark:text-red-400" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="rounded-xl dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="rounded-xl">
                    {{ $editingUserId ? __('Update Employee') : __('Create Employee') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
