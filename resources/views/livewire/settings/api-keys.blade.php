<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new 
#[Layout('components.layouts.app')]
#[Title('API Keys - Modern POS')]
class extends Component
{
    // Data for "API Keys" to satisfy 10+ entries requirement
    public $apiKeys = [
        ['name' => 'Mobile App', 'prefix' => 'pk_live_...4a2b', 'created' => 'Oct 15, 2023', 'last_used' => '2 mins ago', 'status' => 'Active'],
        ['name' => 'Website Widget', 'prefix' => 'pk_live_...9x8y', 'created' => 'Sep 20, 2023', 'last_used' => '1 hour ago', 'status' => 'Active'],
        ['name' => 'Old Integration', 'prefix' => 'pk_test_...1z2w', 'created' => 'Jan 10, 2023', 'last_used' => 'Never', 'status' => 'Revoked'],
        ['name' => 'Inventory Scanner', 'prefix' => 'pk_live_...7m3n', 'created' => 'Nov 01, 2023', 'last_used' => '5 mins ago', 'status' => 'Active'],
        ['name' => 'Customer Portal', 'prefix' => 'pk_live_...2k9l', 'created' => 'Aug 15, 2023', 'last_used' => '3 hours ago', 'status' => 'Active'],
        ['name' => 'Accounting Sync', 'prefix' => 'sk_live_...5p4o', 'created' => 'Jul 22, 2023', 'last_used' => '1 day ago', 'status' => 'Active'],
        ['name' => 'Test Server 1', 'prefix' => 'pk_test_...8q6r', 'created' => 'Oct 05, 2023', 'last_used' => '2 days ago', 'status' => 'Active'],
        ['name' => 'Test Server 2', 'prefix' => 'pk_test_...3s1t', 'created' => 'Oct 05, 2023', 'last_used' => 'Never', 'status' => 'Active'],
        ['name' => 'Delivery Partner', 'prefix' => 'sk_live_...9u2v', 'created' => 'Jun 30, 2023', 'last_used' => '10 mins ago', 'status' => 'Active'],
        ['name' => 'Loyalty Program', 'prefix' => 'pk_live_...4w7x', 'created' => 'May 12, 2023', 'last_used' => '4 hours ago', 'status' => 'Active'],
        ['name' => 'Legacy App V1', 'prefix' => 'pk_live_...6y5z', 'created' => 'Jan 01, 2022', 'last_used' => 'Never', 'status' => 'Revoked'],
        ['name' => 'Data Warehouse', 'prefix' => 'sk_live_...1a3b', 'created' => 'Sep 01, 2023', 'last_used' => '12 hours ago', 'status' => 'Active'],
    ];

    public function generateKey()
    {
        // Simulate generation
        session()->flash('message', 'New API key generated successfully.');
    }

    public function revoke($index)
    {
        // Simulate revoke
        $this->apiKeys[$index]['status'] = 'Revoked';
        session()->flash('message', 'API key revoked.');
    }
}; ?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Developer API Keys</h2>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <a href="{{ route('settings.integrations') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">API Keys</button>
            <a href="{{ route('settings.backup') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Backup</a>
        </div>
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Manage Keys</h3>
                    <p class="text-gray-500 text-sm">Manage API keys for external applications.</p>
                </div>
                <button wire:click="generateKey" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
                    <i class="fas fa-plus mr-2"></i> Generate New Key
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Key Prefix</th>
                            <th class="px-6 py-3">Created</th>
                            <th class="px-6 py-3">Last Used</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apiKeys as $index => $key)
                            <tr class="bg-white border-b hover:bg-gray-50 {{ $key['status'] === 'Revoked' ? 'opacity-60' : '' }}">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $key['name'] }}</td>
                                <td class="px-6 py-4 font-mono">{{ $key['prefix'] }}</td>
                                <td class="px-6 py-4">{{ $key['created'] }}</td>
                                <td class="px-6 py-4">{{ $key['last_used'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $key['status'] === 'Active' ? 'text-green-700 bg-green-100' : 'text-gray-700 bg-gray-100' }}">
                                        {{ $key['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($key['status'] === 'Active')
                                        <button wire:click="revoke({{ $index }})" class="text-red-600 hover:text-red-900 text-sm font-medium">Revoke</button>
                                    @else
                                        <button class="text-gray-400 cursor-not-allowed text-sm font-medium" disabled>Revoked</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
