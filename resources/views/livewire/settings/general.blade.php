<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;
use Spatie\Activitylog\Models\Activity;

new #[Layout('components.layouts.app')] #[Title('General Settings - Modern POS')] class extends Component {
    public $storeName;
    public $currency;
    public $phone;
    public $email;
    public $streetAddress;
    public $city;
    public $zipCode;

    public $currencies = [];
    public $timezones = [];
    public $auditLogs = [];

    public function mount()
    {
        $this->loadSettings();

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
            'IDR (Rp)' => __('Indonesian Rupiah'),
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
            'UTC+07:00' => __('Bangkok, Hanoi, Jakarta'),
            'UTC+08:00' => __('Beijing, Perth, Singapore, Hong Kong'),
            'UTC+09:00' => __('Tokyo, Seoul, Osaka, Sapporo, Yakutsk'),
        ];

        // Try to load real activity logs if Spatie Activitylog is installed and table exists
        try {
            if (class_exists(Activity::class)) {
                $this->auditLogs = Activity::latest()
                    ->take(10)
                    ->get()
                    ->map(function ($activity) {
                        return [
                            'action' => $activity->description,
                            'user' => $activity->causer ? $activity->causer->name : 'System',
                            'date' => $activity->created_at->format('Y-m-d h:i A'),
                            'ip' => $activity->properties['ip'] ?? '127.0.0.1',
                        ];
                    })
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Fallback to empty or static if fails
            $this->auditLogs = [];
        }
    }

    public function loadSettings()
    {
        $settings = ApplicationSetting::pluck('value', 'key');

        $this->storeName = $settings['store_name'] ?? 'Modern POS';

        // Menggabungkan kembali 'IDR' dan 'Rp' menjadi 'IDR (Rp)'
        // Agar dropdown/select di UI bisa mencocokkan nilainya
        if (isset($settings['currency']) && isset($settings['currency_code'])) {
            $this->currency = "{$settings['currency']} ({$settings['currency_code']})";
        } else {
            $this->currency = $settings['currency'] ?? 'IDR (Rp)';
        }

        $this->phone = $settings['store_phone'] ?? '+1 234 567 890';
        $this->email = $settings['store_email'] ?? 'support@modernpos.com';
        $this->streetAddress = $settings['store_address'] ?? '123 Main St';
        $this->city = $settings['store_city'] ?? 'New York';
        $this->zipCode = $settings['store_zip'] ?? '10001';
    }

    public function save()
    {
        // Ambil key yang dipilih, misal: "IDR (Rp)"
        $selectedCurrency = $this->currency;

        // Gunakan Regex untuk mengambil teks sebelum kurung dan teks di dalam kurung
        // Hasilnya: $matches[1] = IDR, $matches[2] = Rp
        preg_match('/^(.*?)\s\((.*?)\)$/', $selectedCurrency, $matches);

        $currencyName = $matches[1] ?? $selectedCurrency; // IDR
        $currencyCode = $matches[2] ?? ''; // Rp

        $settings = [
            'store_name' => $this->storeName,
            'currency' => $currencyName, // Hasilnya: IDR
            'currency_code' => $currencyCode, // Hasilnya: Rp
            'store_phone' => $this->phone,
            'store_email' => $this->email,
            'store_address' => $this->streetAddress,
            'store_city' => $this->city,
            'store_zip' => $this->zipCode,
        ];

        foreach ($settings as $key => $value) {
            ApplicationSetting::updateOrCreate(['key' => $key, 'user_id' => auth()->id()], ['value' => $value]);
        }

        session()->flash('message', 'Settings saved successfully.');
        $this->dispatch('notify', __('Settings saved successfully.'));
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Settings') }}</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        

        <div class="p-6">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Store Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Store Name') }}</label>
                            <input wire:model="storeName" type="text"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Currency') }}</label>
                            <select wire:model="currency" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                @foreach ($currencies as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }}</label>
                            <input wire:model="phone" type="text"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                            <input wire:model="email" type="email"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Timezone') }}</label>
                            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                @foreach ($timezones as $offset => $name)
                                    <option value="{{ $offset }}">{{ $offset }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Address') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1">{{ __('Street Address') }}</label>
                            <input wire:model="streetAddress" type="text"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('City') }}</label>
                                <input wire:model="city" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Zip Code') }}</label>
                                <input wire:model="zipCode" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"><i class="fas fa-save mr-2"></i> {{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    
</div>