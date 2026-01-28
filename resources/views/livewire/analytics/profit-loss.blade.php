<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
    #[Title('Profit & Loss - Modern POS')]
    class extends Component
{
    public $revenueItems = [
        ['name' => 'Food Sales', 'amount' => 32500.00],
        ['name' => 'Beverage Sales', 'amount' => 12800.00],
        ['name' => 'Merchandise', 'amount' => 1200.00],
        ['name' => 'Catering Services', 'amount' => 500.00],
        ['name' => 'Delivery Fees', 'amount' => 850.00],
        ['name' => 'Gift Card Sales', 'amount' => 450.00],
        ['name' => 'Event Hosting', 'amount' => 1500.00],
        ['name' => 'Vending Machine', 'amount' => 300.00],
        ['name' => 'Loyalty Program', 'amount' => -200.00], // Discount/Cost
        ['name' => 'Misc Income', 'amount' => 150.00],
    ];

    public $expenseItems = [
        ['name' => 'Cost of Goods Sold', 'amount' => 18500.00],
        ['name' => 'Salaries & Wages', 'amount' => 12000.00],
        ['name' => 'Rent & Utilities', 'amount' => 4500.00],
        ['name' => 'Marketing & Ads', 'amount' => 1200.00],
        ['name' => 'Equipment Maintenance', 'amount' => 850.00],
        ['name' => 'Software Subscriptions', 'amount' => 350.00],
        ['name' => 'Insurance', 'amount' => 600.00],
        ['name' => 'Office Supplies', 'amount' => 250.00],
        ['name' => 'Legal & Professional', 'amount' => 500.00],
        ['name' => 'Travel & Transport', 'amount' => 450.00],
        ['name' => 'Taxes & Licenses', 'amount' => 800.00],
        ['name' => 'Bank Fees', 'amount' => 120.00],
    ];

    public function getTotalRevenue()
    {
        return array_sum(array_column($this->revenueItems, 'amount'));
    }

    public function getTotalExpenses()
    {
        return array_sum(array_column($this->expenseItems, 'amount'));
    }

    public function getNetProfit()
    {
        return $this->getTotalRevenue() - $this->getTotalExpenses();
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                    <input type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                </div>
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                    <input type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                </div>
                <div class="relative self-end">
                    <button class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Apply
                    </button>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total Revenue</p>
            <h3 class="text-3xl font-bold text-green-600 mt-2">${{ number_format($this->getTotalRevenue(), 2) }}</h3>
            <p class="text-xs text-green-500 mt-1 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> 12.5% vs last period
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total Expenses</p>
            <h3 class="text-3xl font-bold text-red-600 mt-2">${{ number_format($this->getTotalExpenses(), 2) }}</h3>
            <p class="text-xs text-red-500 mt-1 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> 5.2% vs last period
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Net Profit</p>
            <h3 class="text-3xl font-bold text-indigo-600 mt-2">${{ number_format($this->getNetProfit(), 2) }}</h3>
            <p class="text-xs text-green-500 mt-1 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> 8.3% Net Margin
            </p>
        </div>
    </div>

    <!-- Detailed P&L Statement -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Income Statement</h2>
            <span class="text-sm text-gray-500">Jan 1, 2024 - Jan 31, 2024</span>
        </div>

        <div class="p-6">
            <!-- Revenue Section -->
            <div class="mb-8">
                <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">Revenue</h3>
                <div class="space-y-3">
                    @foreach($revenueItems as $item)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-700">{{ $item['name'] }}</span>
                        <span class="font-medium text-gray-900">${{ number_format($item['amount'], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between items-center py-3 bg-green-50 px-4 rounded-lg mt-4">
                        <span class="font-bold text-green-800">Total Revenue</span>
                        <span class="font-bold text-green-800">${{ number_format($this->getTotalRevenue(), 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Expenses Section -->
            <div class="mb-8">
                <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">Expenses</h3>
                <div class="space-y-3">
                    @foreach($expenseItems as $item)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-700">{{ $item['name'] }}</span>
                        <span class="font-medium text-gray-900">${{ number_format($item['amount'], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between items-center py-3 bg-red-50 px-4 rounded-lg mt-4">
                        <span class="font-bold text-red-800">Total Expenses</span>
                        <span class="font-bold text-red-800">${{ number_format($this->getTotalExpenses(), 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Net Profit -->
            <div class="flex justify-between items-center py-4 bg-gray-900 text-white px-6 rounded-xl shadow-lg">
                <span class="text-lg font-bold">Net Profit</span>
                <span class="text-2xl font-bold">${{ number_format($this->getNetProfit(), 2) }}</span>
            </div>
        </div>
    </div>
</div>
