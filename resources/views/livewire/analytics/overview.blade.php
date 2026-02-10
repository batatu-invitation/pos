<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Analytics Overview - Modern POS')]
class extends Component
{
    use WithPagination;

    public $dateRange = '30_days';
    public $totalSales = 0;
    public $totalOrders = 0;
    public $newCustomers = 0;
    public $avgTransaction = 0;
    public $totalReceivable = 0;
    public $totalPayable = 0;
    public $topProducts = [];
    public $chartData = [];

    // Comparison percentages (vs last period)
    public $salesGrowth = 0;
    public $ordersGrowth = 0;
    public $customersGrowth = 0;
    public $avgTransactionGrowth = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function updatedDateRange()
    {
        $this->loadData();
        $this->dispatch('update-chart', data: $this->chartData);
    }

    public function loadData()
    {
        // Define dates
        $endDate = Carbon::now();

        switch ($this->dateRange) {
            case '7_days':
                $startDate = Carbon::now()->subDays(7);
                $prevEndDate = $startDate->copy()->subDay();
                $prevStartDate = $prevEndDate->copy()->subDays(7);
                break;

            case '30_days':
                $startDate = Carbon::now()->subDays(30);
                $prevEndDate = $startDate->copy()->subDay();
                $prevStartDate = $prevEndDate->copy()->subDays(30);
                break;

            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                // Compare with same period last month
                $prevStartDate = Carbon::now()->subMonth()->startOfMonth();
                $prevEndDate = Carbon::now()->subMonth(); // Same day of last month
                break;

            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                // Compare with month before last
                $prevStartDate = Carbon::now()->subMonths(2)->startOfMonth();
                $prevEndDate = Carbon::now()->subMonths(2)->endOfMonth();
                break;

            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                // Compare with same period last year
                $prevStartDate = Carbon::now()->subYear()->startOfYear();
                $prevEndDate = Carbon::now()->subYear(); // Same day of last year
                break;

            default:
                $startDate = Carbon::now()->subDays(30);
                $prevEndDate = $startDate->copy()->subDay();
                $prevStartDate = $prevEndDate->copy()->subDays(30);
        }

        // 1. Total Sales & Orders
        $currentSalesQuery = Sale::whereBetween('created_at', [$startDate, $endDate])->where('status', 'completed');
        $this->totalSales = $currentSalesQuery->sum('total_amount');
        $this->totalOrders = $currentSalesQuery->count();

        $prevSalesQuery = Sale::whereBetween('created_at', [$prevStartDate, $prevEndDate])->where('status', 'completed');
        $prevTotalSales = $prevSalesQuery->sum('total_amount');
        $prevTotalOrders = $prevSalesQuery->count();

        $this->salesGrowth = $this->calculateGrowth($this->totalSales, $prevTotalSales);
        $this->ordersGrowth = $this->calculateGrowth($this->totalOrders, $prevTotalOrders);

        // 2. New Customers
        $this->newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        $prevNewCustomers = Customer::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();
        $this->customersGrowth = $this->calculateGrowth($this->newCustomers, $prevNewCustomers);

        // 3. Avg Transaction
        $this->avgTransaction = $this->totalOrders > 0 ? $this->totalSales / $this->totalOrders : 0;
        $prevAvgTransaction = $prevTotalOrders > 0 ? $prevTotalSales / $prevTotalOrders : 0;
        $this->avgTransactionGrowth = $this->calculateGrowth($this->avgTransaction, $prevAvgTransaction);

        // 4. Receivables & Payables (Current Outstanding)
        $this->totalReceivable = Sale::where('payment_status', '!=', 'paid')->sum(DB::raw('total_amount - cash_received'));
        $this->totalPayable = Purchase::where('status', '!=', 'paid')->sum(DB::raw('total_amount - paid_amount'));

        // 5. Top Products
        $this->topProducts = SaleItem::select('product_name as name', DB::raw('SUM(quantity) as sales'), DB::raw('SUM(total_price) as revenue'))
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->completed();
            })
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->toArray();

        // 6. Chart Data
        $chartQuery = Sale::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates
        $period = CarbonPeriod::create($startDate, $endDate);
        $labels = [];
        $data = [];

        foreach($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format('D, d M');
            $record = $chartQuery->firstWhere('date', $formattedDate);
            $data[] = $record ? $record->total : 0;
        }

        $this->chartData = [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    public function with()
    {
        return [
            'recentActivities' => $this->getRecentActivities(),
        ];
    }

    public function getRecentActivities()
    {
        $sales = Sale::query()->select('id', 'created_at', DB::raw("'sale' as type"));
        $customers = Customer::query()->select('id', 'created_at', DB::raw("'customer' as type"));

        $query = $sales->union($customers)->orderBy('created_at', 'desc');

        $paginated = $query->paginate(10);

        $transformed = $paginated->getCollection()->map(function ($item) {
             if ($item->type === 'sale') {
                 $sale = Sale::with('user', 'customer')->find($item->id);
                 if (!$sale) return null; // Handle deleted or missing
                 return [
                    'action' => 'New Order #' . $sale->invoice_number,
                    'user' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'time' => $sale->created_at->diffForHumans(),
                    'amount' => $sale->total_amount,
                    'status' => ucfirst($sale->status),
                    'type' => 'success',
                    'details' => 'Order processed by ' . ($sale->user ? $sale->user->name : 'System'),
                    'created_at' => $sale->created_at
                 ];
             } else {
                 $customer = Customer::find($item->id);
                 if (!$customer) return null;
                 return [
                    'action' => 'New Customer',
                    'user' => $customer->name,
                    'time' => $customer->created_at->diffForHumans(),
                    'amount' => null,
                    'status' => 'Registered',
                    'type' => 'info',
                    'details' => 'Customer added to system',
                    'created_at' => $customer->created_at
                 ];
             }
        })->filter()->values();

        $paginated->setCollection($transformed);

        return $paginated;
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6 custom-scrollbar"
     x-data="{
         chart: null,
         isDark: document.documentElement.classList.contains('dark'),
         init() {
             this.$nextTick(() => {
                 if (this.$el.dataset.chart) {
                    this.renderChart(JSON.parse(this.$el.dataset.chart));
                 }
             });

             const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        this.isDark = document.documentElement.classList.contains('dark');
                        if (this.chart) {
                            this.updateChartTheme();
                        }
                    }
                });
             });
             observer.observe(document.documentElement, { attributes: true });
         },
         updateChartTheme() {
            if (!this.chart) return;
            const textColor = this.isDark ? '#9ca3af' : '#4b5563';
            const gridColor = this.isDark ? '#374151' : '#f3f4f6';
            
            this.chart.options.scales.x.grid.color = gridColor;
            this.chart.options.scales.y.grid.color = gridColor;
            this.chart.options.scales.x.ticks.color = textColor;
            this.chart.options.scales.y.ticks.color = textColor;
            this.chart.update();
         },
         renderChart(data) {
             if (!data) return;

             const ctx = this.$refs.revenueChart;
             if (!ctx) return;

             if (this.chart) {
                 this.chart.destroy();
             }

             const textColor = this.isDark ? '#9ca3af' : '#4b5563';
             const gridColor = this.isDark ? '#374151' : '#f3f4f6';

             this.chart = new Chart(ctx, {
                 type: 'line',
                 data: {
                     labels: data.labels,
                     datasets: [{
                         label: '{{ __('Revenue') }}',
                         data: data.data,
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
                                 color: gridColor
                             },
                             ticks: {
                                 color: textColor,
                                 callback: function(value) {
                                     return 'Rp ' + value.toLocaleString();
                                 }
                             }
                         },
                         x: {
                             grid: {
                                 display: false,
                                 color: gridColor
                             },
                             ticks: {
                                 color: textColor
                             }
                         }
                     }
                 }
             });
         }
     }"
     data-chart='@json($chartData, JSON_HEX_APOS)'
     @update-chart.window="renderChart($event.detail.data)">



    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">{{ __('Business Overview') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Key performance indicators for your business.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="relative">
                <i class="fas fa-calendar absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <select wire:model.live="dateRange" class="pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                    <option value="7_days">{{ __('Last 7 Days') }}</option>
                    <option value="30_days">{{ __('Last 30 Days') }}</option>
                    <option value="this_month">{{ __('This Month') }}</option>
                    <option value="last_month">{{ __('Last Month') }}</option>
                    <option value="this_year">{{ __('This Year') }}</option>
                </select>
            </div>
            <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                <i class="fas fa-download mr-2"></i> {{ __('Report') }}
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Sales -->
        <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Sales') }}</span>
                    <i class="fas fa-dollar-sign text-indigo-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">Rp. {{ number_format($totalSales, 0, ',', '.') }}</h3>
                <div class="flex items-center text-sm text-indigo-100 opacity-90">
                    <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-arrow-{{ $salesGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($salesGrowth), 1) }}%
                    </span>
                    <span>{{ __('vs last period') }}</span>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Total Orders') }}</span>
                    <i class="fas fa-shopping-bag text-blue-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($totalOrders) }}</h3>
                <div class="flex items-center text-sm text-blue-100 opacity-90">
                    <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-arrow-{{ $ordersGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                    </span>
                    <span>{{ __('vs last period') }}</span>
                </div>
            </div>
        </div>

        <!-- New Customers -->
        <div class="bg-gradient-to-br from-purple-500 to-fuchsia-600 rounded-3xl p-6 text-white shadow-lg shadow-purple-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('New Customers') }}</span>
                    <i class="fas fa-users text-purple-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($newCustomers) }}</h3>
                <div class="flex items-center text-sm text-purple-100 opacity-90">
                    <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-arrow-{{ $customersGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($customersGrowth), 1) }}%
                    </span>
                    <span>{{ __('vs last period') }}</span>
                </div>
            </div>
        </div>

        <!-- Avg Transaction -->
        <div class="bg-gradient-to-br from-orange-400 to-amber-500 rounded-3xl p-6 text-white shadow-lg shadow-orange-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Avg. Transaction') }}</span>
                    <i class="fas fa-receipt text-orange-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">Rp. {{ number_format($avgTransaction, 0, ',', '.') }}</h3>
                <div class="flex items-center text-sm text-orange-100 opacity-90">
                    <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-arrow-{{ $avgTransactionGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($avgTransactionGrowth), 1) }}%
                    </span>
                    <span>{{ __('vs last period') }}</span>
                </div>
            </div>
        </div>

        <!-- Accounts Receivable -->
        <div class="bg-gradient-to-br from-teal-400 to-emerald-500 rounded-3xl p-6 text-white shadow-lg shadow-teal-200 dark:shadow-none relative overflow-hidden group cursor-pointer" wire:navigate href="{{ route('analytics.accounts-receivable') }}">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Receivables') }}</span>
                    <i class="fas fa-hand-holding-usd text-teal-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">Rp. {{ number_format($totalReceivable, 0, ',', '.') }}</h3>
                <div class="flex items-center justify-between text-sm text-teal-100 opacity-90 mt-2">
                    <span>Unpaid Invoices</span>
                    <span class="flex items-center bg-white/20 px-2 py-1 rounded-lg text-xs hover:bg-white/30 transition-colors">
                        View Details <i class="fas fa-arrow-right ml-1"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Accounts Payable -->
        <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-3xl p-6 text-white shadow-lg shadow-rose-200 dark:shadow-none relative overflow-hidden group cursor-pointer" wire:navigate href="{{ route('analytics.accounts-payable') }}">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Payables') }}</span>
                    <i class="fas fa-file-invoice-dollar text-rose-100 text-xl"></i>
                </div>
                <h3 class="text-3xl font-bold mb-1">Rp. {{ number_format($totalPayable, 0, ',', '.') }}</h3>
                <div class="flex items-center justify-between text-sm text-rose-100 opacity-90 mt-2">
                    <span>Unpaid Bills</span>
                    <span class="flex items-center bg-white/20 px-2 py-1 rounded-lg text-xs hover:bg-white/30 transition-colors">
                        View Details <i class="fas fa-arrow-right ml-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-8" wire:ignore>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">{{ __('Revenue Analytics') }}</h3>
        <div class="relative h-80 w-full">
            <canvas x-ref="revenueChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Products -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-800 dark:text-white">{{ __('Top Selling Products') }}</h3>
                <a href="#" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('View All') }}</a>
            </div>
            <div class="p-0">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Product') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Sales') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        @foreach($topProducts as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-3 flex items-center">
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 mr-3"></div>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $product['name'] }}</span>
                            </td>
                            <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-400">{{ $product['sales'] }}</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-900 dark:text-gray-100">Rp. {{ number_format($product['revenue'], 0 , ',', '.' ) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activities Table -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden mt-8">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ __('Recent System Activities') }}</h3>
            <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">{{ __('View All Logs') }}</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-medium">{{ __('Activity') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('User') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Time') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentActivities as $activity)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center mr-3
                                    {{ $activity['type'] === 'success' ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' :
                                       ($activity['type'] === 'warning' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                       ($activity['type'] === 'danger' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400')) }}">
                                    <i class="fas fa-{{ $activity['type'] === 'success' ? 'check' :
                                       ($activity['type'] === 'warning' ? 'exclamation' :
                                       ($activity['type'] === 'danger' ? 'times' : 'info')) }}"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['details'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $activity['user'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $activity['time'] }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium {{ $activity['amount'] && $activity['amount'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $activity['amount'] ? ($activity['amount'] < 0 ? '-Rp. ' . number_format(abs($activity['amount']), ) : 'Rp. ' . number_format($activity['amount'], 0 , ',', '.' )) : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize
                                {{ $activity['type'] === 'success' ? 'bg-green-100 text-green-800' :
                                   ($activity['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                                   ($activity['type'] === 'danger' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $activity['status'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $recentActivities->links() }}
        </div>
    </div>
</div>
