<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new
#[Layout('components.layouts.app')]
#[Title('Profile Settings - Modern POS')]
class extends Component
{
    public $activeTab = 'profile';

    // Profile Data
    public $name = '';
    public $email = '';
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public $activities = [];

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->loadActivities();
    }

    public function loadActivities()
    {
        if (class_exists(Activity::class)) {
            $this->activities = Activity::where('causer_id', auth()->id())
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($activity) {
                    $action = $activity->description;

                    if ($activity->subject_type === 'App\Models\User' && $activity->description === 'updated') {
                        $action = 'Updated profile';
                    }

                    return [
                        'action' => ucfirst($action),
                        'date' => $activity->created_at->format('Y-m-d h:i A'),
                        'ip' => $activity->getExtraProperty('ip') ?? 'System',
                        'device' => $activity->getExtraProperty('device') ?? 'Server',
                    ];
                })->toArray();
        }
    }

    public function saveProfile()
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->forceFill([
            'name' => $this->name,
            'email' => $this->email,
        ])->save();

        if ($this->new_password) {
            $this->validate([
                'current_password' => ['required', 'current_password'],
                'new_password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $user->forceFill([
                'password' => Hash::make($this->new_password),
            ])->save();
        }

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('notify', 'Profile updated successfully!');
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6" x-data="{ activeTab: @entangle('activeTab') }">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Settings</h2>

        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'"
                    :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'profile', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300': activeTab !== 'profile' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-user mr-2"></i> Profile
                </button>
                <button @click="activeTab = 'activity'"
                    :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'activity', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300': activeTab !== 'activity' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-history mr-2"></i> Recent Activity
                </button>
            </nav>
        </div>

        <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex flex-col items-center mb-8">
                    <div class="relative">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($name) }}&size=128&background=4f46e5&color=fff" class="w-32 h-32 rounded-full mb-4 shadow-sm" alt="Avatar">
                        <button class="absolute bottom-4 right-0 p-2 bg-white dark:bg-gray-700 rounded-full shadow-md border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">{{ $name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400">Store Manager</p>
                </div>

                <form wire:submit="saveProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input type="text" wire:model="name" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white border p-2.5">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" wire:model="email" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white border p-2.5">
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-2 border-t border-gray-100 dark:border-gray-700 pt-6 mt-2">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Change Password</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
                                    <input type="password" wire:model="current_password" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white border p-2.5">
                                    @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                                    <input type="password" wire:model="new_password" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white border p-2.5">
                                    @error('new_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
                                    <input type="password" wire:model="new_password_confirmation" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white border p-2.5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <span wire:loading.remove target="saveProfile">Save Changes</span>
                            <span wire:loading target="saveProfile"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activity Tab -->
        <div x-show="activeTab === 'activity'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Recent Activity</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm">
                                <th class="p-3 font-medium">Action</th>
                                <th class="p-3 font-medium">Date & Time</th>
                                <th class="p-3 font-medium">IP Address</th>
                                <th class="p-3 font-medium">Device</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($activities as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300">
                                <td class="p-3 font-medium">{{ $activity['action'] }}</td>
                                <td class="p-3">{{ $activity['date'] }}</td>
                                <td class="p-3 font-mono text-xs">{{ $activity['ip'] }}</td>
                                <td class="p-3">{{ $activity['device'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500 dark:text-gray-400">No recent activity found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
