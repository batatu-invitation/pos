<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Expenses Report - Modern POS')]
class extends Component
{
    public $expenses = [
        ['date' => 'Oct 24, 2023', 'description' => 'Shop Rent', 'category' => 'Rent', 'amount' => 2000.00, 'status' => 'Paid'],
        ['date' => 'Oct 20, 2023', 'description' => 'Electricity Bill', 'category' => 'Utilities', 'amount' => 350.00, 'status' => 'Paid'],
        ['date' => 'Oct 18, 2023', 'description' => 'Internet Subscription', 'category' => 'Utilities', 'amount' => 80.00, 'status' => 'Paid'],
        ['date' => 'Oct 15, 2023', 'description' => 'Office Supplies', 'category' => 'Supplies', 'amount' => 120.50, 'status' => 'Paid'],
        ['date' => 'Oct 12, 2023', 'description' => 'Cleaning Services', 'category' => 'Maintenance', 'amount' => 150.00, 'status' => 'Pending'],
        ['date' => 'Oct 10, 2023', 'description' => 'Marketing Campaign', 'category' => 'Advertising', 'amount' => 500.00, 'status' => 'Paid'],
        ['date' => 'Oct 08, 2023', 'description' => 'Water Bill', 'category' => 'Utilities', 'amount' => 45.00, 'status' => 'Paid'],
        ['date' => 'Oct 05, 2023', 'description' => 'Staff Lunch', 'category' => 'Meals', 'amount' => 85.00, 'status' => 'Paid'],
        ['date' => 'Oct 03, 2023', 'description' => 'Equipment Repair', 'category' => 'Maintenance', 'amount' => 250.00, 'status' => 'Paid'],
        ['date' => 'Oct 01, 2023', 'description' => 'Software License', 'category' => 'Software', 'amount' => 100.00, 'status' => 'Paid'],
        ['date' => 'Sep 28, 2023', 'description' => 'New Furniture', 'category' => 'Assets', 'amount' => 1200.00, 'status' => 'Paid'],
        ['date' => 'Sep 25, 2023', 'description' => 'Transport', 'category' => 'Travel', 'amount' => 60.00, 'status' => 'Paid'],
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
                <h1 class="text-2xl font-semibold text-gray-800">Expenses Report</h1>
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
                <h2 class="text-2xl font-bold text-gray-800">Expenses Report</h2>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add Expense
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($expenses as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $expense['date'] }}</td>
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $expense['description'] }}</td>
                                <td class="px-6 py-4">{{ $expense['category'] }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">${{ number_format($expense['amount'], 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $expense['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $expense['status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
