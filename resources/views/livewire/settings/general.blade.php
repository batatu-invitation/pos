<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
    #[Title('General Settings - Modern POS')]
    class extends Component {
    public $storeName = 'Modern POS';
    public $currency = 'IDR (Rp)';
    public $phone = '+1 234 567 890';
    public $email = 'support@modernpos.com';
    public $streetAddress = '123 Main St';
    public $city = 'New York';
    public $zipCode = '10001';

    public function mount()
    {
        $this->currencies = [
            'USD ($)' => __('United States Dollar'),
            'EUR (€)' => __('Euro'),
            'GBP (£)' => __('British Pound'),
            'JPY (¥)' => __('Japanese Yen'),
            'CAD ($)' => __('Canadian Dollar'),
            'AUD ($)' => __('Australian Dollar'),
            'CNY (¥)' => __('Chinese Yuan'),
            'INR (₹)' => __('Indian Rupee'),
            'BRL (R$)' => __('Brazilian Real'),
            'RUB (₽)' => __('Russian Ruble'),
            'KRW (₩)' => __('South Korean Won'),
            'SGD ($)' => __('Singapore Dollar'),
        ];

        $this->timezones = [
            'UTC-12:00' => __('International Date Line West'),
            'UTC-11:00' => __('Midway Island, Samoa'),
            'UTC-10:00' => __('Hawaii'),
            'UTC-09:00' => __('Alaska'),
            'UTC-08:00' => __('Pacific Time (US & Canada)'),
            'UTC-07:00' => __('Mountain Time (US & Canada)'),
            'UTC-06:00' => __('Central Time (US & Canada)'),
            'UTC-05:00' => __('Eastern Time (US & Canada)'),
            'UTC-04:00' => __('Atlantic Time (Canada)'),
            'UTC-03:00' => __('Brasilia, Buenos Aires'),
            'UTC-02:00' => __('Mid-Atlantic'),
            'UTC-01:00' => __('Azores, Cape Verde Is.'),
            'UTC+00:00' => __('London, Dublin, Edinburgh'),
        ];

        $this->auditLogs = [
            ['action' => __('Updated Store Name'), 'user' => __('Super Admin'), 'date' => '2023-10-25 10:30 AM', 'ip' => '192.168.1.1'],
            ['action' => __('Changed Currency to USD'), 'user' => __('Super Admin'), 'date' => '2023-10-24 02:15 PM', 'ip' => '192.168.1.1'],
            ['action' => __('Updated Address'), 'user' => __('Super Admin'), 'date' => '2023-10-23 09:45 AM', 'ip' => '192.168.1.1'],
            ['action' => __('Changed Timezone'), 'user' => __('Admin User'), 'date' => '2023-10-22 11:20 AM', 'ip' => '192.168.1.5'],
            ['action' => __('Updated Phone Number'), 'user' => __('Super Admin'), 'date' => '2023-10-21 04:00 PM', 'ip' => '192.168.1.1'],
            ['action' => __('Updated Email Address'), 'user' => __('Super Admin'), 'date' => '2023-10-20 01:30 PM', 'ip' => '192.168.1.1'],
            ['action' => __('System Backup Created'), 'user' => __('System'), 'date' => '2023-10-19 03:00 AM', 'ip' => '127.0.0.1'],
            ['action' => __('Updated Tax Settings'), 'user' => __('Super Admin'), 'date' => '2023-10-18 10:00 AM', 'ip' => '192.168.1.1'],
            ['action' => __('Changed Receipt Header'), 'user' => __('Manager'), 'date' => '2023-10-17 05:45 PM', 'ip' => '192.168.1.10'],
            ['action' => __('Updated Store Logo'), 'user' => __('Super Admin'), 'date' => '2023-10-16 09:15 AM', 'ip' => '192.168.1.1'],
            ['action' => __('Enabled Maintenance Mode'), 'user' => __('System'), 'date' => '2023-10-15 11:30 PM', 'ip' => '127.0.0.1'],
            ['action' => __('Disabled Maintenance Mode'), 'user' => __('System'), 'date' => '2023-10-15 11:45 PM', 'ip' => '127.0.0.1'],
        ];
    }

    public function save()
    {
        // Save logic here
        session()->flash('message', 'Settings saved successfully.');
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('Settings') }}</h2>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">{{ __('General') }}</a>
            <a href="{{ route('settings.payment') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">{{ __('Payment Methods') }}</a>
            <a href="{{ route('settings.receipt') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">{{ __('Receipt') }}</a>
            <a href="{{ route('settings.notifications') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">{{ __('Notifications') }}</a>
            <a href="{{ route('settings.integrations') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">{{ __('Integrations') }}</a>
        </div>

        <div class="p-6">
            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ __(session('message')) }}</span>
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Store Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Store Name') }}</label>
                            <input wire:model="storeName" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Currency') }}</label>
                            <select wire:model="currency" class="block w-full rounded-lg border-gray-300 border p-2.5">
                                @foreach($currencies as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }}</label>
                            <input wire:model="phone" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                            <input wire:model="email" type="email" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Timezone') }}</label>
                            <select class="block w-full rounded-lg border-gray-300 border p-2.5">
                                @foreach($timezones as $offset => $name)
                                    <option value="{{ $offset }}">{{ $offset }} - {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Address') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Street Address') }}</label>
                            <input wire:model="streetAddress" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('City') }}</label>
                                <input wire:model="city" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Zip Code') }}</label>
                                <input wire:model="zipCode" type="text" class="block w-full rounded-lg border-gray-300 border p-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Configuration History / Audit Log -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('Configuration History') }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm">
                        <th class="p-3 font-medium">{{ __('Action') }}</th>
                        <th class="p-3 font-medium">{{ __('User') }}</th>
                        <th class="p-3 font-medium">{{ __('Date & Time') }}</th>
                        <th class="p-3 font-medium">{{ __('IP Address') }}</th>
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
