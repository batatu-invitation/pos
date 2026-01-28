<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app', ['header' => 'Analytics Overview'])]
#[Title('Analytics Overview - Modern POS')]
class extends Component
{
    public $topProducts = [
        ['name' => 'Wireless Mouse', 'sales' => 124, 'revenue' => 2480.00],
        ['name' => 'Mechanical Keyboard', 'sales' => 85, 'revenue' => 8500.00],
        ['name' => 'USB-C Cable (2m)', 'sales' => 200, 'revenue' => 2000.00],
        ['name' => '27" 4K Monitor', 'sales' => 15, 'revenue' => 5250.00],
        ['name' => 'Ergonomic Chair', 'sales' => 22, 'revenue' => 6600.00],
        ['name' => 'Laptop Stand', 'sales' => 45, 'revenue' => 1350.00],
        ['name' => 'Webcam 1080p', 'sales' => 30, 'revenue' => 1800.00],
        ['name' => 'Noise Cancelling Headphones', 'sales' => 18, 'revenue' => 3600.00],
        ['name' => 'Wireless Charger', 'sales' => 60, 'revenue' => 1500.00],
        ['name' => 'Bluetooth Speaker', 'sales' => 40, 'revenue' => 2400.00],
        ['name' => 'Gaming Mouse Pad', 'sales' => 75, 'revenue' => 1125.00],
        ['name' => 'External SSD 1TB', 'sales' => 25, 'revenue' => 3000.00],
    ];

