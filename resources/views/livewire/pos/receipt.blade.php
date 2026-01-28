<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.pos')]
#[Title('Receipt - Modern POS')]
class extends Component {
    public $receiptItems = [
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

    public function getTotalProperty()
    {
        return collect($this->receiptItems)->sum(fn($item) => $item['quantity'] * $item['price']);
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

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        @page {
            size: 80mm auto; /* Standard thermal receipt width */
            margin: 0;
        }
    }
</style>

<div class="h-full w-full flex items-center justify-center p-4">

    <!-- Printable Receipt Section (Hidden on Screen) -->
    <div id="printable-area" class="hidden print:block bg-white p-4 max-w-[80mm] mx-auto text-black font-mono text-sm">
        <div class="text-center mb-4">
            <h2 class="text-xl font-bold uppercase">POS Pro Store</h2>
            <p class="text-xs">123 Business Street, City, Country</p>
            <p class="text-xs">Tel: +1 234 567 890</p>
        </div>

        <div class="border-b-2 border-dashed border-black my-2"></div>

        <div class="flex justify-between text-xs mb-2">
            <span>Date: {{ now()->format('Y-m-d') }}</span>
            <span>Time: {{ now()->format('h:i A') }}</span>
        </div>
        <div class="flex justify-between text-xs mb-2">
            <span>Order: #ORD-2023-001</span>
            <span>Cashier: Admin</span>
        </div>

        <div class="border-b-2 border-dashed border-black my-2"></div>

        <div class="flex flex-col gap-1 text-xs">
            @foreach($receiptItems as $item)
            <div class="flex justify-between">
                <span>{{ $item['quantity'] }} x {{ $item['name'] }}</span>
                <span>${{ number_format($item['quantity'] * $item['price'], 2) }}</span>
            </div>
            @endforeach
        </div>

        <div class="border-b-2 border-dashed border-black my-2"></div>

        <div class="flex justify-between font-bold text-sm">
            <span>TOTAL</span>
            <span>${{ number_format($this->grandTotal, 2) }}</span>
        </div>
        <div class="flex justify-between text-xs mt-1">
            <span>CASH</span>
            <span>${{ number_format($this->grandTotal, 2) }}</span>
        </div>
        <div class="flex justify-between text-xs mt-1">
            <span>CHANGE</span>
            <span>$0.00</span>
        </div>

        <div class="border-b-2 border-dashed border-black my-4"></div>

        <div class="text-center text-xs">
            <p class="mb-2">Thank you for your purchase!</p>
            <p>Please visit us again.</p>
            <div class="mt-4 mx-auto max-w-[150px]">
                <!-- Barcode Placeholder -->
                <div class="h-8 bg-black"></div>
                <p class="text-[10px] mt-1">#ORD-2023-001</p>
            </div>
        </div>
    </div>

    <!-- Screen UI -->
    <div class="max-w-md w-full print:hidden">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="bg-green-500 p-8 text-center text-white">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                    <i class="fas fa-check text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold">Payment Successful!</h2>
                <p class="text-green-100 mt-1">Transaction #ORD-2023-001</p>
            </div>

            <div class="p-8">
                <div class="text-center mb-6">
                    <p class="text-gray-500 text-sm">Total Paid</p>
                    <p class="text-3xl font-bold text-gray-900">$39.60</p>
                    <p class="text-gray-400 text-xs mt-1">Via Cash</p>
                </div>

                <div class="border-t border-b border-gray-100 py-4 mb-6 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Date</span>
                        <span class="font-medium text-gray-900">Oct 24, 2023, 10:30 AM</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Customer</span>
                        <span class="font-medium text-gray-900">Walk-in Customer</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Cashier</span>
                        <span class="font-medium text-gray-900">Admin User</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <button onclick="window.print()" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg transition-colors flex items-center justify-center">
                        <i class="fas fa-print mr-2"></i> Print Receipt
                    </button>
                    <button class="w-full py-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-medium transition-colors flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i> Email Receipt
                    </button>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('pos.visual') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i> New Sale
            </a>
        </div>
    </div>
</div>
