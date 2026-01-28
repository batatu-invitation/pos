<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] 
    #[Title('Payment Settings - Modern POS')] 
    class extends Component {
    public $paymentMethods = [
        [
            'id' => 'cash',
            'name' => 'Cash',
            'description' => 'Accept cash payments',
            'icon' => 'fa-money-bill-wave',
            'color' => 'text-green-500',
            'enabled' => true
        ],
        [
            'id' => 'card',
            'name' => 'Credit/Debit Card',
            'description' => 'Accept card payments via Stripe/Square',
            'icon' => 'fa-credit-card',
            'color' => 'text-blue-500',
            'enabled' => true
        ],
        [
            'id' => 'bank_transfer',
            'name' => 'Bank Transfer',
            'description' => 'Direct bank transfer payments',
            'icon' => 'fa-university',
            'color' => 'text-gray-500',
            'enabled' => false
        ],
        [
            'id' => 'paypal',
            'name' => 'PayPal',
            'description' => 'Accept payments via PayPal',
            'icon' => 'fa-paypal',
            'color' => 'text-blue-700',
            'enabled' => false
        ],
        [
            'id' => 'apple_pay',
            'name' => 'Apple Pay',
            'description' => 'Accept Apple Pay',
            'icon' => 'fa-apple',
            'color' => 'text-gray-900',
            'enabled' => true
        ],
        [
            'id' => 'google_pay',
            'name' => 'Google Pay',
            'description' => 'Accept Google Pay',
            'icon' => 'fa-google',
            'color' => 'text-red-500',
            'enabled' => true
        ],
        [
            'id' => 'crypto',
            'name' => 'Cryptocurrency',
            'description' => 'Accept Bitcoin, Ethereum, etc.',
            'icon' => 'fa-bitcoin',
            'color' => 'text-yellow-500',
            'enabled' => false
        ],
        [
            'id' => 'gift_card',
            'name' => 'Gift Card',
            'description' => 'Redeem store gift cards',
            'icon' => 'fa-gift',
            'color' => 'text-pink-500',
            'enabled' => true
        ],
        [
            'id' => 'cheque',
            'name' => 'Cheque',
            'description' => 'Accept paper cheques',
            'icon' => 'fa-money-check',
            'color' => 'text-indigo-500',
            'enabled' => false
        ],
        [
            'id' => 'store_credit',
            'name' => 'Store Credit',
            'description' => 'Pay using store credit balance',
            'icon' => 'fa-wallet',
            'color' => 'text-purple-500',
            'enabled' => true
        ],
        [
            'id' => 'qr_code',
            'name' => 'QR Code Payment',
            'description' => 'Scan QR code to pay',
            'icon' => 'fa-qrcode',
            'color' => 'text-gray-700',
            'enabled' => true
        ],
        [
            'id' => 'loyalty_points',
            'name' => 'Loyalty Points',
            'description' => 'Redeem loyalty points',
            'icon' => 'fa-star',
            'color' => 'text-yellow-400',
            'enabled' => true
        ]
    ];

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
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Settings</h2>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.payment') }}" class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.receipt') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.notifications') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <a href="{{ route('settings.integrations') }}" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
        </div>
        
        <div class="p-6">
            <div class="space-y-4">
                @foreach($paymentMethods as $method)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <i class="fas {{ $method['icon'] }} text-2xl {{ $method['color'] }} mr-4 w-8 text-center"></i>
                        <div>
                            <h4 class="font-bold text-gray-800">{{ $method['name'] }}</h4>
                            <p class="text-sm text-gray-500">{{ $method['description'] }}</p>
                        </div>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <button wire:click="toggleMethod('{{ $method['id'] }}')" 
                                class="relative focus:outline-none" x-data="{ toggle: @entangle('paymentMethods.' . $loop->index . '.enabled') }">
                            <div class="w-10 h-6 bg-gray-200 rounded-full shadow-inner" :class="{ 'bg-indigo-600': toggle }"></div>
                            <div class="absolute top-0 left-0 w-6 h-6 bg-white rounded-full shadow transform transition-transform duration-200 ease-in-out" :class="{ 'translate-x-4': toggle }"></div>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>