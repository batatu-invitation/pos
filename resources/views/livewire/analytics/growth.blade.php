<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Company Growth - Modern POS')] class extends Component {
    public $growthHistory = [];
    public $kpi = [];
    public $charts = [];
    public $topCategories = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $now = Carbon::now();
        $thisYear = $now->year;
        $lastYear = $thisYear - 1;

        // --- 1. KPI & YEARLY TREND (Single Query) ---
        $yearlyStats = Sale::selectRaw('
                YEAR(created_at) as year, 
                SUM(total_amount) as revenue, 
                COUNT(*) as orders
            ')
            ->where('status', 'completed')
            ->where('created_at', '>=', $now->copy()->subYears(4)->startOfYear())
            ->groupBy('year')
            ->get()
            ->keyBy('year');

        $thisYearRev = $yearlyStats->get($thisYear)->revenue ?? 0;
        $lastYearRev = $yearlyStats->get($lastYear)->revenue ?? 0;
        $thisYearOrders = $yearlyStats->get($thisYear)->orders ?? 0;
        $lastYearOrders = $yearlyStats->get($lastYear)->orders ?? 0;

        // --- 2. CUSTOMER METRICS (Optimized) ---
        $customerStats = Customer::selectRaw("
                SUM(CASE WHEN month(created_at) = {$now->month} AND year(created_at) = {$thisYear} THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN month(created_at) = {$now->copy()->subMonth()->month} AND year(created_at) = {$now->copy()->subMonth()->year} THEN 1 ELSE 0 END) as last_month,
                COUNT(*) as total_all_time
            ")
            ->first();

        $repeatCustomers = Sale::where('status', 'completed')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('customer_id')
            ->count();

        // --- 3. TOP GROWING CATEGORIES (Anti N+1) ---
        $start = now()->subDays(30);
        $prevStart = now()->subDays(60);

        $categoryStats = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                categories.name, categories.icon, categories.color,
                SUM(CASE WHEN sales.created_at >= ? THEN sale_items.quantity ELSE 0 END) as current_sales,
                SUM(CASE WHEN sales.created_at >= ? AND sales.created_at < ? THEN sale_items.quantity ELSE 0 END) as prev_sales
            ', [$start, $prevStart, $start])
            ->where('sales.status', 'completed')
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sales' => (int) $item->current_sales,
                    'growth' => $this->calculateGrowth($item->current_sales, $item->prev_sales),
                    'icon' => $item->icon ?? 'box',
                    'color' => $item->color ?? 'blue',
                ];
            })
            ->sortByDesc('growth')
            ->take(6)
            ->values()
            ->toArray();

        // --- 4. MONTHLY COMPARISON CHART ---
        $monthlySales = Sale::selectRaw('
                YEAR(created_at) as year, 
                MONTH(created_at) as month, 
                SUM(total_amount) as total
            ')
            ->whereIn(DB::raw('YEAR(created_at)'), [$thisYear, $lastYear])
            ->where('status', 'completed')
            ->groupBy('year', 'month')
            ->get();

        $thisYearMonthly = array_fill(1, 12, 0);
        $lastYearMonthly = array_fill(1, 12, 0);

        foreach ($monthlySales as $sale) {
            if ($sale->year == $thisYear) $thisYearMonthly[$sale->month] = (float)$sale->total;
            else $lastYearMonthly[$sale->month] = (float)$sale->total;
        }

        // --- 5. GROWTH HISTORY (Last 12 Months) ---
        $historyData = Sale::selectRaw('
                YEAR(created_at) as year, 
                MONTH(created_at) as month, 
                SUM(total_amount) as revenue, 
                COUNT(*) as orders
            ')
            ->where('status', 'completed')
            ->where('created_at', '>=', $now->copy()->subMonths(13)->startOfMonth())
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($i) => $i->year . '-' . $i->month);

        $histCustomers = Customer::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', $now->copy()->subMonths(13)->startOfMonth())
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($i) => $i->year . '-' . $i->month);

        $history = [];
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonthsNoOverflow($i);
            $key = $date->year . '-' . $date->month;
            $prevKey = $date->copy()->subMonthNoOverflow()->year . '-' . $date->copy()->subMonthNoOverflow()->month;

            $curr = $historyData->get($key);
            $prev = $historyData->get($prevKey);
            $rev = $curr->revenue ?? 0;
            $growth = $this->calculateGrowth($rev, $prev->revenue ?? 0);

            $history[] = [
                'period' => $date->format('M Y'),
                'revenue' => $rev,
                'growth_rate' => $growth,
                'customers' => $histCustomers->get($key)->count ?? 0,
                'avg_order' => ($curr->orders ?? 0) > 0 ? $rev / $curr->orders : 0,
                'status' => $growth >= 10 ? 'Excellent' : ($growth > 0 ? 'Good' : ($growth < -10 ? 'Poor' : 'Average')),
            ];
        }

        // --- ASSIGN TO PUBLIC PROPERTIES ---
        $this->topCategories = $categoryStats;
        $this->growthHistory = $history;
        $this->kpi = [
            'annual_revenue_growth' => $this->calculateGrowth($thisYearRev, $lastYearRev),
            'new_customer_rate' => $this->calculateGrowth($customerStats->this_month, $customerStats->last_month),
            'new_customer_change' => $customerStats->this_month - $customerStats->last_month,
            'customer_retention' => $customerStats->total_all_time > 0 ? ($repeatCustomers / $customerStats->total_all_time) * 100 : 0,
            'aov' => $thisYearOrders > 0 ? $thisYearRev / $thisYearOrders : 0,
            'aov_growth' => $this->calculateGrowth(
                $thisYearOrders > 0 ? $thisYearRev / $thisYearOrders : 0,
                $lastYearOrders > 0 ? $lastYearRev / $lastYearOrders : 0
            ),
        ];

        $this->charts = [
            'revenue_years' => array_map('strval', range($thisYear - 4, $thisYear)),
            'revenue_trend' => array_values(array_map(fn($y) => $yearlyStats->get($y)->revenue ?? 0, range($thisYear - 4, $thisYear))),
            'monthly_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'this_year_monthly' => array_values($thisYearMonthly),
            'last_year_monthly' => array_values($lastYearMonthly),
            'this_year' => $thisYear,
            'last_year' => $lastYear,
        ];
    }

    private function calculateGrowth($current, $previous) {
        if ($previous <= 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }
}; ?>

