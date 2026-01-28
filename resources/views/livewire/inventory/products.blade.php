<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
#[Title('Products - Modern POS')]
class extends Component
{
    public $products = [
        ['name' => 'Double Burger', 'sku' => 'BUR-001', 'category' => 'Food', 'price' => 12.00, 'stock' => 120, 'status' => 'Active', 'icon' => '🍔'],
        ['name' => 'Cola Zero', 'sku' => 'DRK-001', 'category' => 'Drinks', 'price' => 3.00, 'stock' => 50, 'status' => 'Active', 'icon' => '🥤'],
        ['name' => 'Cheese Burger', 'sku' => 'BUR-002', 'category' => 'Food', 'price' => 10.00, 'stock' => 100, 'status' => 'Active', 'icon' => '🍔'],
        ['name' => 'Chicken Nuggets', 'sku' => 'FOD-001', 'category' => 'Food', 'price' => 8.00, 'stock' => 60, 'status' => 'Active', 'icon' => '🍗'],
        ['name' => 'French Fries', 'sku' => 'SNK-001', 'category' => 'Food', 'price' => 4.00, 'stock' => 80, 'status' => 'Active', 'icon' => '🍟'],
        ['name' => 'Vanilla Shake', 'sku' => 'DRK-002', 'category' => 'Drinks', 'price' => 5.00, 'stock' => 40, 'status' => 'Active', 'icon' => '🥤'],
        ['name' => 'Chocolate Cake', 'sku' => 'DES-001', 'category' => 'Desserts', 'price' => 6.00, 'stock' => 20, 'status' => 'Active', 'icon' => '🍰'],
        ['name' => 'Coffee', 'sku' => 'DRK-003', 'category' => 'Drinks', 'price' => 3.50, 'stock' => 100, 'status' => 'Active', 'icon' => '☕'],
        ['name' => 'Tea', 'sku' => 'DRK-004', 'category' => 'Drinks', 'price' => 3.00, 'stock' => 100, 'status' => 'Active', 'icon' => '🍵'],
        ['name' => 'Salad', 'sku' => 'FOD-002', 'category' => 'Food', 'price' => 9.00, 'stock' => 30, 'status' => 'Active', 'icon' => '🥗'],
        ['name' => 'Pizza Slice', 'sku' => 'FOD-003', 'category' => 'Food', 'price' => 5.00, 'stock' => 40, 'status' => 'Active', 'icon' => '🍕'],
        ['name' => 'Ice Cream', 'sku' => 'DES-002', 'category' => 'Desserts', 'price' => 4.50, 'stock' => 50, 'status' => 'Active', 'icon' => '🍦'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Products</h2>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Product
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative max-w-sm w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-search text-gray-400"></i>
                </span>
                <input type="text" class="w-full py-2 pl-10 pr-4 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500" placeholder="Search products...">
            </div>
            <div class="flex items-center gap-2">
                <select class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                    <option>All Categories</option>
                    <option>Food</option>
                    <option>Drinks</option>
                </select>
                <button class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Price</th>
                        <th class="px-6 py-4">Stock</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-xl mr-3">{{ $product['icon'] }}</div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $product['name'] }}</p>
                                    <p class="text-xs text-gray-500">SKU: {{ $product['sku'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $product['category'] }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">${{ number_format($product['price'], 2) }}</td>
                        <td class="px-6 py-4">{{ $product['stock'] }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $product['status'] }}</span></td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 flex items-center justify-between">
            <span class="text-sm text-gray-500">Showing 1-{{ count($products) }} of {{ count($products) }} products</span>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50" disabled>Previous</button>
                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50" disabled>Next</button>
            </div>
        </div>
    </div>
</div>
