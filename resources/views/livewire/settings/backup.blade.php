<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new 
#[Layout('components.layouts.app')]
#[Title('Backup & Restore - Modern POS')]
class extends Component
{
    public $dailyBackup = true;

    // Data for "Backup History" to satisfy 10+ entries requirement
    public $backupHistory = [
        ['date' => 'Oct 24, 2023 00:00 AM', 'size' => '45.2 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 23, 2023 00:00 AM', 'size' => '44.8 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 22, 2023 14:30 PM', 'size' => '44.5 MB', 'type' => 'Manual', 'status' => 'Completed'],
        ['date' => 'Oct 22, 2023 00:00 AM', 'size' => '44.1 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 21, 2023 00:00 AM', 'size' => '43.9 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 20, 2023 11:15 AM', 'size' => '43.5 MB', 'type' => 'Manual', 'status' => 'Completed'],
        ['date' => 'Oct 20, 2023 00:00 AM', 'size' => '43.2 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 19, 2023 00:00 AM', 'size' => '42.8 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 18, 2023 00:00 AM', 'size' => '42.5 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 17, 2023 16:45 PM', 'size' => '42.1 MB', 'type' => 'Manual', 'status' => 'Failed'],
        ['date' => 'Oct 17, 2023 00:00 AM', 'size' => '42.0 MB', 'type' => 'Auto', 'status' => 'Completed'],
        ['date' => 'Oct 16, 2023 00:00 AM', 'size' => '41.6 MB', 'type' => 'Auto', 'status' => 'Completed'],
    ];

    public function createBackup()
    {
        // Simulate backup creation
        array_unshift($this->backupHistory, [
            'date' => now()->format('M d, Y h:i A'),
            'size' => '45.3 MB',
            'type' => 'Manual',
            'status' => 'Completed'
        ]);
        session()->flash('message', 'Backup created successfully.');
    }

    public function download($index)
    {
        // Simulate download
        session()->flash('message', 'Backup download started.');
    }

    public function delete($index)
    {
        // Simulate delete
        unset($this->backupHistory[$index]);
        $this->backupHistory = array_values($this->backupHistory);
        session()->flash('message', 'Backup deleted.');
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Backup & Restore</h2>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <a href="{{ route('settings.integrations') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
            <a href="{{ route('settings.api-keys') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">API Keys</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">Backup</button>
        </div>
        
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Manage Backups</h3>
                    <p class="text-gray-500 text-sm">Manage your data backups and restoration points.</p>
                </div>
                <button wire:click="createBackup" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
                    <i class="fas fa-cloud-download-alt mr-2"></i> Create Backup
                </button>
            </div>

            <!-- Auto Backup Settings -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Automatic Backup Settings</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Daily Cloud Backup</p>
                        <p class="text-xs text-gray-500">Automatically backup data to the cloud every day at midnight.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="dailyBackup" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <!-- Backup History -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Date & Time</th>
                            <th class="px-6 py-3">Size</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backupHistory as $index => $backup)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $backup['date'] }}</td>
                                <td class="px-6 py-4">{{ $backup['size'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $backup['type'] === 'Auto' ? 'text-blue-700 bg-blue-100' : 'text-purple-700 bg-purple-100' }}">
                                        {{ $backup['type'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $backup['status'] === 'Completed' ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100' }}">
                                        {{ $backup['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button wire:click="download({{ $index }})" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-download"></i></button>
                                    <button wire:click="delete({{ $index }})" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
