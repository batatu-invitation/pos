<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new 
#[Layout('components.layouts.app')]
#[Title('Notification Settings - Modern POS')]
class extends Component
{
    public $newOrderAlert = true;
    public $dailySalesSummary = true;
    public $lowStockWarning = true;
    public $emailChannel = true;
    public $smsChannel = false;
    public $pushChannel = true;

    // Data for "Notification History" to satisfy 10+ entries requirement
    public $notificationHistory = [
        ['id' => 1, 'type' => 'System', 'message' => 'System backup completed successfully', 'date' => '2023-10-25 02:00 AM', 'status' => 'Read'],
        ['id' => 2, 'type' => 'Inventory', 'message' => 'Low stock alert: Milk (Whole) - 2L', 'date' => '2023-10-24 04:30 PM', 'status' => 'Unread'],
        ['id' => 3, 'type' => 'Sales', 'message' => 'Daily sales report ready for download', 'date' => '2023-10-24 08:00 PM', 'status' => 'Read'],
        ['id' => 4, 'type' => 'Security', 'message' => 'New login from IP 192.168.1.5', 'date' => '2023-10-24 09:15 AM', 'status' => 'Read'],
        ['id' => 5, 'type' => 'Inventory', 'message' => 'Low stock alert: Bread (White) - 500g', 'date' => '2023-10-23 03:45 PM', 'status' => 'Read'],
        ['id' => 6, 'type' => 'System', 'message' => 'Software update v2.1.0 available', 'date' => '2023-10-23 10:00 AM', 'status' => 'Unread'],
        ['id' => 7, 'type' => 'Sales', 'message' => 'High value transaction detected: $540.00', 'date' => '2023-10-22 02:20 PM', 'status' => 'Read'],
        ['id' => 8, 'type' => 'System', 'message' => 'Database optimization completed', 'date' => '2023-10-22 03:00 AM', 'status' => 'Read'],
        ['id' => 9, 'type' => 'Inventory', 'message' => 'Stock adjustment: +50 units (Apples)', 'date' => '2023-10-21 11:30 AM', 'status' => 'Read'],
        ['id' => 10, 'type' => 'User', 'message' => 'New user created: cashier_02', 'date' => '2023-10-21 09:00 AM', 'status' => 'Read'],
        ['id' => 11, 'type' => 'System', 'message' => 'Weekly performance report available', 'date' => '2023-10-21 08:00 AM', 'status' => 'Read'],
        ['id' => 12, 'type' => 'Security', 'message' => 'Failed login attempt: admin', 'date' => '2023-10-20 10:15 PM', 'status' => 'Read'],
    ];

    public function save()
    {
        session()->flash('message', 'Notification preferences saved successfully.');
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Alert Settings</h2>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">Notifications</button>
            <a href="{{ route('settings.integrations') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
            <a href="{{ route('settings.api-keys') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">API Keys</a>
            <a href="{{ route('settings.backup') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Backup</a>
        </div>
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <form wire:submit="save" class="space-y-8">
                <!-- Sales Notifications -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sales & Orders</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">New Order Alert</p>
                                <p class="text-xs text-gray-500">Get notified when a new order is placed.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="newOrderAlert" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Daily Sales Summary</p>
                                <p class="text-xs text-gray-500">Receive a daily email summary of sales performance.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="dailySalesSummary" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Inventory Notifications -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Inventory Alerts</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Low Stock Warning</p>
                                <p class="text-xs text-gray-500">Notify when items fall below reorder level.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="lowStockWarning" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Channels -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Channels</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                            <input id="email-notif" type="checkbox" wire:model="emailChannel" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="email-notif" class="ml-3 text-sm font-medium text-gray-900">Email</label>
                        </div>
                        <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                            <input id="sms-notif" type="checkbox" wire:model="smsChannel" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="sms-notif" class="ml-3 text-sm font-medium text-gray-900">SMS</label>
                        </div>
                        <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                            <input id="push-notif" type="checkbox" wire:model="pushChannel" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="push-notif" class="ml-3 text-sm font-medium text-gray-900">Push Notifications</label>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Save Preferences</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification History (to satisfy 10+ entries requirement) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Recent Notifications</h3>
            <p class="text-sm text-gray-500">History of sent alerts and system messages</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Type</th>
                        <th class="px-6 py-3 font-medium">Message</th>
                        <th class="px-6 py-3 font-medium">Date & Time</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($notificationHistory as $notif)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    {{ $notif['type'] === 'System' ? 'bg-blue-100 text-blue-700' : 
                                       ($notif['type'] === 'Inventory' ? 'bg-yellow-100 text-yellow-700' : 
                                       ($notif['type'] === 'Sales' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ $notif['type'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $notif['message'] }}</td>
                            <td class="px-6 py-3">{{ $notif['date'] }}</td>
                            <td class="px-6 py-3">
                                <span class="text-xs {{ $notif['status'] === 'Unread' ? 'font-bold text-indigo-600' : 'text-gray-500' }}">
                                    {{ $notif['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
