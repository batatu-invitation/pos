<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Sales Report - Modern POS')]
class extends Component
{
    public function with()
    {
        $salesQuery = Sale::query()->with(['customer']);
        
        $totalRevenue = Sale::where('status', 'completed')->sum('total_amount');
        $totalOrders = Sale::count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Chart Data (Last 7 Days)
        $dates = collect(range(6, 0))->map(function($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });
        
        $chartData = $dates->map(function($date) {
            return Sale::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        });
        
        $chartLabels = $dates->map(function($date) {
            return Carbon::parse($date)->format('D');
        });

        return [
            'sales' => $salesQuery->latest()->paginate(10),
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ];
    }

    public function getStatusColor($status)
    {
        return match(strtolower($status)) {
            'completed' => 'bg-green-100 text-green-800',
            'refunded' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function exportExcel()
    {
        return Excel::download(new SalesExport, 'sales-report.xlsx');
    }

    public function exportPdf()
    {
        $sales = Sale::with(['customer', 'user'])->latest()->get();
        $pdf = Pdf::loadView('pdf.sales-report', compact('sales'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'sales-report.pdf');
    }
}; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        

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
                        labels: @js($chartLabels),
                        datasets: [{
                            label: '{{ __('Sales (Rp.)') }}',
                            data: @js($chartData),
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
                    <button wire:click="exportPdf" class="flex items-center px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </button>
                    <button wire:click="exportExcel" class="flex items-center px-4 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
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
                            <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                        </div>
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                     <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ number_format($totalOrders, 0, ',', '.') }}</h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                            <i class="fas fa-shopping-bag text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                     <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Avg. Order Value</p>
                            <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($avgOrderValue, 0, ',', '.') }}</h3>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Overview (Last 7 Days)</h3>
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
                            @foreach($sales as $sale)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-indigo-600">{{ $sale->invoice_number }}</td>
                                <td class="px-6 py-4">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">Rp. {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusColor($sale->status) }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $sales->links() }}
                </div>
            </div>

        </main>
    </div>
</div>
