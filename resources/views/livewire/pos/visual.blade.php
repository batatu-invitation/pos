<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.pos')]
#[Title('Visual POS - Modern POS')]
class extends Component
{
    public $products = [
        ['name' => 'Classic Burger', 'price' => 12.00, 'stock' => 15, 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Food'],
        ['name' => 'Caramel Latte', 'price' => 4.50, 'stock' => 100, 'image' => 'https://images.unsplash.com/photo-1576107232684-1279f390859f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Drinks'],
        ['name' => 'French Fries', 'price' => 3.50, 'stock' => 50, 'image' => 'https://images.unsplash.com/photo-1573080496987-a199f8cd4058?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Food'],
        ['name' => 'Cheesecake', 'price' => 5.00, 'stock' => 20, 'image' => 'https://images.unsplash.com/photo-1524351199678-941a58a3df50?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Desserts'],
        ['name' => 'Orange Juice', 'price' => 3.00, 'stock' => 40, 'image' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Drinks'],
        ['name' => 'Pizza Slice', 'price' => 4.00, 'stock' => 10, 'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Food'],
        ['name' => 'Chocolate Cake', 'price' => 4.50, 'stock' => 12, 'image' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Desserts'],
        ['name' => 'Iced Tea', 'price' => 2.50, 'stock' => 60, 'image' => 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Drinks'],
        ['name' => 'Chicken Nuggets', 'price' => 5.50, 'stock' => 30, 'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Food'],
        ['name' => 'Hot Dog', 'price' => 3.00, 'stock' => 25, 'image' => 'https://images.unsplash.com/photo-1612392062631-94dd85fa9802?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Food'],
        ['name' => 'Vanilla Shake', 'price' => 4.00, 'stock' => 45, 'image' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Drinks'],
        ['name' => 'Donut', 'price' => 1.50, 'stock' => 80, 'image' => 'https://images.unsplash.com/photo-1551024601-562943300ac1?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'category' => 'Desserts'],
    ];
};
?>

<div class="flex h-full">

    <!-- Left Section: Products -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="bg-white p-4 shadow-sm z-10 flex items-center justify-between">
            <div class="flex items-center space-x-4 w-full">
                <a href="{{ route('dashboard') }}" class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="relative flex-1 max-w-lg">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" class="w-full py-2.5 pl-10 pr-4 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 transition-colors" placeholder="Scan barcode or search products...">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-barcode text-gray-500 cursor-pointer"></i>
                    </span>
                </div>

                <div class="hidden md:flex space-x-2 overflow-x-auto no-scrollbar">
                    <!-- Extra Buttons -->
                    <div class="flex items-center space-x-1 mr-2 border-r border-gray-200 pr-2">
                        <button onclick="toggleFullscreen()" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100" title="Toggle Fullscreen">
                            <i class="fas fa-expand text-lg"></i>
                        </button>
                        <button onclick="connectDevice('printer')" id="btn-printer" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group" title="Connect Printer">
                            <i class="fas fa-print text-lg"></i>
                            <span id="status-printer" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                        </button>
                        <button onclick="connectDevice('scanner')" id="btn-scanner" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group" title="Connect Scanner">
                            <i class="fas fa-barcode text-lg"></i>
                            <span id="status-scanner" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                        </button>
                    </div>

                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium whitespace-nowrap shadow-sm">All Items</button>
                    <button class="px-4 py-2 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">Food</button>
                    <button class="px-4 py-2 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">Drinks</button>
                    <button class="px-4 py-2 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">Desserts</button>
                    <button class="px-4 py-2 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">Electronics</button>
                </div>
            </div>
        </header>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6" id="product-grid">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($products as $product)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer group">
                    <div class="relative h-32 overflow-hidden bg-gray-100">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <span class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">${{ number_format($product['price'], 2) }}</span>
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-bold text-gray-800 truncate">{{ $product['name'] }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock' }}</p>
                    </div>
                </div>
                @endforeach
             </div>
        </div>
    </div>

    <!-- Right Section: Cart -->
    <div class="w-96 bg-white border-l border-gray-200 flex flex-col h-full shadow-xl z-20">
        <!-- Customer & Options -->
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-lg border border-gray-300 cursor-pointer hover:border-indigo-500 transition-colors flex-1 mr-2">
                    <i class="fas fa-user text-indigo-600"></i>
                    <span class="text-sm font-medium text-gray-700">Walk-in Customer</span>
                </div>
                <button class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-indigo-600">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-sticky-note mr-1"></i> Note
                </button>
                <button class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-truck mr-1"></i> Shipping
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">

            <!-- Item 1 -->
            <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                <div class="flex items-start space-x-3">
                    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=60" class="w-12 h-12 rounded-lg object-cover" alt="Item">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Classic Burger</h4>
                        <p class="text-xs text-gray-500">$12.00 / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800">$24.00</span>
                    <div class="flex items-center mt-1 bg-gray-100 rounded-lg">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors">-</button>
                        <span class="text-xs font-medium w-4 text-center">2</span>
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-500 transition-colors">+</button>
                    </div>
                </div>
            </div>

            <!-- Item 2 -->
            <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                <div class="flex items-start space-x-3">
                    <img src="https://images.unsplash.com/photo-1576107232684-1279f390859f?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=60" class="w-12 h-12 rounded-lg object-cover" alt="Item">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Caramel Latte</h4>
                        <p class="text-xs text-gray-500">$4.50 / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800">$4.50</span>
                    <div class="flex items-center mt-1 bg-gray-100 rounded-lg">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors">-</button>
                        <span class="text-xs font-medium w-4 text-center">1</span>
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-500 transition-colors">+</button>
                    </div>
                </div>
            </div>

             <!-- Item 3 -->
             <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                <div class="flex items-start space-x-3">
                    <img src="https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=60" class="w-12 h-12 rounded-lg object-cover" alt="Item">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Glazed Donut</h4>
                        <p class="text-xs text-gray-500">$2.50 / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800">$7.50</span>
                    <div class="flex items-center mt-1 bg-gray-100 rounded-lg">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors">-</button>
                        <span class="text-xs font-medium w-4 text-center">3</span>
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-500 transition-colors">+</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer / Totals -->
        <div class="bg-gray-50 p-4 border-t border-gray-200">
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span>$36.00</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Tax (10%)</span>
                    <span>$3.60</span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-200 pt-2">
                    <span>Total Payable</span>
                    <span>$39.60</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <button class="py-3 rounded-lg border border-red-200 text-red-600 font-medium text-sm hover:bg-red-50 transition-colors">
                    Cancel
                </button>
                <button class="py-3 rounded-lg border border-indigo-200 text-indigo-600 font-medium text-sm hover:bg-indigo-50 transition-colors">
                    Hold Order
                </button>
            </div>

            <a href="{{ route('pos.payment') }}" class="block w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                Pay Now $39.60
            </a>
        </div>
    </div>
</div>
