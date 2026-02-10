<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new
#[Layout('components.layouts.app')]
#[Title('Sales Report - Modern POS')]
class extends Component
{
    public $startDate;
    public $endDate;
    public $statusFilter = 'completed';
    public $paymentMethodFilter = '';
    public $userFilter = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function with()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $query = Sale::query()
            ->with(['customer', 'user', 'items.product.category'])
            ->whereBetween('created_at', [$start, $end]);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        $sales = $query->latest()->get(); // Get all for calculations

        // 1. Summary Metrics
        $totalRevenue = $sales->sum('total_amount');
        $totalOrders = $sales->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // Calculate Cost & Profit (Approximate based on current product cost)
        $totalCost = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                // If product exists, use its cost. If deleted, assume 0 or handle gracefully.
                $cost = $item->product ? $item->product->cost : 0; 
                $totalCost += $cost * $item->quantity;
            }
        }
        $grossProfit = $totalRevenue - $totalCost;
        $margin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // 2. Sales by Payment Method
        $salesByPaymentMethod = $sales->groupBy('payment_method')->map(function ($group) {
            return $group->sum('total_amount');
        });

        // 3. Sales by Category
        $salesByCategory = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product && $item->product->category) {
                    $catName = $item->product->category->name;
                    if (!isset($salesByCategory[$catName])) {
                        $salesByCategory[$catName] = 0;
                    }
                    $salesByCategory[$catName] += $item->total_price;
                } else {
                    $catName = 'Uncategorized';
                    if (!isset($salesByCategory[$catName])) {
                        $salesByCategory[$catName] = 0;
                    }
                    $salesByCategory[$catName] += $item->total_price;
                }
            }
        }
        arsort($salesByCategory);

        // 4. Chart Data (Daily)
        $chartData = [];
        $chartLabels = [];
        
        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $chartLabels[] = $date->format('d M');
            $daySales = $sales->filter(function ($sale) use ($date) {
                return $sale->created_at->format('Y-m-d') === $date->format('Y-m-d');
            });
            $chartData[] = $daySales->sum('total_amount');
        }

        // Pagination for the list
        $paginatedSales = Sale::query()
            ->with(['customer', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentMethodFilter, fn($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->when($this->userFilter, fn($q) => $q->where('user_id', $this->userFilter))
            ->latest()
            ->paginate(10);

        return [
            'sales' => $paginatedSales,
            'users' => User::all(),
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'totalCost' => $totalCost,
            'grossProfit' => $grossProfit,
            'margin' => $margin,
            'salesByPaymentMethod' => $salesByPaymentMethod,
            'salesByCategory' => $salesByCategory,
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
        
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none mr-4 md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-semibold text-gray-800">Sales Report</h1>
            </div>
            
            <div class="flex items-center space-x-4">
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
                                    drawBorder: false
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
        }" x-init="initChart()" @update-chart.window="initChart()">
        
            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input wire:model.live="startDate" type="date" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input wire:model.live="endDate" type="date" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model.live="statusFilter" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select wire:model.live="paymentMethodFilter" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div>
                         <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                         <select wire:model.live="userFilter" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Employees</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                    <p class="text-xs text-green-500 mt-1"><i class="fas fa-arrow-up"></i> {{ $totalOrders }} Orders</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Cost (COGS)</p>
                    <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($totalCost, 0, ',', '.') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Est. based on current cost</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Gross Profit</p>
                    <h3 class="text-2xl font-bold text-green-600">Rp. {{ number_format($grossProfit, 0, ',', '.') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Revenue - COGS</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Net Margin</p>
                    <h3 class="text-2xl font-bold text-indigo-600">{{ number_format($margin, 1) }}%</h3>
                    <p class="text-xs text-gray-500 mt-1">Profit / Revenue</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Chart -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Trend</h3>
                    <div class="h-64">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>

                <!-- Sales by Category -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top Categories</h3>
                    <div class="space-y-4">
                        @foreach(collect($salesByCategory)->take(5) as $category => $amount)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $category }}</span>
                            <span class="text-sm font-semibold text-gray-900">Rp. {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            @php $percent = $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0; @endphp
                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $percent }}%"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Sales Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Sales Transactions</h3>
                    
                    <div class="flex gap-2" x-data="{ open: false }">
                        <div class="relative">
                            <button @click="open = !open" @click.away="open = false" class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 hover:bg-green-700 transition-colors">
                                <i class="fas fa-file-export"></i> Export
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border py-1" style="display: none;">
                                <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-excel text-green-600 mr-2"></i> Export Excel
                                </button>
                                <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-pdf text-red-600 mr-2"></i> Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Invoice</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Items</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Payment</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $sale->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-indigo-600 font-medium">
                                    {{ $sale->invoice_number }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $sale->customer->name ?? 'Walk-in Customer' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $sale->items->count() }}
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    Rp. {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 capitalize">
                                    {{ $sale->payment_method }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusColor($sale->status) }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions found in this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200">
                    {{ $sales->links() }}
                </div>
            </div>
        </main>
    </div>
</div>