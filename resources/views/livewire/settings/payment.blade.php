s<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;

new #[Layout('components.layouts.app')]
    #[Title('Payment Settings - Modern POS')]
    class extends Component {
    public $paymentMethods = [];

    public function mount()
    {
        $this->paymentMethods = [
            [
                'id' => 'cash',
                'name' => __('Cash'),
                'description' => __('Accept cash payments'),
                'icon' => 'fa-money-bill-wave',
                'color' => 'text-green-500',
                'enabled' => true
            ],
            [
                'id' => 'card',
                'name' => __('Credit/Debit Card'),
                'description' => __('Accept card payments via Stripe/Square'),
                'icon' => 'fa-credit-card',
                'color' => 'text-blue-500',
                'enabled' => true
            ],
            [
                'id' => 'bank_transfer',
                'name' => __('Bank Transfer'),
                'description' => __('Direct bank transfer payments'),
                'icon' => 'fa-university',
                'color' => 'text-gray-500',
                'enabled' => false
            ],
            [
                'id' => 'paypal',
                'name' => __('PayPal'),
                'description' => __('Accept payments via PayPal'),
                'icon' => 'fa-paypal',
                'color' => 'text-blue-700',
                'enabled' => false
            ],
            [
                'id' => 'apple_pay',
                'name' => __('Apple Pay'),
                'description' => __('Accept Apple Pay'),
                'icon' => 'fa-apple',
                'color' => 'text-gray-900',
                'enabled' => true
            ],
            [
                'id' => 'google_pay',
                'name' => __('Google Pay'),
                'description' => __('Accept Google Pay'),
                'icon' => 'fa-google',
                'color' => 'text-red-500',
                'enabled' => true
            ],
            [
                'id' => 'crypto',
                'name' => __('Cryptocurrency'),
                'description' => __('Accept Bitcoin, Ethereum, etc.'),
                'icon' => 'fa-bitcoin',
                'color' => 'text-yellow-500',
                'enabled' => false
            ],
            [
                'id' => 'gift_card',
                'name' => __('Gift Card'),
                'description' => __('Redeem store gift cards'),
                'icon' => 'fa-gift',
                'color' => 'text-pink-500',
                'enabled' => true
            ],
            [
                'id' => 'cheque',
                'name' => __('Cheque'),
                'description' => __('Accept paper cheques'),
                'icon' => 'fa-money-check',
                'color' => 'text-indigo-500',
                'enabled' => false
            ],
            [
                'id' => 'store_credit',
                'name' => __('Store Credit'),
                'description' => __('Pay using store credit balance'),
                'icon' => 'fa-wallet',
                'color' => 'text-purple-500',
                'enabled' => true
            ],
            [
                'id' => 'qr_code',
                'name' => __('QR Code Payment'),
                'description' => __('Scan QR code to pay'),
                'icon' => 'fa-qrcode',
                'color' => 'text-gray-700',
                'enabled' => true
            ],
            [
                'id' => 'loyalty_points',
                'name' => __('Loyalty Points'),
                'description' => __('Redeem loyalty points'),
                'icon' => 'fa-star',
                'color' => 'text-yellow-400',
                'enabled' => true
            ]
        ];
    }

    public function toggleMethod($id)
    {
        foreach ($this->paymentMethods as &$method) {
            if ($method['id'] === $id) {
                $method['enabled'] = !$method['enabled'];
                break;
            }
        }
        // Save logic here
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('Payment Settings') }}</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('General') }}</a>
            <button class="px-6 py-3 text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400 font-medium text-sm whitespace-nowrap">{{ __('Payment Methods') }}</button>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('Receipt') }}</a>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('Notifications') }}</a>
            <a href="{{ route('settings.integrations') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('Integrations') }}</a>
            <a href="{{ route('settings.api-keys') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('API Keys') }}</a>
            <a href="{{ route('settings.backup') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('Backup') }}</a>
            <a href="{{ route('settings.taxes') }}" wire:navigate class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium text-sm whitespace-nowrap">{{ __('Taxes') }}</a>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                @foreach($paymentMethods as $method)
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center">
                        <i class="fas {{ $method['icon'] }} text-2xl {{ $method['color'] }} mr-4 w-8 text-center"></i>
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white">{{ $method['name'] }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $method['description'] }}</p>
                        </div>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <button wire:click="toggleMethod('{{ $method['id'] }}')"
                                class="relative focus:outline-none" x-data="{ toggle: @entangle('paymentMethods.' . $loop->index . '.enabled') }">
                            <div class="w-10 h-6 bg-gray-200 dark:bg-gray-600 rounded-full shadow-inner" :class="{ 'bg-indigo-600': toggle }"></div>
                            <div class="absolute top-0 left-0 w-6 h-6 bg-white rounded-full shadow transform transition-transform duration-200 ease-in-out" :class="{ 'translate-x-4': toggle }"></div>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
