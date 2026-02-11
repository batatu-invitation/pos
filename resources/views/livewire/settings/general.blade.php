<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] #[Title('General Settings - Modern POS')] class extends Component {
    public $storeName;
    public $currency;
    public $phone;
    public $email;
    public $code_transaction;
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
            'IDR (Rp)' => __('Indonesian Rupiah'),
        ];

        $this->timezones = [
            'UTC+07:00' => __('WIB - Waktu Indonesia Barat (Jakarta)'),
            'UTC+08:00' => __('WITA - Waktu Indonesia Tengah (Makassar)'),
            'UTC+09:00' => __('WIT - Waktu Indonesia Timur (Jayapura)'),
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

        $this->phone = $settings['store_phone'] ?? '+62 812 3456 7890';
        $this->email = $settings['store_email'] ?? 'support@tokoanda.com';
        $this->code_transaction = $settings['code_transaction'] ?? 'TRX';
        $this->streetAddress = $settings['store_address'] ?? 'Jl. Jendral Sudirman No. 1';
        $this->city = $settings['store_city'] ?? 'Jakarta';
        $this->zipCode = $settings['store_zip'] ?? '10220';
    }

    public function save()
    {
        $user = Auth::user();

        $hasSettings = ApplicationSetting::where('user_id', $user->created_by)->exists();

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
            'code_transaction' => $this->code_transaction,
            'store_address' => $this->streetAddress,
            'store_city' => $this->city,
            'store_zip' => $this->zipCode,
        ];


        if ($hasSettings) {
            foreach ($settings as $key => $value) {
                ApplicationSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        } else {
            foreach ($settings as $key => $value) {
                ApplicationSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        session()->flash('message', 'Settings saved successfully.');
        $this->dispatch('notify', __('Settings saved successfully.'));
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('Settings') }}</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">


        <div class="p-6">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Store Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Store Name') }}</label>
                            <input wire:model="storeName" type="text"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Currency') }}</label>
                            <select wire:model="currency"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                @foreach ($currencies as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Phone') }}</label>
                            <input wire:model="phone" type="text"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                            <input wire:model="email" type="email"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Timezone') }}</label>
                            <select
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                @foreach ($timezones as $offset => $name)
                                    <option value="{{ $offset }}">{{ $offset }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Code Transaction') }}</label>
                            <input wire:model="code_transaction" type="text"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Address') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Street Address') }}</label>
                            <input wire:model="streetAddress" type="text"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('City') }}</label>
                                <input wire:model="city" type="text"
                                    class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Zip Code') }}</label>
                                <input wire:model="zipCode" type="text"
                                    class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"><i
                            class="fas fa-save mr-2"></i> {{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
