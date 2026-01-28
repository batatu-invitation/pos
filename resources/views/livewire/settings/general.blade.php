<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] 
    #[Title('General Settings - Modern POS')] 
    class extends Component {
    public $storeName = 'Modern POS';
    public $currency = 'USD ($)';
    public $phone = '+1 234 567 890';
    public $email = 'support@modernpos.com';
    public $streetAddress = '123 Main St';
    public $city = 'New York';
    public $zipCode = '10001';

    public $currencies = [
        'USD ($)' => 'United States Dollar',
        'EUR (€)' => 'Euro',
        'GBP (£)' => 'British Pound',
        'JPY (¥)' => 'Japanese Yen',
        'CAD ($)' => 'Canadian Dollar',
        'AUD ($)' => 'Australian Dollar',
        'CNY (¥)' => 'Chinese Yuan',
        'INR (₹)' => 'Indian Rupee',
        'BRL (R$)' => 'Brazilian Real',
        'RUB (₽)' => 'Russian Ruble',
        'KRW (₩)' => 'South Korean Won',
        'SGD ($)' => 'Singapore Dollar',
    ];

    public $timezones = [
        'UTC-12:00' => 'International Date Line West',
        'UTC-11:00' => 'Midway Island, Samoa',
        'UTC-10:00' => 'Hawaii',
        'UTC-09:00' => 'Alaska',
        'UTC-08:00' => 'Pacific Time (US & Canada)',
        'UTC-07:00' => 'Mountain Time (US & Canada)',
        'UTC-06:00' => 'Central Time (US & Canada)',
        'UTC-05:00' => 'Eastern Time (US & Canada)',
        'UTC-04:00' => 'Atlantic Time (Canada)',
        'UTC-03:00' => 'Brasilia, Buenos Aires',
        'UTC-02:00' => 'Mid-Atlantic',
        'UTC-01:00' => 'Azores, Cape Verde Is.',
        'UTC+00:00' => 'London, Dublin, Edinburgh',
    ];

    public $auditLogs = [
        ['action' => 'Updated Store Name', 'user' => 'Super Admin', 'date' => '2023-10-25 10:30 AM', 'ip' => '192.168.1.1'],
        ['action' => 'Changed Currency to USD', 'user' => 'Super Admin', 'date' => '2023-10-24 02:15 PM', 'ip' => '192.168.1.1'],
        ['action' => 'Updated Address', 'user' => 'Super Admin', 'date' => '2023-10-23 09:45 AM', 'ip' => '192.168.1.1'],
        ['action' => 'Changed Timezone', 'user' => 'Admin User', 'date' => '2023-10-22 11:20 AM', 'ip' => '192.168.1.5'],
        ['action' => 'Updated Phone Number', 'user' => 'Super Admin', 'date' => '2023-10-21 04:00 PM', 'ip' => '192.168.1.1'],
        ['action' => 'Updated Email Address', 'user' => 'Super Admin', 'date' => '2023-10-20 01:30 PM', 'ip' => '192.168.1.1'],
        ['action' => 'System Backup Created', 'user' => 'System', 'date' => '2023-10-19 03:00 AM', 'ip' => '127.0.0.1'],
        ['action' => 'Updated Tax Settings', 'user' => 'Super Admin', 'date' => '2023-10-18 10:00 AM', 'ip' => '192.168.1.1'],
        ['action' => 'Changed Receipt Header', 'user' => 'Manager', 'date' => '2023-10-17 05:45 PM', 'ip' => '192.168.1.10'],
        ['action' => 'Updated Store Logo', 'user' => 'Super Admin', 'date' => '2023-10-16 09:15 AM', 'ip' => '192.168.1.1'],
        ['action' => 'Enabled Maintenance Mode', 'user' => 'System', 'date' => '2023-10-15 11:30 PM', 'ip' => '127.0.0.1'],
        ['action' => 'Disabled Maintenance Mode', 'user' => 'System', 'date' => '2023-10-15 11:45 PM', 'ip' => '127.0.0.1'],
    ];

    public function save()
    {
        // Save logic here
        session()->flash('message', 'Settings saved successfully.');
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Settings</h2>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.notifications') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <a href="{{ route('settings.integrations') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
        </div>
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Store Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                            <input wire:model="storeName" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                            <select wire:model="currency" class="block w-full rounded-lg border-gray-300 border p-2.5">
                                @foreach($currencies as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input wire:model="phone" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input wire:model="email" type="email" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                            <select class="block w-full rounded-lg border-gray-300 border p-2.5">
                                @foreach($timezones as $offset => $name)
                                    <option value="{{ $offset }}">{{ $offset }} - {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Address</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <input wire:model="streetAddress" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input wire:model="city" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                                <input wire:model="zipCode" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Configuration History / Audit Log -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Configuration History</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm">
                        <th class="p-3 font-medium">Action</th>
                        <th class="p-3 font-medium">User</th>
                        <th class="p-3 font-medium">Date & Time</th>
                        <th class="p-3 font-medium">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($auditLogs as $log)
                    <tr class="hover:bg-gray-50 text-sm text-gray-700">
                        <td class="p-3 font-medium">{{ $log['action'] }}</td>
                        <td class="p-3">{{ $log['user'] }}</td>
                        <td class="p-3">{{ $log['date'] }}</td>
                        <td class="p-3 font-mono text-xs">{{ $log['ip'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>