<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Dashboard - Modern POS')]
class extends Component
{
    public $topProducts = [
        ['name' => 'Double Burger', 'sales' => 120, 'revenue' => 1200, 'icon' => '🍔'],
        ['name' => 'French Fries', 'sales' => 85, 'revenue' => 425, 'icon' => '🍟'],
        ['name' => 'Cola Zero', 'sales' => 70, 'revenue' => 210, 'icon' => '🥤'],
        ['name' => 'Ice Coffee', 'sales' => 54, 'revenue' => 270, 'icon' => '☕'],
        ['name' => 'Chicken Nuggets', 'sales' => 45, 'revenue' => 225, 'icon' => '🍗'],
        ['name' => 'Vanilla Shake', 'sales' => 40, 'revenue' => 200, 'icon' => '🍦'],
        ['name' => 'Cheese Sandwich', 'sales' => 35, 'revenue' => 175, 'icon' => '🥪'],
        ['name' => 'Hot Dog', 'sales' => 30, 'revenue' => 150, 'icon' => '🌭'],
        ['name' => 'Onion Rings', 'sales' => 25, 'revenue' => 125, 'icon' => '🧅'],
        ['name' => 'Caesar Salad', 'sales' => 20, 'revenue' => 140, 'icon' => '🥗'],
        ['name' => 'Apple Pie', 'sales' => 15, 'revenue' => 75, 'icon' => '🥧'],
        ['name' => 'Mineral Water', 'sales' => 10, 'revenue' => 20, 'icon' => '💧'],
    ];

    public $recentTransactions = [
        ['id' => '#ORD-001', 'customer' => 'John Doe', 'date' => 'Today, 10:45 AM', 'items' => '3 Items', 'total' => 45.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-002', 'customer' => 'Sarah Smith', 'date' => 'Today, 10:30 AM', 'items' => '1 Item', 'total' => 12.50, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-003', 'customer' => 'Michael Brown', 'date' => 'Today, 10:15 AM', 'items' => '5 Items', 'total' => 85.00, 'status' => 'Pending', 'status_color' => 'yellow'],
        ['id' => '#ORD-004', 'customer' => 'Emily Davis', 'date' => 'Today, 10:00 AM', 'items' => '2 Items', 'total' => 24.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-005', 'customer' => 'David Wilson', 'date' => 'Today, 09:45 AM', 'items' => '4 Items', 'total' => 55.50, 'status' => 'Refunded', 'status_color' => 'red'],
        ['id' => '#ORD-006', 'customer' => 'Jessica Garcia', 'date' => 'Today, 09:30 AM', 'items' => '1 Item', 'total' => 8.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-007', 'customer' => 'Daniel Martinez', 'date' => 'Today, 09:15 AM', 'items' => '3 Items', 'total' => 32.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-008', 'customer' => 'Laura Robinson', 'date' => 'Today, 09:00 AM', 'items' => '2 Items', 'total' => 18.50, 'status' => 'Pending', 'status_color' => 'yellow'],
        ['id' => '#ORD-009', 'customer' => 'Kevin Clark', 'date' => 'Today, 08:45 AM', 'items' => '6 Items', 'total' => 95.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-010', 'customer' => 'Amanda Lewis', 'date' => 'Today, 08:30 AM', 'items' => '1 Item', 'total' => 5.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-011', 'customer' => 'Robert Walker', 'date' => 'Today, 08:15 AM', 'items' => '2 Items', 'total' => 22.00, 'status' => 'Completed', 'status_color' => 'green'],
        ['id' => '#ORD-012', 'customer' => 'Jennifer Hall', 'date' => 'Today, 08:00 AM', 'items' => '3 Items', 'total' => 38.00, 'status' => 'Completed', 'status_color' => 'green'],
    ];
};
?>

<div>
    <x-slot name="header">Dashboard</x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Sales</p>
                    <h3 class="text-2xl font-bold text-gray-800">$12,426</h3>
                </div>
                <div class="p-3 bg-indigo-50 rounded-full text-indigo-600">
                    <i class="fas fa-dollar-sign text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 12%
                </span>
                <span class="text-gray-400 ml-2">vs last month</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-800">1,240</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-full text-blue-600">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 8%
                </span>
                <span class="text-gray-400 ml-2">vs last month</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Products</p>
                    <h3 class="text-2xl font-bold text-gray-800">845</h3>
                </div>
                <div class="p-3 bg-orange-50 rounded-full text-orange-600">
                    <i class="fas fa-box text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-red-500 flex items-center font-medium">
                    <i class="fas fa-arrow-down mr-1"></i> 2%
                </span>
                <span class="text-gray-400 ml-2">vs last month</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Customers</p>
                    <h3 class="text-2xl font-bold text-gray-800">3,200</h3>
                </div>
                <div class="p-3 bg-teal-50 rounded-full text-teal-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-500 flex items-center font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> 14%
                </span>
                <span class="text-gray-400 ml-2">vs last month</span>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Sales Overview</h3>
                <select class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>This Year</option>
                </select>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Selling Products</h3>
            <div class="space-y-4">
                @foreach($topProducts as $product)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-xl mr-3">{{ $product['icon'] }}</div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $product['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $product['sales'] }} sales</p>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-gray-800">${{ number_format($product['revenue'], 0) }}</span>
                </div>
                @endforeach
            </div>
            <button class="w-full mt-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">View All Products</button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Recent Transactions</h3>
            <a href="#" class="text-indigo-600 text-sm font-medium hover:text-indigo-800">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Order ID</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Items</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $transaction['id'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['date'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['items'] }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">${{ number_format($transaction['total'], 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction['status_color'] }}-100 text-{{ $transaction['status_color'] }}-800">{{ $transaction['status'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <button class="text-gray-400 hover:text-indigo-600"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Simple Chart Configuration
        document.addEventListener('livewire:navigated', () => {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
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
        });

        // Initial load check if not navigating
        if (typeof Chart !== 'undefined' && document.getElementById('salesChart')) {
             const ctx = document.getElementById('salesChart').getContext('2d');
             // ... same chart code ...
             // Actually, to avoid duplication, I should wrap it in a function.
        }
    </script>
    @script
    <script>
        const initChart = () => {
            if(!document.getElementById('salesChart')) return;
            const ctx = document.getElementById('salesChart').getContext('2d');
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

        initChart();
    </script>
    @endscript
</div>
