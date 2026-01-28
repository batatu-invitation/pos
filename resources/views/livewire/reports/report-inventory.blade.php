<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Inventory Report - Modern POS')]
class extends Component
{
    public $inventoryItems = [
        ['name' => 'Double Burger', 'category' => 'Food', 'in' => 150, 'out' => 30, 'current' => 120],
        ['name' => 'Cola Zero', 'category' => 'Drinks', 'in' => 100, 'out' => 50, 'current' => 50],
        ['name' => 'French Fries', 'category' => 'Food', 'in' => 200, 'out' => 80, 'current' => 120],
        ['name' => 'Chicken Nuggets', 'category' => 'Food', 'in' => 180, 'out' => 60, 'current' => 120],
        ['name' => 'Ice Cream', 'category' => 'Dessert', 'in' => 80, 'out' => 20, 'current' => 60],
        ['name' => 'Water Bottle', 'category' => 'Drinks', 'in' => 300, 'out' => 120, 'current' => 180],
        ['name' => 'Orange Juice', 'category' => 'Drinks', 'in' => 120, 'out' => 40, 'current' => 80],
        ['name' => 'Cheeseburger', 'category' => 'Food', 'in' => 160, 'out' => 70, 'current' => 90],
        ['name' => 'Salad', 'category' => 'Food', 'in' => 50, 'out' => 15, 'current' => 35],
        ['name' => 'Coffee', 'category' => 'Drinks', 'in' => 250, 'out' => 150, 'current' => 100],
        ['name' => 'Tea', 'category' => 'Drinks', 'in' => 150, 'out' => 60, 'current' => 90],
        ['name' => 'Muffin', 'category' => 'Dessert', 'in' => 100, 'out' => 45, 'current' => 55],
    ];
}; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none mr-4 md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-semibold text-gray-800">Inventory Report</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative hidden md:block">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" class="w-64 py-2 pl-10 pr-4 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors" placeholder="Search...">
                </div>
                
                <button class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <a href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-cash-register mr-2"></i>
                    Open POS
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
             <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Inventory Report</h2>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Items</p>
                    <h3 class="text-2xl font-bold text-gray-800">1,250</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Value</p>
                    <h3 class="text-2xl font-bold text-gray-800">$45,350.00</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Low Stock Items</p>
                    <h3 class="text-2xl font-bold text-red-600">12</h3>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Product Name</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Stock In</th>
                                <th class="px-6 py-4">Stock Out</th>
                                <th class="px-6 py-4">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($inventoryItems as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $item['name'] }}</td>
                                <td class="px-6 py-4">{{ $item['category'] }}</td>
                                <td class="px-6 py-4 text-green-600">+{{ $item['in'] }}</td>
                                <td class="px-6 py-4 text-red-600">-{{ $item['out'] }}</td>
                                <td class="px-6 py-4 font-bold">{{ $item['current'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
