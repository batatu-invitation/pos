<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Profile Settings - Modern POS')]
class extends Component
{
    public $activities = [
        ['action' => 'Logged in', 'date' => '2023-10-25 09:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Updated profile', 'date' => '2023-10-24 04:30 PM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Changed password', 'date' => '2023-10-24 04:15 PM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Logged in', 'date' => '2023-10-24 09:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Logged out', 'date' => '2023-10-23 06:00 PM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Logged in', 'date' => '2023-10-23 09:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Exported sales report', 'date' => '2023-10-22 02:00 PM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Logged in', 'date' => '2023-10-22 10:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Added new user', 'date' => '2023-10-21 11:30 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'Logged in', 'date' => '2023-10-21 09:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
        ['action' => 'System backup', 'date' => '2023-10-20 11:00 PM', 'ip' => 'System', 'device' => 'Server'],
        ['action' => 'Logged in', 'date' => '2023-10-20 09:00 AM', 'ip' => '192.168.1.1', 'device' => 'Chrome on MacOS'],
    ];
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h2>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col items-center mb-8">
                <div class="relative">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&size=128&background=4f46e5&color=fff" class="w-32 h-32 rounded-full mb-4 shadow-sm" alt="Avatar">
                    <button class="absolute bottom-4 right-0 p-2 bg-white rounded-full shadow-md border border-gray-200 text-gray-600 hover:text-indigo-600">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Admin User</h3>
                <p class="text-gray-500">Store Manager</p>
            </div>

            <form class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" class="block w-full rounded-lg border-gray-300 border p-2.5" value="Admin">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" class="block w-full rounded-lg border-gray-300 border p-2.5" value="User">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" class="block w-full rounded-lg border-gray-300 border p-2.5" value="admin@pos.com">
                    </div>

                    <div class="col-span-2 border-t border-gray-100 pt-6 mt-2">
                        <h4 class="font-medium text-gray-900 mb-4">Change Password</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Recent Activity Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-sm">
                            <th class="p-3 font-medium">Action</th>
                            <th class="p-3 font-medium">Date & Time</th>
                            <th class="p-3 font-medium">IP Address</th>
                            <th class="p-3 font-medium">Device</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($activities as $activity)
                        <tr class="hover:bg-gray-50 text-sm text-gray-700">
                            <td class="p-3 font-medium">{{ $activity['action'] }}</td>
                            <td class="p-3">{{ $activity['date'] }}</td>
                            <td class="p-3 font-mono text-xs">{{ $activity['ip'] }}</td>
                            <td class="p-3">{{ $activity['device'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
