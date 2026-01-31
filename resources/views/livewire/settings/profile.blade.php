<?php

use App\Models\ApplicationSetting;
use Spatie\Activitylog\Models\Activity;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Profile Settings - Modern POS')]
class extends Component
{
    public $activeTab = 'profile';

    // Profile Data
    public $name = 'Admin User';
    public $email = 'admin@pos.com';
    public $current_password = '';
    public $new_password = '';

    // Settings Data
    public $settings = [];

    public $activities = [];

    public function mount()
    {
        $this->loadSettings();
        $this->loadActivities();
    }

    public function loadActivities()
    {
        $this->activities = Activity::where('causer_id', auth()->id())
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($activity) {
                $action = $activity->description;

                if ($activity->subject_type === 'App\Models\User' && $activity->description === 'updated') {
                    $action = 'Updated profile';
                }

                return [
                    'action' => ucfirst($action),
                    'date' => $activity->created_at->format('Y-m-d h:i A'),
                    'ip' => $activity->getExtraProperty('ip') ?? 'System',
                    'device' => $activity->getExtraProperty('device') ?? 'Server',
                ];
            })->toArray();
    }

    public function loadSettings()
    {
        $dbSettings = ApplicationSetting::where('user_id', auth()->id())->pluck('value', 'key')->toArray();

        $defaultSettings = [
            'company_name' => 'My POS Store',
            'company_address' => '123 Main St, City, Country',
            'company_phone' => '+1 234 567 890',
            'receipt_header' => 'Thank you for shopping with us!',
            'receipt_footer' => 'Visit us again soon.',
            'currency_symbol' => 'Rp.',
            'code_invoice' => 'INV-',
        ];

        $this->settings = array_merge($defaultSettings, $dbSettings);
    }

    public function saveProfile()
    {
        // Placeholder for profile save logic
        $this->dispatch('notify', 'Profile updated successfully!');
    }

    public function saveSettings()
    {
        foreach ($this->settings as $key => $value) {
            ApplicationSetting::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'key' => $key
                ],
                ['value' => $value]
            );
        }

        $this->dispatch('notify', 'Settings saved successfully!');
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6" x-data="{ activeTab: @entangle('activeTab') }">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Settings</h2>

        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'"
                    :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-user mr-2"></i> Profile
                </button>
                <button @click="activeTab = 'activity'"
                    :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'activity', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'activity' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-history mr-2"></i> Recent Activity
                </button>
                <button @click="activeTab = 'settings'"
                    :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'settings', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'settings' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-cogs mr-2"></i> Application Settings
                </button>
                <button @click="activeTab = 'receipt'"
                    :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'receipt', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'receipt' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-receipt mr-2"></i> Receipt Preview
                </button>
            </nav>
        </div>

        <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
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

                <form wire:submit="saveProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" wire:model="name" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="email" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>

                        <div class="col-span-2 border-t border-gray-100 pt-6 mt-2">
                            <h4 class="font-medium text-gray-900 mb-4">Change Password</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" wire:model="current_password" class="block w-full rounded-lg border-gray-300 border p-2.5">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" wire:model="new_password" class="block w-full rounded-lg border-gray-300 border p-2.5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activity Tab -->
        <div x-show="activeTab === 'activity'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;">
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

        <!-- Application Settings Tab -->
        <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Application Settings</h3>
                <form wire:submit="saveSettings" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <h4 class="font-medium text-gray-900 mb-3 pb-2 border-b">General Info</h4>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                            <input type="text" wire:model="settings.company_name" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" wire:model="settings.company_phone" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea wire:model="settings.company_address" rows="2" class="block w-full rounded-lg border-gray-300 border p-2.5"></textarea>
                        </div>

                        <div class="col-span-2 mt-4">
                            <h4 class="font-medium text-gray-900 mb-3 pb-2 border-b">Receipt Settings</h4>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Header</label>
                            <textarea wire:model="settings.receipt_header" rows="2" class="block w-full rounded-lg border-gray-300 border p-2.5" placeholder="Text to appear at the top of the receipt"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Footer</label>
                            <textarea wire:model="settings.receipt_footer" rows="2" class="block w-full rounded-lg border-gray-300 border p-2.5" placeholder="Text to appear at the bottom of the receipt"></textarea>
                        </div>

                        <div class="col-span-2 mt-4">
                            <h4 class="font-medium text-gray-900 mb-3 pb-2 border-b">System Settings</h4>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                            <input type="text" wire:model="settings.currency_symbol" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Code</label>
                            <input type="text" wire:model="settings.code_invoice" class="block w-full rounded-lg border-gray-300 border p-2.5">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <span wire:loading.remove target="saveSettings">Save Settings</span>
                            <span wire:loading target="saveSettings"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Receipt Preview Tab -->
        <div x-show="activeTab === 'receipt'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Receipt Preview</h3>
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Receipt visual representation -->
                    <div class="w-full md:w-80 bg-white border border-gray-300 shadow-lg p-4 mx-auto md:mx-0 font-mono text-sm">
                        <div class="text-center mb-4">
                            <h2 class="font-bold text-xl">{{ $settings['company_name'] ?? 'Store Name' }}</h2>
                            <p>{{ $settings['company_address'] ?? 'Store Address' }}</p>
                            <p>{{ $settings['company_phone'] ?? 'Phone Number' }}</p>
                        </div>

                        <div class="text-center mb-4 border-b border-dashed border-gray-400 pb-2">
                            <p class="font-bold">{{ $settings['receipt_header'] ?? '' }}</p>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span>Date:</span>
                                <span>{{ now()->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Receipt #:</span>
                                <span>REC-00123</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Cashier:</span>
                                <span>Admin User</span>
                            </div>
                        </div>

                        <div class="border-b border-dashed border-gray-400 mb-2"></div>

                        <div class="mb-4">
                            <div class="flex justify-between font-bold mb-1">
                                <span>Item</span>
                                <span>Total</span>
                            </div>
                            <!-- Sample Items -->
                            <div class="flex justify-between mb-1">
                                <span>1 x Sample Product A</span>
                                <span>Rp. 50.000</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>2 x Sample Product B</span>
                                <span>Rp. 30.000</span>
                            </div>
                        </div>

                        <div class="border-b border-dashed border-gray-400 mb-2"></div>

                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span>Subtotal:</span>
                                <span>Rp. 80.000</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Tax ({{ $settings['tax_rate'] ?? 10 }}%):</span>
                                <span>Rp. 8.000</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg mt-2">
                                <span>Total:</span>
                                <span>Rp. 88.000</span>
                            </div>
                        </div>

                        <div class="text-center mt-6 border-t border-dashed border-gray-400 pt-2">
                            <p>{{ $settings['receipt_footer'] ?? 'Thank you for your visit!' }}</p>
                            <div class="mt-4">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=REC-00123" class="w-24 h-24 mx-auto" alt="QR">
                            </div>
                        </div>
                    </div>

                    <!-- Controls/Info -->
                    <div class="flex-1">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-blue-800">
                            <h4 class="font-bold mb-2"><i class="fas fa-info-circle mr-2"></i>Preview Mode</h4>
                            <p class="text-sm">This is a live preview of how your receipts will look based on the settings configured in the "Application Settings" tab. Adjust the settings to see changes reflected here.</p>
                        </div>

                        <div class="mt-6">
                            <button type="button" class="w-full md:w-auto px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors">
                                <i class="fas fa-print mr-2"></i> Test Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