    public $recentActivities = [
        ['action' => 'New Order #ORD-001', 'user' => 'Walk-in Customer', 'time' => '2 mins ago', 'amount' => 45.00, 'status' => 'Completed', 'type' => 'success', 'details' => 'Purchased 2x Wireless Mouse'],
        ['action' => 'Stock Update', 'user' => 'System', 'time' => '15 mins ago', 'amount' => null, 'status' => 'Processed', 'type' => 'info', 'details' => 'Added 50 units of USB-C Cable'],
        ['action' => 'Refund Processed', 'user' => 'Admin', 'time' => '1 hour ago', 'amount' => -15.00, 'status' => 'Refunded', 'type' => 'warning', 'details' => 'Returned faulty keyboard'],
        ['action' => 'New Customer', 'user' => 'Jane Smith', 'time' => '2 hours ago', 'amount' => null, 'status' => 'Registered', 'type' => 'success', 'details' => 'Customer account created via web'],
        ['action' => 'Payment Failed', 'user' => 'Online Order', 'time' => '3 hours ago', 'amount' => 120.00, 'status' => 'Failed', 'type' => 'danger', 'details' => 'Card declined: Insufficient funds'],
        ['action' => 'Shift Closed', 'user' => 'John Doe', 'time' => '4 hours ago', 'amount' => 1250.00, 'status' => 'Closed', 'type' => 'info', 'details' => 'Register #1 closed successfully'],
        ['action' => 'New Order #ORD-002', 'user' => 'Mike Johnson', 'time' => '4.5 hours ago', 'amount' => 210.00, 'status' => 'Completed', 'type' => 'success', 'details' => 'Purchased 1x Monitor'],
        ['action' => 'Inventory Alert', 'user' => 'System', 'time' => '5 hours ago', 'amount' => null, 'status' => 'Low Stock', 'type' => 'warning', 'details' => 'Wireless Mouse stock below 5'],
        ['action' => 'Settings Updated', 'user' => 'Super Admin', 'time' => '6 hours ago', 'amount' => null, 'status' => 'Updated', 'type' => 'info', 'details' => 'Changed tax rate to 10%'],
        ['action' => 'New Order #ORD-003', 'user' => 'Sarah Williams', 'time' => '7 hours ago', 'amount' => 55.00, 'status' => 'Completed', 'type' => 'success', 'details' => 'Purchased 1x Webcam'],
        ['action' => 'Login Attempt', 'user' => 'Unknown', 'time' => '8 hours ago', 'amount' => null, 'status' => 'Blocked', 'type' => 'danger', 'details' => 'Failed login attempt from IP 192.168.1.1'],
        ['action' => 'Backup Created', 'user' => 'System', 'time' => '10 hours ago', 'amount' => null, 'status' => 'Success', 'type' => 'success', 'details' => 'Daily database backup completed'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6"
     x-data="{
         initCharts() {
             if (document.getElementById('revenueChart')) {
                 const ctx = document.getElementById('revenueChart').getContext('2d');
                 new Chart(ctx, {
                     type: 'line',
                     data: {
                         labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                         datasets: [{
                             label: 'Revenue',
                             data: [1200, 1900, 1500, 2500, 2200, 3000, 2800],
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
                                     borderDash: [2, 4],
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
         }
     }"
     x-init="initCharts(); Livewire.hook('morph.updated', () => { initCharts(); });">

    <!-- Header -->
    <header class="flex items-center justify-between mb-8">
        <div class="flex items-center">
            <h1 class="text-2xl font-semibold text-gray-800">Business Intelligence</h1>
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

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Business Overview</h2>
            <p class="text-sm text-gray-500">Key performance indicators for your business.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="relative">
                <i class="fas fa-calendar absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <select class="pl-8 pr-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>This Month</option>
                    <option>Last Month</option>
                    <option>This Year</option>
                </select>
            </div>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-download mr-2"></i> Report
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Sales -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Sales</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">$24,560</h3>
                </div>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="fas fa-dollar-sign text-lg"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-600 font-medium flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i> 12.5%
                </span>
                <span class="text-gray-400 ml-2">vs last period</span>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">456</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fas fa-shopping-bag text-lg"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-600 font-medium flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i> 8.2%
                </span>
                <span class="text-gray-400 ml-2">vs last period</span>
            </div>
        </div>

        <!-- New Customers -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">New Customers</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">125</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-red-600 font-medium flex items-center">
                    <i class="fas fa-arrow-down mr-1"></i> 2.1%
                </span>
                <span class="text-gray-400 ml-2">vs last period</span>
            </div>
        </div>

        <!-- Avg Transaction -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Avg. Transaction</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">$53.80</h3>
                </div>
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <i class="fas fa-receipt text-lg"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-green-600 font-medium flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i> 4.5%
                </span>
                <span class="text-gray-400 ml-2">vs last period</span>
            </div>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue Analytics</h3>
        <div class="relative h-80 w-full">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">Top Selling Products</h3>
                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
            </div>
            <div class="p-0">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 font-medium">Product</th>
                            <th class="px-6 py-3 font-medium text-right">Sales</th>
                            <th class="px-6 py-3 font-medium text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach($topProducts as $product)
                        <tr>
                            <td class="px-6 py-3 flex items-center">
                                <div class="w-8 h-8 rounded bg-gray-200 mr-3"></div>
                                <span class="font-medium text-gray-800">{{ $product['name'] }}</span>
                            </td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ $product['sales'] }}</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-900">${{ number_format($product['revenue'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activities Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Recent System Activities</h3>
            <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All Logs</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-medium">Activity</th>
                        <th class="px-6 py-3 font-medium">User</th>
                        <th class="px-6 py-3 font-medium">Time</th>
                        <th class="px-6 py-3 font-medium text-right">Amount</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentActivities as $activity)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center mr-3
                                    {{ $activity['type'] === 'success' ? 'bg-green-100 text-green-600' :
                                       ($activity['type'] === 'warning' ? 'bg-yellow-100 text-yellow-600' :
                                       ($activity['type'] === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600')) }}">
                                    <i class="fas fa-{{ $activity['type'] === 'success' ? 'check' :
                                       ($activity['type'] === 'warning' ? 'exclamation' :
                                       ($activity['type'] === 'danger' ? 'times' : 'info')) }}"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $activity['action'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['details'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $activity['user'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $activity['time'] }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium {{ $activity['amount'] && $activity['amount'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $activity['amount'] ? ($activity['amount'] < 0 ? '-$' . number_format(abs($activity['amount']), 2) : '$' . number_format($activity['amount'], 2)) : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize
                                {{ $activity['type'] === 'success' ? 'bg-green-100 text-green-800' :
                                   ($activity['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                                   ($activity['type'] === 'danger' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $activity['type'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
