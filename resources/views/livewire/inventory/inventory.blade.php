<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
#[Title('Inventory - Modern POS')]
class extends Component
{
    public $inventoryItems = [
        ['product' => 'Double Burger', 'sku' => 'BUR-001', 'stock' => 120, 'limit' => 10, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Special Sauce', 'sku' => 'SAU-005', 'stock' => 5, 'limit' => 15, 'status' => 'Low Stock', 'stock_color' => 'text-red-600', 'status_color' => 'bg-red-100 text-red-800'],
        ['product' => 'Cheese Slice', 'sku' => 'ING-003', 'stock' => 200, 'limit' => 20, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Cola 330ml', 'sku' => 'DRK-001', 'stock' => 85, 'limit' => 24, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'French Fries (Frozen)', 'sku' => 'FRZ-002', 'stock' => 50, 'limit' => 10, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Chicken Nuggets', 'sku' => 'FRZ-005', 'stock' => 8, 'limit' => 15, 'status' => 'Low Stock', 'stock_color' => 'text-red-600', 'status_color' => 'bg-red-100 text-red-800'],
        ['product' => 'Napkins', 'sku' => 'SUP-001', 'stock' => 500, 'limit' => 100, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Straws', 'sku' => 'SUP-002', 'stock' => 1000, 'limit' => 200, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Ketchup Packets', 'sku' => 'CON-001', 'stock' => 300, 'limit' => 50, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Mayonnaise Packets', 'sku' => 'CON-002', 'stock' => 280, 'limit' => 50, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Orange Juice', 'sku' => 'DRK-005', 'stock' => 40, 'limit' => 10, 'status' => 'In Stock', 'stock_color' => 'text-green-600', 'status_color' => 'bg-green-100 text-green-800'],
        ['product' => 'Ice Cream Mix', 'sku' => 'ING-010', 'stock' => 2, 'limit' => 5, 'status' => 'Low Stock', 'stock_color' => 'text-red-600', 'status_color' => 'bg-red-100 text-red-800'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Inventory Stock</h2>
        <div class="flex space-x-2">
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-file-export mr-2"></i> Export
            </button>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-sync mr-2"></i> Stock Adjustment
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Current Stock</th>
                        <th class="px-6 py-4">Low Stock Limit</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($inventoryItems as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $item['product'] }}</td>
                        <td class="px-6 py-4">{{ $item['sku'] }}</td>
                        <td class="px-6 py-4 {{ $item['stock_color'] }} font-bold">{{ $item['stock'] }}</td>
                        <td class="px-6 py-4">{{ $item['limit'] }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item['status_color'] }}">{{ $item['status'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>