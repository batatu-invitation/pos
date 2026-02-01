<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Backup & Restore - Modern POS')]
class extends Component
{
    public $dailyBackup;
    public $backupHistory = [];

    public function mount()
    {
        $settings = ApplicationSetting::pluck('value', 'key');
        $this->dailyBackup = filter_var($settings['backup_daily'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->loadBackups();
    }

    public function loadBackups()
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name');

        try {
            $backupDestination = BackupDestination::create($diskName, $backupName);

            $this->backupHistory = $backupDestination->backups()->map(function ($backup) {
                return [
                    'path' => $backup->path(),
                    'date' => $backup->date()->format('M d, Y h:i A'),
                    'size' => $this->formatSize($backup->sizeInBytes()),
                    'type' => 'Manual', // Defaulting to Manual as we can't easily distinguish without metadata
                    'status' => 'Completed',
                    'disk' => config('backup.backup.destination.disks')[0] ?? 'local',
                ];
            })->sortByDesc('date')->values()->toArray();
        } catch (\Exception $e) {
            $this->backupHistory = [];
        }
    }

    public function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    public function updatedDailyBackup($value)
    {
        ApplicationSetting::updateOrCreate(['key' => 'backup_daily'], ['value' => $value]);
        session()->flash('message', 'Backup settings updated.');
    }

    public function createBackup()
    {
        try {
            // Run backup command (only DB to prevent timeout on web request)
            Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

            $this->loadBackups();
            session()->flash('message', 'Backup created successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($index)
    {
        if (isset($this->backupHistory[$index])) {
            $backup = $this->backupHistory[$index];
            return Storage::disk($backup['disk'])->download($backup['path']);
        }
    }

    public function delete($index)
    {
        if (isset($this->backupHistory[$index])) {
            $backup = $this->backupHistory[$index];
            Storage::disk($backup['disk'])->delete($backup['path']);

            $this->loadBackups();
            session()->flash('message', 'Backup deleted.');
        }
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
            @if (session()->has('error'))
                <div class="mb-4 p-4 text-red-700 bg-red-100 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Manage Backups</h3>
                    <p class="text-gray-500 text-sm">Manage your data backups and restoration points.</p>
                </div>
                <button wire:click="createBackup" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center disabled:opacity-50">
                    <i class="fas fa-cloud-download-alt mr-2" wire:loading.remove></i>
                    <i class="fas fa-spinner fa-spin mr-2" wire:loading></i>
                    <span wire:loading.remove>Create Backup</span>
                    <span wire:loading>Creating...</span>
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
                        <input type="checkbox" wire:model.live="dailyBackup" class="sr-only peer">
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
                        @forelse($backupHistory as $index => $backup)
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
                                    <button wire:click="delete({{ $index }})" wire:confirm="Are you sure you want to delete this backup?" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No backups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
