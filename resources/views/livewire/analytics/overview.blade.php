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
        $this->resetPage(); // Reset pagination saat filter berubah
        $this->loadData();
        $this->dispatch('update-chart', data: $this->chartData);
    }

    public function loadData()
    {
        $now = Carbon::now();
        $endDate = $now->copy();

        // 1. Optimized Date Range Logic
        switch ($this->dateRange) {
            case '7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $prevStartDate = $startDate->copy()->subDays(7);
                $prevEndDate = $startDate->copy()->subSecond();
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $prevStartDate = $startDate->copy()->subMonth();
                $prevEndDate = $now->copy()->subMonth();
                break;
            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                $endDate = $now->copy()->subMonth()->endOfMonth();
                $prevStartDate = $startDate->copy()->subMonth();
                $prevEndDate = $endDate->copy()->subMonth();
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $prevStartDate = $startDate->copy()->subYear();
                $prevEndDate = $now->copy()->subYear();
                break;
            default: // 30_days
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $prevStartDate = $startDate->copy()->subDays(30);
                $prevEndDate = $startDate->copy()->subSecond();
        }

        // 2. Aggregate Current and Previous Sales in 2 queries instead of many
        $salesMetrics = Sale::selectRaw("
            SUM(CASE WHEN created_at >= '{$startDate}' THEN total_amount ELSE 0 END) as current_sales,
            COUNT(CASE WHEN created_at >= '{$startDate}' THEN id ELSE NULL END) as current_orders,
            SUM(CASE WHEN created_at BETWEEN '{$prevStartDate}' AND '{$prevEndDate}' THEN total_amount ELSE 0 END) as prev_sales,
            COUNT(CASE WHEN created_at BETWEEN '{$prevStartDate}' AND '{$prevEndDate}' THEN id ELSE NULL END) as prev_orders
        ")->where('status', 'completed')->first();

        $this->totalSales = (float) $salesMetrics->current_sales;
        $this->totalOrders = (int) $salesMetrics->current_orders;
        
        // 3. Customer Growth Metrics
        $customerMetrics = Customer::selectRaw("
            COUNT(CASE WHEN created_at >= '{$startDate}' THEN id ELSE NULL END) as current_new,
            COUNT(CASE WHEN created_at BETWEEN '{$prevStartDate}' AND '{$prevEndDate}' THEN id ELSE NULL END) as prev_new
        ")->first();

        $this->newCustomers = (int) $customerMetrics->current_new;

        // 4. Growth Calculations
        $this->salesGrowth = $this->calculateGrowth($this->totalSales, $salesMetrics->prev_sales);
        $this->ordersGrowth = $this->calculateGrowth($this->totalOrders, $salesMetrics->prev_orders);
        $this->customersGrowth = $this->calculateGrowth($this->newCustomers, $customerMetrics->prev_new);
        
        $this->avgTransaction = $this->totalOrders > 0 ? $this->totalSales / $this->totalOrders : 0;
        $prevAvg = $salesMetrics->prev_orders > 0 ? $salesMetrics->prev_sales / $salesMetrics->prev_orders : 0;
        $this->avgTransactionGrowth = $this->calculateGrowth($this->avgTransaction, $prevAvg);

        // 5. Global Receivables/Payables (Hanya dipanggil sekali)
        $this->totalReceivable = Sale::where('payment_status', '!=', 'paid')->sum(DB::raw('total_amount - cash_received'));
        $this->totalPayable = Purchase::where('status', '!=', 'paid')->sum(DB::raw('total_amount - paid_amount'));

        // 6. Top Products Optimized
        $this->topProducts = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select('product_name as name', DB::raw('SUM(quantity) as sales'), DB::raw('SUM(total_price) as revenue'))
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()->toArray();

        // 7. Chart Data (Optimized Fill missing dates)
        $dailySales = Sale::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = []; $data = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $formatted = $date->format('Y-m-d');
            $labels[] = $date->format('D, d M');
            $data[] = (float) ($dailySales[$formatted] ?? 0);
        }
        $this->chartData = ['labels' => $labels, 'data' => $data];
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous <= 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    public function with()
    {
        return ['recentActivities' => $this->getRecentActivities()];
    }

    public function getRecentActivities()
    {
        // Hindari query manual find() di dalam loop. Gunakan Polymorphic Eager Loading.
        $union = DB::table('sales')
            ->select('id', 'created_at', DB::raw("'sale' as type"))
            ->union(DB::table('customers')->select('id', 'created_at', DB::raw("'customer' as type")))
            ->orderBy('created_at', 'desc');

        $paginated = $union->paginate(10);
        
        // Ambil ID per tipe untuk batch loading (Eager Loading)
        $idsByType = collect($paginated->items())->groupBy('type')->map->pluck('id');

        $sales = isset($idsByType['sale']) 
            ? Sale::with(['user', 'customer'])->whereIn('id', $idsByType['sale'])->get()->keyBy('id') 
            : collect();
        $customers = isset($idsByType['customer']) 
            ? Customer::whereIn('id', $idsByType['customer'])->get()->keyBy('id') 
            : collect();

        // Transformasi koleksi yang sudah di-load secara batch
        $transformed = collect($paginated->items())->map(function ($item) use ($sales, $customers) {
            if ($item->type === 'sale') {
                $sale = $sales->get($item->id);
                if (!$sale) return null;
                return [
                    'action' => 'New Order #' . $sale->invoice_number,
                    'user' => $sale->customer->name ?? 'Walk-in Customer',
                    'time' => Carbon::parse($item->created_at)->diffForHumans(),
                    'amount' => $sale->total_amount,
                    'status' => ucfirst($sale->status),
                    'type' => 'success',
                    'details' => 'Processed by ' . ($sale->user->name ?? 'System'),
                ];
            } else {
                $customer = $customers->get($item->id);
                if (!$customer) return null;
                return [
                    'action' => 'New Customer',
                    'user' => $customer->name,
                    'time' => Carbon::parse($item->created_at)->diffForHumans(),
                    'amount' => null,
                    'status' => 'Registered',
                    'type' => 'info',
                    'details' => 'Customer added to system',
                ];
            }
        })->filter()->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $transformed,
            $paginated->total(),
            $paginated->perPage(),
            $paginated->currentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }
}; ?>

<div class="mx-auto p-6 space-y-8"
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
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Business Overview') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Key performance indicators for your business.') }}</p>
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6" wire:ignore>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">{{ __('Revenue Analytics') }}</h3>
        <div class="relative h-80 w-full">
            <canvas x-ref="revenueChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Products -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-800 dark:text-white">{{ __('Top Selling Products') }}</h3>
                <a href="#" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('View All') }}</a>
            </div>
            <div class="p-0">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">{{ __('Product') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Sales') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        @foreach($topProducts as $product)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors group">
                            <td class="px-6 py-3 flex items-center">
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 mr-3"></div>
                                <span class="font-medium text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 transition-colors">{{ $product['name'] }}</span>
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
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ __('Recent System Activities') }}</h3>
            <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">{{ __('View All Logs') }}</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-medium">{{ __('Activity') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('User') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Time') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentActivities as $activity)
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors group">
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
