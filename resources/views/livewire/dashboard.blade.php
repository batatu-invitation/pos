<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('Dashboard - Modern POS')] class extends Component {
    public function mount()
    {
        if (!session()->has('locale')) {
            session(['locale' => 'id']);
        }


    }
    public $topProducts = [['name' => 'Double Burger', 'sales' => 120, 'revenue' => 1200, 'icon' => '🍔'], ['name' => 'French Fries', 'sales' => 85, 'revenue' => 425, 'icon' => '🍟'], ['name' => 'Cola Zero', 'sales' => 70, 'revenue' => 210, 'icon' => '🥤'], ['name' => 'Ice Coffee', 'sales' => 54, 'revenue' => 270, 'icon' => '☕'], ['name' => 'Chicken Nuggets', 'sales' => 45, 'revenue' => 225, 'icon' => '🍗'], ['name' => 'Vanilla Shake', 'sales' => 40, 'revenue' => 200, 'icon' => '🍦'], ['name' => 'Cheese Sandwich', 'sales' => 35, 'revenue' => 175, 'icon' => '🥪'], ['name' => 'Hot Dog', 'sales' => 30, 'revenue' => 150, 'icon' => '🌭'], ['name' => 'Onion Rings', 'sales' => 25, 'revenue' => 125, 'icon' => '🧅'], ['name' => 'Caesar Salad', 'sales' => 20, 'revenue' => 140, 'icon' => '🥗'], ['name' => 'Apple Pie', 'sales' => 15, 'revenue' => 75, 'icon' => '🥧'], ['name' => 'Mineral Water', 'sales' => 10, 'revenue' => 20, 'icon' => '💧']];

    public $recentTransactions = [
        ['id' => '#ORD-001', 'customer' => 'John Doe', 'date' => 'Today, 10:45 AM', 'items' => '3 Items', 'total' => 45.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-002', 'customer' => 'Sarah Smith', 'date' => 'Today, 10:30 AM', 'items' => '1 Item', 'total' => 12.5, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-003', 'customer' => 'Michael Brown', 'date' => 'Today, 10:15 AM', 'items' => '5 Items', 'total' => 85.0, 'status' => 'Pending', 'status_color' => 'yellow'],
        ['id' => '#ORD-004', 'customer' => 'Emily Davis', 'date' => 'Today, 10:00 AM', 'items' => '2 Items', 'total' => 24.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-005', 'customer' => 'David Wilson', 'date' => 'Today, 09:45 AM', 'items' => '4 Items', 'total' => 55.5, 'status' => 'Refunded', 'status_color' => 'red'],
        ['id' => '#ORD-006', 'customer' => 'Jessica Garcia', 'date' => 'Today, 09:30 AM', 'items' => '1 Item', 'total' => 8.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-007', 'customer' => 'Daniel Martinez', 'date' => 'Today, 09:15 AM', 'items' => '3 Items', 'total' => 32.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-008', 'customer' => 'Laura Robinson', 'date' => 'Today, 09:00 AM', 'items' => '2 Items', 'total' => 18.5, 'status' => 'Pending', 'status_color' => 'yellow'],
        ['id' => '#ORD-009', 'customer' => 'Kevin Clark', 'date' => 'Today, 08:45 AM', 'items' => '6 Items', 'total' => 95.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-010', 'customer' => 'Amanda Lewis', 'date' => 'Today, 08:30 AM', 'items' => '1 Item', 'total' => 5.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-011', 'customer' => 'Robert Walker', 'date' => 'Today, 08:15 AM', 'items' => '2 Items', 'total' => 22.0, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-012', 'customer' => 'Jennifer Hall', 'date' => 'Today, 08:00 AM', 'items' => '3 Items', 'total' => 38.0, 'status' => 'Completed', 'status_color' => 'green'],
    ];
};
?>

<div>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-indigo-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Sales') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">$12,426</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-2xl text-indigo-600 shadow-lg shadow-indigo-200/30 dark:from-indigo-900/30 dark:to-indigo-800/30 dark:text-indigo-400">
                    <i class="fas fa-dollar-sign text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 12%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Orders') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">1,240</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl text-blue-600 shadow-lg shadow-blue-200/30 dark:from-blue-900/30 dark:to-blue-800/30 dark:text-blue-400">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 8%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-orange-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Products') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">845</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl text-orange-600 shadow-lg shadow-orange-200/30 dark:from-orange-900/30 dark:to-orange-800/30 dark:text-orange-400">
                    <i class="fas fa-box text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-red-500 flex items-center font-medium">
                    <i class="fas fa-arrow-down mr-1"></i> 2%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-teal-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Customers') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">3,200</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-teal-50 to-teal-100 rounded-2xl text-teal-600 shadow-lg shadow-teal-200/30 dark:from-teal-900/30 dark:to-teal-800/30 dark:text-teal-400">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 14%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Chart -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 lg:col-span-2 hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Sales Overview') }}</h3>
                <select
                    class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    <option>{{ __('Last 7 Days') }}</option>
                    <option>{{ __('Last 30 Days') }}</option>
                    <option>{{ __('This Year') }}</option>
                </select>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 mb-4 dark:text-gray-100">{{ __('Top Selling Products') }}</h3>
            <div class="space-y-4">
                @foreach ($topProducts as $product)
                    <div class="flex items-center justify-between group p-2 rounded-xl hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/50">
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center text-xl mr-4 shadow-sm dark:from-gray-700 dark:to-gray-600">
                                {{ $product['icon'] }}</div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $product['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product['sales'] }} {{ __('sales') }}</p>
                            </div>
                        </div>
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-gray-100">${{ number_format($product['revenue'], 0) }}</span>
                    </div>
                @endforeach
            </div>
            <button
                class="w-full mt-6 py-3 bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl text-sm font-medium text-indigo-700 hover:from-indigo-100 hover:to-blue-100 transition-all duration-300 dark:from-indigo-900/30 dark:to-blue-900/30 dark:border-indigo-700 dark:text-indigo-300">{{ __('View All Products') }}</button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Recent Transactions') }}</h3>
            <a href="#"
                class="text-indigo-600 text-sm font-medium hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('View All') }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">{{ __('Order ID') }}</th>
                        <th class="px-6 py-4">{{ __('Customer') }}</th>
                        <th class="px-6 py-4">{{ __('Date') }}</th>
                        <th class="px-6 py-4">{{ __('Items') }}</th>
                        <th class="px-6 py-4">{{ __('Total') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100">{{ $transaction['id'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['date'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['items'] }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-100">
                                ${{ number_format($transaction['total'], 2) }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction['status_color'] }}-100 text-{{ $transaction['status_color'] }}-800 dark:bg-{{ $transaction['status_color'] }}-900/30 dark:text-{{ $transaction['status_color'] }}-300">{{ $transaction['status'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    @script
        <script>
            const initSalesChart = () => {
                const canvas = document.getElementById('salesChart');
                if (!canvas) return;

                // Destroy existing chart if it exists to prevent "Canvas is already in use" error
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }

                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Sales ($)',
                            data: [1200, 1900, 3000, 2500, 2200, 3200, 4000],
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#f3f4f6'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Initialize on load
            initSalesChart();

            // Re-initialize on Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                // Only try to init if the canvas actually exists on the current page
                if (document.getElementById('salesChart')) {
                    initSalesChart();
                }
            });
        </script>
    @endscript
</div>
