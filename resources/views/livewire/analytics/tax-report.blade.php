<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Tax Report - Modern POS')]
class extends Component
{
    public $taxDetails = [
        ['name' => 'VAT (Standard)', 'rate' => 10, 'taxable' => 35000.00, 'tax' => 3500.00],
        ['name' => 'Service Tax', 'rate' => 5, 'taxable' => 10200.00, 'tax' => 510.00],
        ['name' => 'Eco Tax', 'rate' => 2, 'taxable' => 25500.00, 'tax' => 510.00],
        ['name' => 'VAT (Reduced)', 'rate' => 5, 'taxable' => 15000.00, 'tax' => 750.00],
        ['name' => 'Luxury Tax', 'rate' => 15, 'taxable' => 5000.00, 'tax' => 750.00],
        ['name' => 'Import Duty', 'rate' => 8, 'taxable' => 8000.00, 'tax' => 640.00],
        ['name' => 'Digital Services Tax', 'rate' => 6, 'taxable' => 12000.00, 'tax' => 720.00],
        ['name' => 'Tourism Tax', 'rate' => 10, 'taxable' => 3000.00, 'tax' => 300.00],
        ['name' => 'Withholding Tax', 'rate' => 10, 'taxable' => 2000.00, 'tax' => 200.00],
        ['name' => 'Corporate Tax', 'rate' => 20, 'taxable' => 50000.00, 'tax' => 10000.00],
        ['name' => 'Local Sales Tax', 'rate' => 4, 'taxable' => 9000.00, 'tax' => 360.00],
        ['name' => 'Excise Duty', 'rate' => 12, 'taxable' => 4500.00, 'tax' => 540.00],
    ];

    public function getTotalTaxable()
    {
        return array_sum(array_column($this->taxDetails, 'taxable'));
    }

    public function getTotalTax()
    {
        return array_sum(array_column($this->taxDetails, 'tax'));
    }
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
                <h1 class="text-2xl font-semibold text-gray-800">Tax Report</h1>
            </div>
            
            <div class="flex items-center space-x-4">
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
            
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Period</label>
                            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                <option>This Month</option>
                                <option>Last Month</option>
                                <option>This Quarter</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <div class="relative self-end">
                            <button class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                Update
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tax Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Total Sales Tax</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">${{ number_format($this->getTotalTax(), 2) }}</span>
                        <span class="ml-2 text-sm text-green-600">+5.2%</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Taxable Sales</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">${{ number_format($this->getTotalTaxable(), 2) }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Non-Taxable Sales</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-900">$2,100.00</span>
                    </div>
                </div>
            </div>

            <!-- Tax Detail Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Tax Details</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Tax Name</th>
                                <th class="px-6 py-4 font-semibold">Rate</th>
                                <th class="px-6 py-4 font-semibold text-right">Taxable Amount</th>
                                <th class="px-6 py-4 font-semibold text-right">Tax Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($taxDetails as $tax)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $tax['name'] }}</td>
                                <td class="px-6 py-4">{{ $tax['rate'] }}%</td>
                                <td class="px-6 py-4 text-right">${{ number_format($tax['taxable'], 2) }}</td>
                                <td class="px-6 py-4 text-right font-medium text-indigo-600">${{ number_format($tax['tax'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-900">
                            <tr>
                                <td class="px-6 py-4" colspan="2">Total</td>
                                <td class="px-6 py-4 text-right">${{ number_format($this->getTotalTaxable(), 2) }}</td>
                                <td class="px-6 py-4 text-right">${{ number_format($this->getTotalTax(), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>
