<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new 
#[Layout('components.layouts.app')]
#[Title('Integrations - Modern POS')]
class extends Component
{
    // Data for "Sync History" to satisfy 10+ entries requirement
    public $syncHistory = [
        ['id' => 'SYNC-1001', 'integration' => 'Stripe', 'action' => 'Payment Sync', 'date' => '2023-10-25 10:00 AM', 'status' => 'Success', 'details' => 'Synced 15 transactions'],
        ['id' => 'SYNC-1002', 'integration' => 'Xero', 'action' => 'Invoice Sync', 'date' => '2023-10-25 09:30 AM', 'status' => 'Success', 'details' => 'Synced 5 invoices'],
        ['id' => 'SYNC-1003', 'integration' => 'Mailchimp', 'action' => 'Audience Sync', 'date' => '2023-10-25 09:00 AM', 'status' => 'Success', 'details' => 'Added 3 new contacts'],
        ['id' => 'SYNC-1004', 'integration' => 'Uber Direct', 'action' => 'Order Status', 'date' => '2023-10-24 06:45 PM', 'status' => 'Success', 'details' => 'Order #10234 delivered'],
        ['id' => 'SYNC-1005', 'integration' => 'Stripe', 'action' => 'Payout Sync', 'date' => '2023-10-24 05:00 PM', 'status' => 'Success', 'details' => 'Payout $1,250.00 processed'],
        ['id' => 'SYNC-1006', 'integration' => 'Xero', 'action' => 'Inventory Sync', 'date' => '2023-10-24 04:00 PM', 'status' => 'Failed', 'details' => 'Connection timeout'],
        ['id' => 'SYNC-1007', 'integration' => 'Mailchimp', 'action' => 'Campaign Stats', 'date' => '2023-10-24 02:30 PM', 'status' => 'Success', 'details' => 'Updated open rates'],
        ['id' => 'SYNC-1008', 'integration' => 'Stripe', 'action' => 'Refund Sync', 'date' => '2023-10-24 01:15 PM', 'status' => 'Success', 'details' => 'Refund $45.00 processed'],
        ['id' => 'SYNC-1009', 'integration' => 'Shopify', 'action' => 'Product Sync', 'date' => '2023-10-24 11:00 AM', 'status' => 'Success', 'details' => 'Updated 12 products'],
        ['id' => 'SYNC-1010', 'integration' => 'Xero', 'action' => 'Expense Sync', 'date' => '2023-10-24 10:00 AM', 'status' => 'Success', 'details' => 'Synced 8 expenses'],
        ['id' => 'SYNC-1011', 'integration' => 'Slack', 'action' => 'Alert Notification', 'date' => '2023-10-24 09:45 AM', 'status' => 'Success', 'details' => 'Sent daily sales summary'],
        ['id' => 'SYNC-1012', 'integration' => 'Uber Direct', 'action' => 'Driver Assignment', 'date' => '2023-10-24 09:30 AM', 'status' => 'Success', 'details' => 'Driver assigned to #10230'],
    ];

    public function connect($service)
    {
        // Simulate connection
        session()->flash('message', "Connecting to {$service}...");
    }
}; ?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">App Integrations</h2>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">Integrations</button>
            <a href="{{ route('settings.api-keys') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">API Keys</a>
            <a href="{{ route('settings.backup') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Backup</a>
        </div>
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-blue-700 bg-blue-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Available Integrations</h3>
                <p class="text-gray-500 text-sm">Connect your POS with third-party tools and services.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Stripe -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-2xl">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Connected</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Stripe</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">Accept credit cards, Apple Pay, and Google Pay directly on your POS.</p>
                    <button class="w-full py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Configure</button>
                </div>

                <!-- Xero -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-2xl">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-100 rounded-full">Not Connected</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Xero</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">Sync daily sales and invoices automatically with Xero accounting software.</p>
                    <button wire:click="connect('Xero')" class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Connect</button>
                </div>

                <!-- Mailchimp -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600 text-2xl">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-100 rounded-full">Not Connected</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Mailchimp</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">Automatically add new customers to your newsletter lists.</p>
                    <button wire:click="connect('Mailchimp')" class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Connect</button>
                </div>

                <!-- Uber Direct -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-2xl">
                            <i class="fas fa-truck"></i>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-100 rounded-full">Not Connected</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Uber Direct</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">Request local delivery drivers for your orders instantly.</p>
                    <button wire:click="connect('Uber Direct')" class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Connect</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync History (to satisfy 10+ entries requirement) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Integration Logs</h3>
            <p class="text-sm text-gray-500">Recent synchronization activities</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Sync ID</th>
                        <th class="px-6 py-3 font-medium">Integration</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                        <th class="px-6 py-3 font-medium">Date & Time</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($syncHistory as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $log['id'] }}</td>
                            <td class="px-6 py-3">{{ $log['integration'] }}</td>
                            <td class="px-6 py-3">{{ $log['action'] }}</td>
                            <td class="px-6 py-3">{{ $log['date'] }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $log['status'] === 'Success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $log['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $log['details'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
