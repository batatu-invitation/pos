<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new 
#[Layout('components.layouts.app')]
#[Title('Receipt Settings - Modern POS')]
class extends Component
{
    public $header = "Modern POS\n123 Main St, New York\nTel: +1 234 567 890";
    public $footer = "Thank you for shopping with us!\nPlease come again.";
    public $showLogo = true;

    // Data for "Print History" to satisfy 10+ entries requirement
    public $printHistory = [
        ['id' => 'RCP-10234', 'date' => '2023-10-25 10:45 AM', 'cashier' => 'John Doe', 'amount' => '$125.50', 'status' => 'Printed'],
        ['id' => 'RCP-10233', 'date' => '2023-10-25 10:30 AM', 'cashier' => 'Jane Smith', 'amount' => '$45.00', 'status' => 'Printed'],
        ['id' => 'RCP-10232', 'date' => '2023-10-25 10:15 AM', 'cashier' => 'John Doe', 'amount' => '$89.99', 'status' => 'Printed'],
        ['id' => 'RCP-10231', 'date' => '2023-10-25 10:00 AM', 'cashier' => 'Jane Smith', 'amount' => '$12.50', 'status' => 'Printed'],
        ['id' => 'RCP-10230', 'date' => '2023-10-25 09:45 AM', 'cashier' => 'John Doe', 'amount' => '$210.00', 'status' => 'Printed'],
        ['id' => 'RCP-10229', 'date' => '2023-10-25 09:30 AM', 'cashier' => 'Jane Smith', 'amount' => '$34.20', 'status' => 'Failed'],
        ['id' => 'RCP-10228', 'date' => '2023-10-25 09:15 AM', 'cashier' => 'John Doe', 'amount' => '$15.00', 'status' => 'Printed'],
        ['id' => 'RCP-10227', 'date' => '2023-10-25 09:00 AM', 'cashier' => 'Jane Smith', 'amount' => '$67.80', 'status' => 'Printed'],
        ['id' => 'RCP-10226', 'date' => '2023-10-24 05:45 PM', 'cashier' => 'Mike Ross', 'amount' => '$99.99', 'status' => 'Printed'],
        ['id' => 'RCP-10225', 'date' => '2023-10-24 05:30 PM', 'cashier' => 'Mike Ross', 'amount' => '$45.50', 'status' => 'Printed'],
        ['id' => 'RCP-10224', 'date' => '2023-10-24 05:15 PM', 'cashier' => 'Mike Ross', 'amount' => '$23.00', 'status' => 'Printed'],
        ['id' => 'RCP-10223', 'date' => '2023-10-24 05:00 PM', 'cashier' => 'Mike Ross', 'amount' => '$112.00', 'status' => 'Printed'],
    ];

    public function save()
    {
        // Simulate save
        session()->flash('message', 'Receipt settings saved successfully.');
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">Receipt</button>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
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

            <form wire:submit="save" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Header</label>
                    <textarea wire:model="header" class="block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-indigo-500 focus:border-indigo-500" rows="3"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Footer</label>
                    <textarea wire:model="footer" class="block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-indigo-500 focus:border-indigo-500" rows="3"></textarea>
                </div>

                 <div class="flex items-center">
                    <input id="show-logo" type="checkbox" wire:model="showLogo" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="show-logo" class="ml-2 block text-sm text-gray-900">Show Logo on Receipt</label>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Print History Section (to satisfy 10+ entries requirement) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Recent Print Jobs</h3>
            <p class="text-sm text-gray-500">History of recently printed receipts</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Receipt ID</th>
                        <th class="px-6 py-3 font-medium">Date & Time</th>
                        <th class="px-6 py-3 font-medium">Cashier</th>
                        <th class="px-6 py-3 font-medium">Amount</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($printHistory as $job)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $job['id'] }}</td>
                            <td class="px-6 py-3">{{ $job['date'] }}</td>
                            <td class="px-6 py-3">{{ $job['cashier'] }}</td>
                            <td class="px-6 py-3">{{ $job['amount'] }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $job['status'] === 'Printed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $job['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
