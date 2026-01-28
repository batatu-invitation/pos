<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.pos')]
#[Title('Mini Market POS - Modern POS')]
class extends Component {
    public $products = [
        ['barcode' => '8934567890123', 'name' => 'Mineral Water 600ml', 'category' => 'Drinks', 'stock' => 124, 'price' => 0.50],
        ['barcode' => '8934567890124', 'name' => 'Instant Noodle Chicken', 'category' => 'Food', 'stock' => 85, 'price' => 0.80],
        ['barcode' => '8934567890125', 'name' => 'Potato Chips Original', 'category' => 'Snacks', 'stock' => 50, 'price' => 1.50],
        ['barcode' => '8934567890126', 'name' => 'Chocolate Bar', 'category' => 'Snacks', 'stock' => 200, 'price' => 1.20],
        ['barcode' => '8934567890127', 'name' => 'Cola Can 330ml', 'category' => 'Drinks', 'stock' => 150, 'price' => 0.90],
        ['barcode' => '8934567890128', 'name' => 'Green Tea Bottle', 'category' => 'Drinks', 'stock' => 80, 'price' => 1.10],
        ['barcode' => '8934567890129', 'name' => 'Sandwich Tuna', 'category' => 'Food', 'stock' => 20, 'price' => 3.50],
        ['barcode' => '8934567890130', 'name' => 'Orange Juice', 'category' => 'Drinks', 'stock' => 40, 'price' => 2.00],
        ['barcode' => '8934567890131', 'name' => 'Biscuits Milk', 'category' => 'Snacks', 'stock' => 90, 'price' => 1.80],
        ['barcode' => '8934567890132', 'name' => 'Energy Drink', 'category' => 'Drinks', 'stock' => 60, 'price' => 2.50],
        ['barcode' => '8934567890133', 'name' => 'Yogurt Strawberry', 'category' => 'Dairy', 'stock' => 30, 'price' => 1.50],
        ['barcode' => '8934567890134', 'name' => 'Milk 1L', 'category' => 'Dairy', 'stock' => 25, 'price' => 2.20],
    ];
};
?>

<div class="flex h-full">

    <!-- Left Section: Product Table -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="bg-white p-4 shadow-sm z-10 flex items-center justify-between">
            <div class="flex items-center space-x-4 w-full">
                <a href="{{ route('dashboard') }}" class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="relative flex-1 max-w-2xl">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" id="search-input" class="w-full py-2.5 pl-10 pr-12 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 transition-colors" placeholder="Scan barcode (F2) or search products..." autofocus>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-barcode text-gray-500 cursor-pointer"></i>
                    </span>
                </div>

                <div class="hidden md:flex items-center space-x-3">
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

                    <div class="text-sm text-gray-500">
                        <span class="font-bold text-gray-700">F2</span> Focus Search
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="font-bold text-gray-700">F4</span> Pay
                    </div>
                </div>
            </div>
        </header>

        <!-- Product Table Container -->
        <div class="flex-1 overflow-hidden p-4 md:p-6 flex flex-col">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex-1 overflow-hidden flex flex-col">
                <!-- Table Header -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200">Barcode</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200">Item Name</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200">Category</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200 text-right">Stock</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200 text-right">Price</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 uppercase border-b border-gray-200 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="product-table-body">
                            @foreach($products as $product)
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">{{ $product['barcode'] }}</td>
                                <td class="p-4 text-sm font-medium text-gray-900">{{ $product['name'] }}</td>
                                <td class="p-4 text-sm text-gray-500">{{ $product['category'] }}</td>
                                <td class="p-4 text-sm text-gray-600 text-right">{{ $product['stock'] }}</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">${{ number_format($product['price'], 2) }}</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 3 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890125</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Chocolate Bar 50g</td>
                                <td class="p-4 text-sm text-gray-500">Snacks</td>
                                <td class="p-4 text-sm text-gray-600 text-right">42</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$1.20</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 4 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890126</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Potato Chips Original</td>
                                <td class="p-4 text-sm text-gray-500">Snacks</td>
                                <td class="p-4 text-sm text-gray-600 text-right">30</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$1.50</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 5 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890127</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Energy Drink 250ml</td>
                                <td class="p-4 text-sm text-gray-500">Drinks</td>
                                <td class="p-4 text-sm text-gray-600 text-right">60</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$2.00</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 6 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890128</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Sandwich Bread</td>
                                <td class="p-4 text-sm text-gray-500">Bakery</td>
                                <td class="p-4 text-sm text-gray-600 text-right">15</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$2.50</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 7 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890129</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Milk 1L</td>
                                <td class="p-4 text-sm text-gray-500">Dairy</td>
                                <td class="p-4 text-sm text-gray-600 text-right">20</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$1.80</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 8 -->
                            <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 font-mono">8934567890130</td>
                                <td class="p-4 text-sm font-medium text-gray-900">Eggs (Dozen)</td>
                                <td class="p-4 text-sm text-gray-500">Dairy</td>
                                <td class="p-4 text-sm text-gray-600 text-right">25</td>
                                <td class="p-4 text-sm font-bold text-gray-900 text-right">$3.00</td>
                                <td class="p-4 text-center">
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Mineral Water 600ml</h4>
                        <p class="text-xs text-gray-500">$0.50 / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800">$1.00</span>
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
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Chocolate Bar 50g</h4>
                        <p class="text-xs text-gray-500">$1.20 / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800">$1.20</span>
                    <div class="flex items-center mt-1 bg-gray-100 rounded-lg">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors">-</button>
                        <span class="text-xs font-medium w-4 text-center">1</span>
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
                    <span>$2.20</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Tax (10%)</span>
                    <span>$0.22</span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-200 pt-2">
                    <span>Total Payable</span>
                    <span>$2.42</span>
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
                Pay Now $2.42 (F4)
            </a>
        </div>
    </div>
</div>

<script>
    // Keyboard Shortcuts
    document.addEventListener('keydown', function(event) {
        // F2 to focus search
        if (event.key === 'F2') {
            event.preventDefault();
            document.getElementById('search-input').focus();
        }
        // F4 to pay
        if (event.key === 'F4') {
            event.preventDefault();
            window.location.href = '{{ route("pos.payment") }}';
        }
    });
</script>