<div x-data="{
    charts: {{ json_encode($charts) }},
    isDark: document.documentElement.classList.contains('dark'),
    initCharts() {
        const textColor = this.isDark ? '#9ca3af' : '#4b5563';
        const gridColor = this.isDark ? '#374151' : '#e5e7eb';

        // Revenue Growth Chart (Line)
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        if (window.growthChartInstance) {
            window.growthChartInstance.destroy();
        }
        window.growthChartInstance = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: this.charts.revenue_years,
                datasets: [{
                    label: '{{ __('Annual Revenue (Rp.)') }}',
                    data: this.charts.revenue_trend,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: this.isDark ? '#1f2937' : '#ffffff',
                    pointBorderColor: '#6366f1',
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
                        grid: { 
                            color: gridColor,
                            borderDash: [2, 4] 
                        },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
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
                labels: this.charts.monthly_labels,
                datasets: [{
                        label: this.charts.this_year,
                        data: this.charts.this_year_monthly,
                        backgroundColor: '#6366f1',
                        borderRadius: 4
                    },
                    {
                        label: this.charts.last_year,
                        data: this.charts.last_year_monthly,
                        backgroundColor: this.isDark ? '#374151' : '#e5e7eb',
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
                        grid: { 
                            color: gridColor,
                            borderDash: [2, 4] 
                        },
                        ticks: { color: textColor }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
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
                labels: this.charts.customer_labels,
                datasets: [{
                    label: '{{ __('New Customers') }}',
                    data: this.charts.customer_data,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: this.isDark ? '#1f2937' : '#ffffff',
                    pointBorderColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { 
                            color: gridColor,
                            borderDash: [2, 4] 
                        },
                        ticks: { color: textColor }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    },
    init() {
        this.initCharts();
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    this.isDark = document.documentElement.classList.contains('dark');
                    this.initCharts();
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
}" x-init="init(); Livewire.hook('morph.updated', () => { initCharts(); });" 
class="mx-auto p-6 space-y-8">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Company Growth') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Track key performance indicators and growth metrics.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                {{ __('Last updated') }}: {{ now()->format('M d, Y') }}
            </span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- YoY Growth -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Annual Revenue Growth') }}</span>
                        <i class="fas fa-chart-line text-emerald-100 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold mb-1">
                        {{ $kpi['annual_revenue_growth'] >= 0 ? '+' : '' }}{{ number_format($kpi['annual_revenue_growth'], 1, ',', '.') }}%
                    </h3>
                    <div class="text-emerald-100 text-sm opacity-90">
                        {{ __('Compared to last year') }}
                    </div>
                </div>
            </div>

            <!-- New Customers -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('New Customer Rate') }}</span>
                        <i class="fas fa-user-plus text-blue-100 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold mb-1">
                        {{ $kpi['new_customer_rate'] >= 0 ? '+' : '' }}{{ number_format($kpi['new_customer_rate'], 1, ',', '.') }}%
                    </h3>
                    <div class="flex items-center text-sm text-blue-100 opacity-90">
                        <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                            <i class="fas fa-arrow-{{ $kpi['new_customer_change'] >= 0 ? 'up' : 'down' }} mr-1"></i> {{ abs($kpi['new_customer_change']) }}
                        </span>
                        <span>{{ __('from last month') }}</span>
                    </div>
                </div>
            </div>

            <!-- Retention -->
            <div class="bg-gradient-to-br from-violet-500 to-fuchsia-600 rounded-3xl p-6 text-white shadow-lg shadow-violet-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Customer Retention') }}</span>
                        <i class="fas fa-hand-holding-heart text-violet-100 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold mb-1">
                        {{ number_format($kpi['customer_retention'], 1, ',', '.') }}%
                    </h3>
                    <div class="text-violet-100 text-sm opacity-90">
                        {{ __('Returning customers') }}
                    </div>
                </div>
            </div>

            <!-- Market Share (Used for AOV) -->
            <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-3xl p-6 text-white shadow-lg shadow-orange-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Avg. Order Value') }}</span>
                        <i class="fas fa-shopping-bag text-orange-100 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($kpi['aov'], 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center text-sm text-orange-100 opacity-90">
                        <span class="font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-lg mr-2">
                            <i class="fas fa-arrow-{{ $kpi['aov_growth'] >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($kpi['aov_growth']), 1, ',', '.') }}%
                        </span>
                        <span>{{ __('YoY') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Revenue Growth Trend (5 Years)') }}</h3>
                <div class="relative h-72 w-full">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Monthly Performance') }}</h3>
                    <div class="flex space-x-2">
                        <span class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-3 h-3 bg-indigo-500 rounded-full mr-1"></span> {{ $charts['this_year'] }}
                        </span>
                        <span class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full mr-1"></span> {{ $charts['last_year'] }}
                        </span>
                    </div>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="monthlyCompChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 & Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Customer Acquisition -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Customer Acquisition vs Churn') }}</h3>
                <div class="relative h-80 w-full">
                    <canvas id="customerChart"></canvas>
                </div>
            </div>

            <!-- Top Growing Categories -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Fastest Growing Categories') }}</h3>
                <div class="space-y-4">
                    @foreach($topCategories as $category)
                    <div class="flex items-center p-3 bg-gray-50/50 dark:bg-gray-700/30 rounded-2xl hover:bg-gray-50/80 dark:hover:bg-gray-700 transition-colors">
                        <div class="p-3 rounded-xl bg-{{ $category['color'] ?? 'blue' }}-100 dark:bg-{{ $category['color'] ?? 'blue' }}-900/30 text-{{ $category['color'] ?? 'blue' }}-600 dark:text-{{ $category['color'] ?? 'blue' }}-400 mr-4">
                            <i class="fas fa-{{ $category['icon'] ?? 'box' }}"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $category['name'] }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $category['sales'] }} {{ __('units sold') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="block font-bold {{ $category['growth'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $category['growth'] >= 0 ? '+' : '' }}{{ number_format($category['growth'], 1) }}%
                            </span>
                            <span class="text-xs text-gray-400">{{ __('growth') }}</span>
                        </div>
                    </div>
                    @endforeach
                    @if (empty($topCategories))
                    <div class="text-center p-4 text-gray-500 dark:text-gray-400">
                        {{ __('No category data available') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Growth History Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Growth History') }}</h3>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/30 text-gray-500 dark:text-gray-400 uppercase tracking-wider text-xs font-semibold border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4">{{ __('Period') }}</th>
                            <th class="px-6 py-4">{{ __('Revenue') }}</th>
                            <th class="px-6 py-4">{{ __('Growth Rate') }}</th>
                            <th class="px-6 py-4">{{ __('New Customers') }}</th>
                            <th class="px-6 py-4">{{ __('Avg Order') }}</th>
                            <th class="px-6 py-4">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($growthHistory as $history)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors group">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $history['period'] }}</td>
                            <td class="px-6 py-4">Rp. {{ number_format($history['revenue'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="{{ $history['growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                    <i class="fas fa-{{ $history['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                                    {{ number_format(abs($history['growth_rate']), 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ number_format($history['customers'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">Rp. {{ number_format($history['avg_order'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    {{ $history['status'] == 'Excellent' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                    {{ $history['status'] == 'Good' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                    {{ $history['status'] == 'Average' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                    {{ $history['status'] == 'Poor' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                ">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
