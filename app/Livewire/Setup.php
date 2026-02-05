<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ApplicationSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Buglinjo\LaravelWebp\Webp;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.setup')]
#[Title('Welcome Setup')]
class Setup extends Component
{
    use WithFileUploads;

    public $step = 1;

    // Store Info
    public $storeName;
    public $currency = 'IDR (Rp)';
    public $phone;
    public $email;
    public $timezone = 'UTC+07:00';

    // Address
    public $streetAddress;
    public $city;
    public $zipCode;

    // Receipt
    public $receiptHeader;
    public $receiptFooter;
    public $receiptShowLogo = true;
    public $logo;

    public $currencies = [];
    public $timezones = [];

    public function mount()
    {
        // Safety check: if already setup, redirect
        $hasSettings = ApplicationSetting::where('user_id', auth()->id())->exists();
        if ($hasSettings) {
            return redirect()->route('dashboard');
        }

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

        $this->receiptHeader = "Modern POS\n123 Main St, New York\nTel: +1 234 567 890";
        $this->receiptFooter = "Thank you for shopping with us!\nPlease come again.";
    }

    public function nextStep()
    {
        if ($this->step === 2) {
            $this->validate([
                'storeName' => 'required|min:3',
                'email' => 'required|email',
                'phone' => 'required',
                'currency' => 'required',
                'timezone' => 'required',
            ]);
        } elseif ($this->step === 3) {
            $this->validate([
                'streetAddress' => 'required',
                'city' => 'required',
                'zipCode' => 'required',
            ]);
        }

        $this->step++;
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function finish()
    {
        $this->validate([
            'receiptHeader' => 'nullable|string',
            'receiptFooter' => 'nullable|string',
            'receiptShowLogo' => 'boolean',
            'logo' => 'nullable|image|max:5048', // 5MB Max
        ]);

        $logoPath = null;
        if ($this->logo) {
            $filename = md5($this->logo->getClientOriginalName() . time()) . '.webp';
            $path = storage_path('app/public/receipt-logos');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            Webp::make($this->logo)->save($path . '/' . $filename);
            $logoPath = 'receipt-logos/' . $filename;
        }

        // Ambil key yang dipilih, misal: "IDR (Rp)"
        $selectedCurrency = $this->currency;

        // Gunakan Regex untuk mengambil teks sebelum kurung dan teks di dalam kurung
        // Hasilnya: $matches[1] = IDR, $matches[2] = Rp
        preg_match('/^(.*?)\s\((.*?)\)$/', $selectedCurrency, $matches);

        $currencyName = $matches[1] ?? $selectedCurrency; // IDR
        $currencyCode = $matches[2] ?? '';                // Rp

        $settings = [
            'store_name' => $this->storeName,
            'currency'      => $currencyName, // Hasilnya: IDR
            'currency_code' => $currencyCode, // Hasilnya: Rp
            'store_phone' => $this->phone,
            'store_email' => $this->email,
            'timezone' => $this->timezone,
            'store_address' => $this->streetAddress,
            'store_city' => $this->city,
            'store_zip' => $this->zipCode,
            'receipt_header' => $this->receiptHeader,
            'receipt_footer' => $this->receiptFooter,
            'receipt_show_logo' => $this->receiptShowLogo,
        ];

        if ($logoPath) {
            $settings['receipt_logo_path'] = $logoPath;
        }

        foreach ($settings as $key => $value) {
            ApplicationSetting::updateOrCreate(
                ['user_id' => auth()->id(), 'key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.setup');
    }
}
