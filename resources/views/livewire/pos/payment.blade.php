<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.pos')]
#[Title('Payment - Modern POS')]
class extends Component {
    public $orderItems = [
        ['name' => 'Classic Burger', 'quantity' => 2, 'price' => 12.00],
        ['name' => 'Caramel Latte', 'quantity' => 1, 'price' => 4.50],
        ['name' => 'Glazed Donut', 'quantity' => 3, 'price' => 2.50],
        ['name' => 'French Fries', 'quantity' => 2, 'price' => 3.50],
        ['name' => 'Cola Can 330ml', 'quantity' => 2, 'price' => 1.50],
        ['name' => 'Chicken Nuggets', 'quantity' => 1, 'price' => 5.50],
        ['name' => 'Ice Cream Cone', 'quantity' => 2, 'price' => 2.00],
        ['name' => 'Mineral Water', 'quantity' => 1, 'price' => 1.00],
        ['name' => 'Onion Rings', 'quantity' => 1, 'price' => 3.00],
        ['name' => 'Caesar Salad', 'quantity' => 1, 'price' => 6.50],
        ['name' => 'Cappuccino', 'quantity' => 1, 'price' => 4.00],
        ['name' => 'Muffin Blueberry', 'quantity' => 2, 'price' => 3.00],
    ];

    public $paymentMethods = [
        ['id' => 'cash', 'name' => 'Cash', 'icon' => 'fa-money-bill-wave', 'color' => 'indigo'],
        ['id' => 'card', 'name' => 'Credit/Debit Card', 'icon' => 'fa-credit-card', 'color' => 'gray'],
        ['id' => 'qr', 'name' => 'QR Code', 'icon' => 'fa-qrcode', 'color' => 'gray'],
        ['id' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'fa-wallet', 'color' => 'gray'],
        ['id' => 'voucher', 'name' => 'Voucher / Gift Card', 'icon' => 'fa-gift', 'color' => 'gray'],
        ['id' => 'split', 'name' => 'Split Bill', 'icon' => 'fa-columns', 'color' => 'gray'],
        ['id' => 'credit', 'name' => 'Store Credit', 'icon' => 'fa-coins', 'color' => 'gray'],
        ['id' => 'cheque', 'name' => 'Cheque', 'icon' => 'fa-money-check', 'color' => 'gray'],
        ['id' => 'transfer', 'name' => 'Bank Transfer', 'icon' => 'fa-university', 'color' => 'gray'],
        ['id' => 'apple', 'name' => 'Apple Pay', 'icon' => 'fa-apple', 'color' => 'gray'],
        ['id' => 'google', 'name' => 'Google Pay', 'icon' => 'fa-google', 'color' => 'gray'],
        ['id' => 'crypto', 'name' => 'Cryptocurrency', 'icon' => 'fa-bitcoin', 'color' => 'gray'],
    ];

    public function getTotalProperty()
    {
        return collect($this->orderItems)->sum(fn($item) => $item['quantity'] * $item['price']);
    }

    public function getTaxProperty()
    {
        return $this->total * 0.1;
    }

    public function getGrandTotalProperty()
    {
        return $this->total + $this->tax;
    }
};
?>

<div class="h-full w-full flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row h-[80vh]">

        <!-- Left: Order Summary -->
        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-200 p-6 flex flex-col">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>

            <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                @foreach($orderItems as $item)
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $item['quantity'] }} x ${{ number_format($item['price'], 2) }}</p>
                    </div>
                    <span class="font-bold text-gray-800">${{ number_format($item['quantity'] * $item['price'], 2) }}</span>
                </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>${{ number_format($this->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Tax (10%)</span>
                    <span>${{ number_format($this->tax, 2) }}</span>
                </div>
                <div class="flex justify-between text-2xl font-bold text-gray-900 pt-2">
                    <span>Total</span>
                    <span>${{ number_format($this->grandTotal, 2) }}</span>
                </div>
            </div>

            <a href="{{ route('pos.visual') }}" class="mt-6 text-center text-indigo-600 font-medium hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to POS
            </a>
        </div>

        <!-- Right: Payment Interface -->
        <div class="w-full md:w-2/3 p-6 flex flex-col">

            <!-- Payment Methods -->
            <div class="grid grid-cols-4 gap-4 mb-8 overflow-y-auto max-h-[240px]">
                @foreach($paymentMethods as $method)
                <button class="flex flex-col items-center justify-center p-4 rounded-xl border-2 {{ $method['color'] == 'indigo' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:border-indigo-300 hover:bg-gray-50' }} transition-all">
                    <i class="fas {{ $method['icon'] }} text-2xl mb-2"></i>
                    <span class="font-medium text-center text-sm">{{ $method['name'] }}</span>
                </button>
                @endforeach
            </div>

            <!-- Amount Input Section -->
            <div class="flex-1 flex flex-col justify-center max-w-md mx-auto w-full">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Received Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 text-xl font-bold">$</span>
                        <input type="text" value="{{ number_format($this->grandTotal, 2) }}" class="w-full pl-10 pr-4 py-4 text-3xl font-bold text-gray-900 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-3 mb-6">
                    <button class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">$10</button>
                    <button class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">$20</button>
                    <button class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">$50</button>
                    <button class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">$100</button>
                </div>

                <div class="bg-green-50 rounded-xl p-4 flex justify-between items-center mb-8 border border-green-100">
                    <span class="text-green-800 font-medium">Change Return</span>
                    <span class="text-2xl font-bold text-green-700">$0.40</span>
                </div>

                <a href="{{ route('pos.receipt') }}" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xl text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                    Complete Payment
                </a>
            </div>
        </div>
    </div>
</div>
