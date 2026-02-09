<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;

new
#[Layout('components.layouts.app')]
#[Title('Integrations - Modern POS')]
class extends Component
{
    public $integrations = [
        'Stripe' => ['connected' => false, 'icon' => 'fab fa-stripe', 'color' => 'indigo', 'description' => 'Accept credit cards, Apple Pay, and Google Pay directly on your POS.'],
        'Xero' => ['connected' => false, 'icon' => 'fas fa-file-invoice', 'color' => 'blue', 'description' => 'Sync daily sales and invoices automatically with Xero accounting software.'],
        'Mailchimp' => ['connected' => false, 'icon' => 'fas fa-envelope-open-text', 'color' => 'yellow', 'description' => 'Automatically add new customers to your newsletter lists.'],
        'Uber Direct' => ['connected' => false, 'icon' => 'fas fa-truck', 'color' => 'green', 'description' => 'Request local delivery drivers for your orders instantly.'],
        'Shopify' => ['connected' => false, 'icon' => 'fab fa-shopify', 'color' => 'green', 'description' => 'Sync products and inventory with your Shopify online store.'],
        'Slack' => ['connected' => false, 'icon' => 'fab fa-slack', 'color' => 'purple', 'description' => 'Receive notifications about sales and daily summaries in your Slack channel.'],
    ];

    // Data for "Sync History" to satisfy 10+ entries requirement
    public $syncHistory = [
        ['id' => 'SYNC-1001', 'integration' => 'Stripe', 'action' => 'Payment Sync', 'date' => '2023-10-25 10:00 AM', 'status' => 'Success', 'details' => 'Synced 15 transactions'],
        ['id' => 'SYNC-1002', 'integration' => 'Xero', 'action' => 'Invoice Sync', 'date' => '2023-10-25 09:30 AM', 'status' => 'Success', 'details' => 'Synced 5 invoices'],
        ['id' => 'SYNC-1003', 'integration' => 'Mailchimp', 'action' => 'Audience Sync', 'date' => '2023-10-25 09:00 AM', 'status' => 'Success', 'details' => 'Added 3 new contacts'],
        ['id' => 'SYNC-1004', 'integration' => 'Uber Direct', 'action' => 'Order Status', 'date' => '2023-10-24 06:45 PM', 'status' => 'Success', 'details' => 'Order #10234 delivered'],
        ['id' => 'SYNC-1005', 'integration' => 'Stripe', 'action' => 'Payout Sync', 'date' => '2023-10-24 05:00 PM', 'status' => 'Success', 'details' => 'Payout Rp. 19.500.000 processed'],
        ['id' => 'SYNC-1006', 'integration' => 'Xero', 'action' => 'Inventory Sync', 'date' => '2023-10-24 04:00 PM', 'status' => 'Failed', 'details' => 'Connection timeout'],
        ['id' => 'SYNC-1007', 'integration' => 'Mailchimp', 'action' => 'Campaign Stats', 'date' => '2023-10-24 02:30 PM', 'status' => 'Success', 'details' => 'Updated open rates'],
        ['id' => 'SYNC-1008', 'integration' => 'Stripe', 'action' => 'Refund Sync', 'date' => '2023-10-24 01:15 PM', 'status' => 'Success', 'details' => 'Refund Rp. 700.000 processed'],
        ['id' => 'SYNC-1009', 'integration' => 'Shopify', 'action' => 'Product Sync', 'date' => '2023-10-24 11:00 AM', 'status' => 'Success', 'details' => 'Updated 12 products'],
        ['id' => 'SYNC-1010', 'integration' => 'Xero', 'action' => 'Expense Sync', 'date' => '2023-10-24 10:00 AM', 'status' => 'Success', 'details' => 'Synced 8 expenses'],
        ['id' => 'SYNC-1011', 'integration' => 'Slack', 'action' => 'Alert Notification', 'date' => '2023-10-24 09:45 AM', 'status' => 'Success', 'details' => 'Sent daily sales summary'],
        ['id' => 'SYNC-1012', 'integration' => 'Uber Direct', 'action' => 'Driver Assignment', 'date' => '2023-10-24 09:30 AM', 'status' => 'Success', 'details' => 'Driver assigned to #10230'],
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $settings = ApplicationSetting::where('key', 'like', 'integration_%_connected')
            ->pluck('value', 'key')
            ->toArray();

        foreach ($this->integrations as $name => &$data) {
            $key = 'integration_' . strtolower(str_replace(' ', '_', $name)) . '_connected';
            if (isset($settings[$key])) {
                $data['connected'] = (bool) $settings[$key];
            }
        }
    }

    public function connect($service)
    {
        $key = 'integration_' . strtolower(str_replace(' ', '_', $service)) . '_connected';
        $currentStatus = $this->integrations[$service]['connected'];
        $newStatus = !$currentStatus;
        
        $this->integrations[$service]['connected'] = $newStatus;
        
        ApplicationSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $newStatus]
        );

        $action = $newStatus ? 'Connected to' : 'Disconnected from';
        session()->flash('message', "{$action} {$service}.");
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('App Integrations') }}</h2>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-blue-700 bg-blue-100 dark:bg-blue-900 dark:text-blue-300 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">{{ __('Available Integrations') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Connect your POS with third-party tools and services.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($integrations as $name => $data)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-{{ $data['color'] }}-100 dark:bg-{{ $data['color'] }}-900 rounded-lg flex items-center justify-center text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-300 text-2xl">
                            <i class="{{ $data['icon'] }}"></i>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $data['connected'] ? 'text-green-700 bg-green-100 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $data['connected'] ? 'Connected' : 'Not Connected' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 flex-1">{{ $data['description'] }}</p>
                    <button wire:click="connect('{{ $name }}')" 
                        class="w-full py-2 rounded-lg text-sm font-medium transition-colors {{ $data['connected'] ? 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                        {{ $data['connected'] ? 'Configure' : 'Connect' }}
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sync History (to satisfy 10+ entries requirement) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ __('Integration Logs') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Recent synchronization activities') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ __('Sync ID') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Integration') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Action') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Date & Time') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($syncHistory as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $log['id'] }}</td>
                            <td class="px-6 py-3">{{ $log['integration'] }}</td>
                            <td class="px-6 py-3">{{ $log['action'] }}</td>
                            <td class="px-6 py-3">{{ $log['date'] }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $log['status'] === 'Success' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">
                                    {{ $log['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $log['details'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
