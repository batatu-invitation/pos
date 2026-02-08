<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Barryvdh\DomPDF\Facade\Pdf;

new #[Layout('components.layouts.app')] #[Title('User Management')] class extends Component {
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
                ->paginate(9), // Changed to 9 for better grid layout (3x3)
            'roles' => Role::all(),
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'Active')->count(),
            'newUsers' => User::where('created_at', '>=', now()->startOfMonth())->count(),
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

    public function exportExcel()
    {
        return Excel::download(new UsersExport($this->search, $this->roleFilter), 'users.xlsx');
    }

    public function exportPdf()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q->where(function($sub) {
                $sub->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->when($this->roleFilter && $this->roleFilter !== 'All Roles', fn($q) => $q->role($this->roleFilter))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('pdf.users', compact('users'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'users.pdf');
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
            $data['created_by'] = auth()->id();
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

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight dark:text-gray-100">{{ __('User Management') }}</h2>
            <p class="text-gray-500 mt-2 text-sm dark:text-gray-400">{{ __('Manage your team members, assign roles, and monitor activity.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
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
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-50 py-2 border border-gray-100 dark:bg-gray-800 dark:border-gray-700" 
                     style="display: none;">
                    <button @click="
                        Swal.fire({
                            title: 'Export Excel?',
                            text: 'Do you want to export the users to Excel?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#ef4444',
                            confirmButtonText: 'Yes, export!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.exportExcel();
                            }
                        })
                    " class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors dark:text-gray-300 dark:hover:bg-gray-700">
                        <i class="fas fa-file-excel mr-2 text-green-500"></i> Excel
                    </button>
                    <button @click="
                        Swal.fire({
                            title: '{{ __('Export PDF?') }}',
                            text: '{{ __('Do you want to export the users to PDF?') }}',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#ef4444',
                            confirmButtonText: '{{ __('Yes, export!') }}',
                            cancelButtonText: '{{ __('Cancel') }}'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.exportPdf();
                            }
                        })
                    " class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors dark:text-gray-300 dark:hover:bg-gray-700">
                        <i class="fas fa-file-pdf mr-2 text-red-500"></i> PDF
                    </button>
                </div>
            </div>
            <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5 dark:shadow-none">
                <i class="fas fa-plus mr-2"></i> {{ __('New User') }}
            </button>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-3xl shadow-lg shadow-blue-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-blue-100 font-medium mb-1">{{ __('Total Users') }}</p>
                <h3 class="text-4xl font-bold">{{ $totalUsers }}</h3>
                <p class="text-blue-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i> 12% {{ __('from last month') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-blue-400/30 text-5xl">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-3xl shadow-lg shadow-green-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-green-100 font-medium mb-1">{{ __('Active Users') }}</p>
                <h3 class="text-4xl font-bold">{{ $activeUsers }}</h3>
                <p class="text-green-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-check-circle mr-1"></i> {{ __('System operational') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-green-400/30 text-5xl">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-6 rounded-3xl shadow-lg shadow-purple-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-purple-100 font-medium mb-1">{{ __('New This Month') }}</p>
                <h3 class="text-4xl font-bold">{{ $newUsers }}</h3>
                <p class="text-purple-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-plus-circle mr-1"></i> {{ __('Growing team') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-purple-400/30 text-5xl">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/50 to-purple-50/50 opacity-50 dark:from-indigo-900/20 dark:to-purple-900/20"></div>
        <div class="relative z-10 w-full md:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 sm:text-sm shadow-sm group-hover:shadow-md dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:placeholder-gray-500" placeholder="{{ __('Search users by name or email...') }}">
        </div>
        <div class="relative z-10 flex items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-56 group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-filter text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>
                <select wire:model.live="roleFilter" class="block w-full pl-10 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm rounded-2xl bg-white text-gray-700 shadow-sm group-hover:shadow-md appearance-none transition-all dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                    <option>{{ __('All Roles') }}</option>
                    @foreach ($roles as $roleItem)
                        <option value="{{ $roleItem->name }}">{{ $roleItem->name }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($users as $user)
            <div class="group bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col relative transform hover:-translate-y-1 dark:bg-gray-800 dark:border-gray-700">
                <!-- Decorative Gradient Line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                <!-- Status Indicator -->
                <div class="absolute top-4 right-4 z-10">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $user->status === 'Active' ? 'bg-green-50 text-green-700 border border-green-100 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800' : 'bg-gray-50 text-gray-600 border border-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600' }} shadow-sm">
                        <span class="w-2 h-2 rounded-full {{ $user->status === 'Active' ? 'bg-green-500' : 'bg-gray-400' }} mr-2 animate-pulse"></span>
                        {{ $user->status }}
                    </span>
                </div>

                <div class="p-8 flex flex-col items-center text-center border-b border-gray-50 relative dark:border-gray-700">
                    <div class="relative mb-6 group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-2xl blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
                        <img class="h-24 w-24 rounded-2xl object-cover border-4 border-white shadow-md relative z-10 dark:border-gray-700"
                            src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random&size=128&bold=true"
                            alt="{{ $user->name }}">
                        @php
                            $roleColor = match($user->role) {
                                'Super Admin' => 'purple',
                                'Store Manager' => 'blue',
                                'Cashier' => 'green',
                                'Inventory Manager' => 'yellow',
                                'Accountant' => 'indigo',
                                'HR' => 'pink',
                                default => 'gray'
                            };
                        @endphp
                        <div class="absolute -bottom-3 -right-3 bg-white p-1 rounded-lg shadow-sm z-20 dark:bg-gray-800">
                            <div class="bg-{{ $roleColor }}-100 text-{{ $roleColor }}-700 text-xs font-bold px-2 py-1 rounded-md border border-{{ $roleColor }}-200 dark:bg-{{ $roleColor }}-900/30 dark:text-{{ $roleColor }}-400 dark:border-{{ $roleColor }}-800">
                                {{ $user->role }}
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-1 tracking-tight dark:text-white">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500 font-medium bg-gray-50 px-3 py-1 rounded-full border border-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">{{ $user->email }}</p>
                    
                    <div class="flex items-center justify-center gap-3 w-full mt-6">
                        <button wire:click="edit('{{ $user->id }}')" class="flex-1 py-2.5 px-4 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-semibold hover:bg-indigo-600 hover:text-white hover:shadow-lg hover:shadow-indigo-200 transition-all duration-300 group/btn dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-600 dark:hover:text-white">
                            <i class="fas fa-edit mr-2 group-hover/btn:rotate-12 transition-transform"></i> {{ __('Edit') }}
                        </button>
                        <button type="button" x-on:click="$dispatch('swal:confirm', {
                            title: '{{ __('Delete User?') }}',
                            text: '{{ __('Are you sure you want to delete this user?') }}',
                            icon: 'warning',
                            method: 'delete',
                            params: ['{{ $user->id }}'],
                            componentId: '{{ $this->getId() }}'
                        })" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-300 hover:scale-110 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-400">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50/30 flex flex-col gap-3 text-sm text-gray-600 dark:bg-gray-700/30 dark:text-gray-400">
                    <div class="flex items-center justify-between group/row">
                        <span class="text-gray-400 flex items-center group-hover/row:text-indigo-500 transition-colors dark:text-gray-500 dark:group-hover/row:text-indigo-400"><i class="fas fa-phone-alt w-5 text-center mr-2"></i> {{ __('Phone') }}</span>
                        <span class="font-medium font-mono text-gray-700 dark:text-gray-300">{{ $user->phone ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between group/row">
                        <span class="text-gray-400 flex items-center group-hover/row:text-indigo-500 transition-colors dark:text-gray-500 dark:group-hover/row:text-indigo-400"><i class="fas fa-clock w-5 text-center mr-2"></i> {{ __('Joined') }}</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-1 md:col-span-2 lg:col-span-3">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                    <div class="absolute inset-0 bg-grid-slate-50 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))] dark:bg-grid-slate-700/30"></div>
                    <div class="relative z-10">
                        <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce dark:bg-indigo-900/30">
                            <i class="fas fa-users-slash text-4xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 dark:text-white">{{ __('No users found') }}</h3>
                        <p class="text-gray-500 mb-8 max-w-md mx-auto dark:text-gray-400">{{ __('We couldn\'t find any users matching your search. Try adjusting your filters or create a new user to get started.') }}</p>
                        <button wire:click="create" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-xl font-bold text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-1 dark:shadow-none">
                            <i class="fas fa-plus mr-2"></i> {{ __('Add New User') }}
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>

    <!-- User Modal -->
    <x-modal name="user-modal" focusable>
        <div class="bg-white rounded-3xl overflow-hidden dark:bg-gray-800">
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-purple-600 text-white dark:border-gray-700">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">
                        {{ $editingUserId ? __('Edit User') : __('Create New User') }}
                    </h2>
                    <p class="text-indigo-100 text-sm mt-1">
                        {{ $editingUserId ? __('Update user information and role.') : __('Add a new member to your team.') }}
                    </p>
                </div>
                <button x-on:click="$dispatch('close-modal', 'user-modal')" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
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
                class="p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- First Name -->
                    <div class="group">
                        <x-input-label for="first_name" value="{{ __('First Name') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <x-text-input wire:model="first_name" id="first_name" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-3 transition-all dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="text"
                                placeholder="John" />
                        </div>
                        <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                    </div>

                    <!-- Last Name -->
                    <div class="group">
                        <x-input-label for="last_name" value="{{ __('Last Name') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <x-text-input wire:model="last_name" id="last_name" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-3 transition-all dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="text"
                                placeholder="Doe" />
                        </div>
                        <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                    </div>

                    <!-- Email -->
                    <div class="col-span-1 md:col-span-2 group">
                        <x-input-label for="email" value="{{ __('Email Address') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <x-text-input wire:model="email" id="email" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-3 transition-all dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="email"
                                placeholder="john@example.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Phone -->
                    <div class="group">
                        <x-input-label for="phone" value="{{ __('Phone Number') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <x-text-input wire:model="phone" id="phone" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-3 transition-all dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="text"
                                placeholder="+1 234 567 890" />
                        </div>
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Role -->
                    <div class="group">
                        <x-input-label for="role" value="{{ __('Role') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tag text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <select wire:model="role" id="role"
                                class="block w-full pl-10 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm rounded-xl text-gray-700 transition-all appearance-none bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                @foreach ($roles as $roleItem)
                                    <option value="{{ $roleItem->name }}">{{ $roleItem->name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('role')" class="mt-1" />
                    </div>

                    <!-- Status -->
                    <div class="group">
                        <x-input-label for="status" value="{{ __('Status') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-toggle-on text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <select wire:model="status" id="status"
                                class="block w-full pl-10 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm rounded-xl text-gray-700 transition-all appearance-none bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                <option value="Active">{{ __('Active') }}</option>
                                <option value="Inactive">{{ __('Inactive') }}</option>
                                <option value="Suspended">{{ __('Suspended') }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    <!-- Password -->
                    <div class="col-span-1 md:col-span-2 group">
                        <x-input-label for="password"
                            value="{{ $editingUserId ? __('New Password (Optional)') : __('Password') }}" class="text-gray-700 font-bold mb-2 ml-1 dark:text-gray-300" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                            </div>
                            <x-text-input wire:model="password" id="password" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-3 transition-all dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:placeholder-gray-500" type="password"
                                placeholder="********" />
                        </div>
                        <p class="text-xs text-gray-500 mt-2 ml-1 flex items-center dark:text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i> {{ __('Must be at least 8 characters.') }}
                        </p>
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t border-gray-100 pt-6 dark:border-gray-700">
                    <button type="button" x-on:click="$dispatch('close-modal', 'user-modal')"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 transition-all font-bold text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all font-bold text-sm transform hover:-translate-y-0.5 dark:shadow-none">
                        <i class="fas fa-save mr-2"></i> {{ $editingUserId ? __('Update User') : __('Create User') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>