<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Sales Report - Modern POS')]
class extends Component
{
    public $transactions = [
        ['id' => '#ORD-001', 'date' => '2023-10-24 10:30 AM', 'customer' => 'Walk-in Customer', 'total' => 45.00, 'status' => 'Completed'],
        ['id' => '#ORD-002', 'date' => '2023-10-24 11:15 AM', 'customer' => 'John Doe', 'total' => 120.50, 'status' => 'Completed'],
        ['id' => '#ORD-003', 'date' => '2023-10-24 12:45 PM', 'customer' => 'Jane Smith', 'total' => 32.00, 'status' => 'Completed'],
        ['id' => '#ORD-004', 'date' => '2023-10-24 01:20 PM', 'customer' => 'Walk-in Customer', 'total' => 15.00, 'status' => 'Refunded'],
        ['id' => '#ORD-005', 'date' => '2023-10-24 02:00 PM', 'customer' => 'Mike Johnson', 'total' => 210.00, 'status' => 'Completed'],
        ['id' => '#ORD-006', 'date' => '2023-10-24 02:45 PM', 'customer' => 'Sarah Williams', 'total' => 55.00, 'status' => 'Completed'],
        ['id' => '#ORD-007', 'date' => '2023-10-24 03:10 PM', 'customer' => 'Walk-in Customer', 'total' => 12.50, 'status' => 'Completed'],
        ['id' => '#ORD-008', 'date' => '2023-10-24 04:30 PM', 'customer' => 'David Brown', 'total' => 89.90, 'status' => 'Completed'],
        ['id' => '#ORD-009', 'date' => '2023-10-24 05:15 PM', 'customer' => 'Emily Davis', 'total' => 42.00, 'status' => 'Pending'],
        ['id' => '#ORD-010', 'date' => '2023-10-24 06:00 PM', 'customer' => 'Walk-in Customer', 'total' => 28.00, 'status' => 'Completed'],
        ['id' => '#ORD-011', 'date' => '2023-10-24 06:45 PM', 'customer' => 'Michael Wilson', 'total' => 150.00, 'status' => 'Completed'],
        ['id' => '#ORD-012', 'date' => '2023-10-24 07:30 PM', 'customer' => 'Jessica Taylor', 'total' => 65.50, 'status' => 'Completed'],
    ];

    public function getStatusColor($status)
    {
        return match($status) {
            'Completed' => 'bg-green-100 text-green-800',
            'Refunded' => 'bg-red-100 text-red-800',
            'Pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
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
                <h1 class="text-2xl font-semibold text-gray-800">Sales Report</h1>
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
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6" x-data="{
            initChart() {
                const ctx = document.getElementById('reportChart').getContext('2d');
                if (window.myReportChart) {
                    window.myReportChart.destroy();
                }
                window.myReportChart = new Chart(ctx, {
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
        }" x-init="initChart(); Livewire.hook('morph.updated', () => { initChart(); });">

            <!-- Report Controls -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div class="flex bg-white rounded-lg p-1 shadow-sm border border-gray-200">
                    <button class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">Daily</button>
                    <button class="px-4 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 transition-colors">Weekly</button>
                    <button class="px-4 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 transition-colors">Monthly</button>
                </div>

                <div class="flex space-x-3">
                    <button class="flex items-center px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </button>
                    <button class="flex items-center px-4 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-800">$1,250.00</h3>
                        </div>
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 mt-2 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>+12.5%</span>
                        <span class="text-gray-400 ml-1">vs last period</span>
                    </p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                     <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800">45</h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                            <i class="fas fa-shopping-bag text-xl"></i>
                        </div>
                    </div>
                     <p class="text-xs text-green-600 mt-2 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>+5.2%</span>
                        <span class="text-gray-400 ml-1">vs last period</span>
                    </p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                     <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Avg. Order Value</p>
                            <h3 class="text-2xl font-bold text-gray-800">$27.80</h3>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                    </div>
                     <p class="text-xs text-red-600 mt-2 flex items-center">
                        <i class="fas fa-arrow-down mr-1"></i>
                        <span>-2.1%</span>
                        <span class="text-gray-400 ml-1">vs last period</span>
                    </p>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Overview</h3>
                <div class="relative h-80 w-full">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

            <!-- Detailed Report Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Detailed Transactions</h3>
                    <div class="relative">
                        <input type="text" placeholder="Search orders..." class="pl-8 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            @foreach($transactions as $transaction)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-indigo-600">{{ $transaction['id'] }}</td>
                                <td class="px-6 py-4">{{ $transaction['date'] }}</td>
                                <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">${{ number_format($transaction['total'], 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusColor($transaction['status']) }}">
                                        {{ $transaction['status'] }}
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
