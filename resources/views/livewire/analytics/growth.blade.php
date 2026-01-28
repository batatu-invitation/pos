<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
    #[Title('Company Growth - Modern POS')]
    class extends Component
{
    public $growthHistory = [
        ['period' => 'Oct 2023', 'revenue' => 25000.00, 'growth_rate' => 12.5, 'customers' => 320, 'avg_order' => 45.50, 'status' => 'Excellent'],
        ['period' => 'Sep 2023', 'revenue' => 22222.00, 'growth_rate' => 8.2, 'customers' => 280, 'avg_order' => 42.00, 'status' => 'Good'],
        ['period' => 'Aug 2023', 'revenue' => 20536.00, 'growth_rate' => 5.1, 'customers' => 265, 'avg_order' => 41.50, 'status' => 'Good'],
        ['period' => 'Jul 2023', 'revenue' => 19540.00, 'growth_rate' => -2.4, 'customers' => 240, 'avg_order' => 40.00, 'status' => 'Average'],
        ['period' => 'Jun 2023', 'revenue' => 20020.00, 'growth_rate' => 15.0, 'customers' => 255, 'avg_order' => 39.50, 'status' => 'Excellent'],
        ['period' => 'May 2023', 'revenue' => 17408.00, 'growth_rate' => 4.5, 'customers' => 210, 'avg_order' => 38.00, 'status' => 'Good'],
        ['period' => 'Apr 2023', 'revenue' => 16658.00, 'growth_rate' => 6.8, 'customers' => 195, 'avg_order' => 37.50, 'status' => 'Good'],
        ['period' => 'Mar 2023', 'revenue' => 15600.00, 'growth_rate' => 10.2, 'customers' => 180, 'avg_order' => 36.00, 'status' => 'Excellent'],
        ['period' => 'Feb 2023', 'revenue' => 14156.00, 'growth_rate' => -1.5, 'customers' => 160, 'avg_order' => 35.50, 'status' => 'Average'],
        ['period' => 'Jan 2023', 'revenue' => 14371.00, 'growth_rate' => 3.0, 'customers' => 155, 'avg_order' => 35.00, 'status' => 'Average'],
        ['period' => 'Dec 2022', 'revenue' => 13952.00, 'growth_rate' => 20.5, 'customers' => 150, 'avg_order' => 48.00, 'status' => 'Excellent'],
        ['period' => 'Nov 2022', 'revenue' => 11578.00, 'growth_rate' => 5.5, 'customers' => 130, 'avg_order' => 34.00, 'status' => 'Good'],
    ];
}; ?>

<div x-data="{
    initCharts() {
        // Revenue Growth Chart (Line)
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        if (window.growthChartInstance) {
            window.growthChartInstance.destroy();
        }
        window.growthChartInstance = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: ['2020', '2021', '2022', '2023', '2024'],
                datasets: [{
                    label: 'Annual Revenue ($)',
                    data: [50000, 75000, 120000, 180000, 250000],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [2, 4] }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // Monthly Comparison Chart (Bar)
        const monthlyCtx = document.getElementById('monthlyCompChart').getContext('2d');
        if (window.monthlyCompChartInstance) {
            window.monthlyCompChartInstance.destroy();
        }
        window.monthlyCompChartInstance = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: '2024',
                        data: [12000, 15000, 14000, 18000, 22000, 25000],
                        backgroundColor: '#4f46e5',
                        borderRadius: 4
                    },
                    {
                        label: '2023',
                        data: [10000, 11000, 12000, 13000, 15000, 17000],
                        backgroundColor: '#e5e7eb',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [2, 4] }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // Customer Acquisition Chart (Line)
        const customerCtx = document.getElementById('customerChart').getContext('2d');
        if (window.customerChartInstance) {
            window.customerChartInstance.destroy();
        }
        window.customerChartInstance = new Chart(customerCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Customers',
                    data: [120, 150, 180, 220, 250, 300],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
}" x-init="initCharts(); Livewire.hook('morph.updated', () => { initCharts(); });" class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- YoY Growth -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Annual Revenue Growth</p>
                    <h3 class="text-2xl font-bold text-gray-800">+25.4%</h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Compared to last year</p>
        </div>

        <!-- New Customers -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">New Customer Rate</p>
                    <h3 class="text-2xl font-bold text-gray-800">+12.8%</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-green-600 mt-2 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> 2.1% from last month
            </p>
        </div>

        <!-- Retention -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Customer Retention</p>
                    <h3 class="text-2xl font-bold text-gray-800">88.5%</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <i class="fas fa-hand-holding-heart text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Returning customers</p>
        </div>

        <!-- Market Share -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Avg. Order Value</p>
                    <h3 class="text-2xl font-bold text-gray-800">$42.50</h3>
                </div>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
            </div>
                <p class="text-xs text-green-600 mt-2 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> 5.3% YoY
            </p>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue Growth Trend (5 Years)</h3>
            <div class="relative h-72 w-full">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

        <!-- Monthly Comparison -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Monthly Performance</h3>
                <div class="flex space-x-2">
                    <span class="flex items-center text-xs text-gray-500">
                        <span class="w-3 h-3 bg-indigo-500 rounded-full mr-1"></span> 2024
                    </span>
                    <span class="flex items-center text-xs text-gray-500">
                        <span class="w-3 h-3 bg-gray-300 rounded-full mr-1"></span> 2023
                    </span>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="monthlyCompChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Customer Acquisition -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Customer Acquisition vs Churn</h3>
            <div class="relative h-64 w-full">
                <canvas id="customerChart"></canvas>
            </div>
        </div>

        <!-- Top Growing Categories -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Growing Categories</h3>
            <div class="space-y-4">
                <!-- 1. Fast Food -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-3">
                            <i class="fas fa-hamburger"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Fast Food</h4>
                            <p class="text-xs text-gray-500">1,240 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+18%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>

                <!-- 2. Beverages -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Beverages</h4>
                            <p class="text-xs text-gray-500">3,500 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+12%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>

                <!-- 3. Fresh Food -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                            <i class="fas fa-carrot"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Fresh Food</h4>
                            <p class="text-xs text-gray-500">890 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+8%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>

                <!-- 4. Electronics -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 mr-3">
                            <i class="fas fa-plug"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Electronics</h4>
                            <p class="text-xs text-gray-500">450 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+15%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>

                <!-- 5. Apparel -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 mr-3">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Apparel</h4>
                            <p class="text-xs text-gray-500">1,120 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+9%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>

                <!-- 6. Home & Garden -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-3">
                            <i class="fas fa-home"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Home & Garden</h4>
                            <p class="text-xs text-gray-500">780 Sales</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-green-600 font-bold">+6%</span>
                        <span class="text-xs text-gray-400">Growth</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed History Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Growth History</h3>
            <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View Full Report</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-medium">Period</th>
                        <th class="px-6 py-3 font-medium">Revenue</th>
                        <th class="px-6 py-3 font-medium">Growth Rate</th>
                        <th class="px-6 py-3 font-medium">New Customers</th>
                        <th class="px-6 py-3 font-medium">Avg Order Value</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($growthHistory as $history)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $history['period'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">${{ number_format($history['revenue'], 2) }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="{{ $history['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                <i class="fas fa-{{ $history['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                                {{ abs($history['growth_rate']) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($history['customers']) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">${{ number_format($history['avg_order'], 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $history['status'] === 'Excellent' ? 'bg-green-100 text-green-800' :
                                   ($history['status'] === 'Good' ? 'bg-blue-100 text-blue-800' :
                                   ($history['status'] === 'Average' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                {{ $history['status'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
