<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ApplicationSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Buglinjo\LaravelWebp\Webp;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
        $hasSettings = ApplicationSetting::where('user_id', Auth::id())->exists();
        if ($hasSettings) {
            return redirect()->route('dashboard');
        }

        $this->currencies = [
            'IDR (Rp)' => __('Indonesian Rupiah'),
        ];

        $this->timezones = [
            'UTC+07:00' => __('WIB - Waktu Indonesia Barat (Jakarta)'),
            'UTC+08:00' => __('WITA - Waktu Indonesia Tengah (Makassar)'),
            'UTC+09:00' => __('WIT - Waktu Indonesia Timur (Jayapura)'),
        ];

        $this->receiptHeader = "Toko Modern\nJl. Jendral Sudirman No. 1, Jakarta\nTel: +62 812 3456 7890";
        $this->receiptFooter = "Terima kasih telah berbelanja!\nSilakan datang kembali.";
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
                ['key' => $key],
                ['user_id' => Auth::id()],
                ['value' => $value]
            );
        }

        // Seed Default Emojis
        $defaultEmojis = [
            ['icon' => '📦', 'name' => 'Box'],
            ['icon' => '🍔', 'name' => 'Burger'],
            ['icon' => '🥤', 'name' => 'Drink'],
            ['icon' => '👕', 'name' => 'Shirt'],
            ['icon' => '🏠', 'name' => 'Home'],
            ['icon' => '🛒', 'name' => 'Cart'],
            ['icon' => '💊', 'name' => 'Pill'],
            ['icon' => '📱', 'name' => 'Mobile'],
        ];

        foreach ($defaultEmojis as $emoji) {
            \App\Models\Emoji::create($emoji);
        }

        // Seed Default Colors
        $defaultColors = [
            ['name' => 'Red', 'class' => 'bg-red-500'],
            ['name' => 'Blue', 'class' => 'bg-blue-500'],
            ['name' => 'Green', 'class' => 'bg-green-500'],
            ['name' => 'Yellow', 'class' => 'bg-yellow-500'],
            ['name' => 'Purple', 'class' => 'bg-purple-500'],
            ['name' => 'Orange', 'class' => 'bg-orange-500'],
            ['name' => 'Pink', 'class' => 'bg-pink-500'],
            ['name' => 'Gray', 'class' => 'bg-gray-500'],
        ];

        foreach ($defaultColors as $color) {
            \App\Models\Color::create($color);
        }

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.setup');
    }
}
