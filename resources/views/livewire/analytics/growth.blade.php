<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

        // --- 1. KPI Cards ---

        // Annual Revenue Growth
        $thisYearRevenue = Sale::whereYear('created_at', $now->year)->where('status', 'completed')->sum('total_amount');
        $lastYearRevenue = Sale::whereYear('created_at', $now->copy()->subYear()->year)
            ->where('status', 'completed')
            ->sum('total_amount');
        $annualGrowth = $this->calculateGrowth($thisYearRevenue, $lastYearRevenue);

        // New Customer Rate (Month over Month)
        $thisMonthCustomers = Customer::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $lastMonthCustomers = Customer::whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->count();
        $customerGrowth = $this->calculateGrowth($thisMonthCustomers, $lastMonthCustomers);

        // Customer Retention (Repeat Customers / Total Customers)
        $totalCustomers = Customer::count();
        $repeatCustomers = Sale::select('customer_id')->whereNotNull('customer_id')->where('status', 'completed')->groupBy('customer_id')->havingRaw('COUNT(*) > 1')->get()->count();
        $retentionRate = $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;

        // Avg Order Value (AOV) YoY
        $thisYearOrders = Sale::whereYear('created_at', $now->year)->where('status', 'completed')->count();
        $thisYearAOV = $thisYearOrders > 0 ? $thisYearRevenue / $thisYearOrders : 0;

        $lastYearOrders = Sale::whereYear('created_at', $now->copy()->subYear()->year)
            ->where('status', 'completed')
            ->count();
        $lastYearAOV = $lastYearOrders > 0 ? $lastYearRevenue / $lastYearOrders : 0;
        $aovGrowth = $this->calculateGrowth($thisYearAOV, $lastYearAOV);

        $this->kpi = [
            'annual_revenue_growth' => $annualGrowth,
            'new_customer_rate' => $customerGrowth,
            'new_customer_change' => $thisMonthCustomers - $lastMonthCustomers, // Absolute change for subtext? Or just use growth rate. View uses % change.
            'customer_retention' => $retentionRate,
            'aov' => $thisYearAOV,
            'aov_growth' => $aovGrowth,
        ];

        // --- 2. Charts ---

        // Revenue Trend (5 Years)
        $years = range($now->year - 4, $now->year);
        $revenueTrendData = [];
        foreach ($years as $year) {
            $revenueTrendData[] = Sale::whereYear('created_at', $year)->where('status', 'completed')->sum('total_amount');
        }

        // Monthly Comparison (This Year vs Last Year)
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $thisYearMonthly = [];
        $lastYearMonthly = [];

        // Efficient query for monthly data
        $thisYearSales = Sale::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', $now->year)
            ->where('status', 'completed')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->pluck('total', 'month')
            ->toArray();

        $lastYearSales = Sale::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', $now->copy()->subYear()->year)
            ->where('status', 'completed')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->pluck('total', 'month')
            ->toArray();

        for ($i = 1; $i <= 12; $i++) {
            $thisYearMonthly[] = $thisYearSales[$i] ?? 0;
            $lastYearMonthly[] = $lastYearSales[$i] ?? 0;
        }

        // Customer Acquisition (Last 6 Months)
        $customerTrendLabels = [];
        $customerTrendData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $customerTrendLabels[] = $date->format('M');
            $customerTrendData[] = Customer::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
        }

        $this->charts = [
            'revenue_years' => array_map('strval', $years),
            'revenue_trend' => $revenueTrendData,
            'monthly_labels' => $months,
            'this_year_monthly' => $thisYearMonthly,
            'last_year_monthly' => $lastYearMonthly,
            'customer_labels' => $customerTrendLabels,
            'customer_data' => $customerTrendData,
            'this_year' => $now->year,
            'last_year' => $now->year - 1,
        ];

        // --- 3. Top Growing Categories ---
        // Comparing last 30 days sales count vs previous 30 days
        $start = now()->subDays(30);
        $prevStart = now()->subDays(60);

        // Ambil semua SaleItems dalam 60 hari terakhir sekaligus
        $allSales = SaleItem::with('product')
            ->whereHas('sale', function ($q) use ($prevStart) {
                $q->where('created_at', '>=', $prevStart)->where('status', 'completed');
            })
            ->get();

        $categories = Category::all();
        $categoryGrowth = [];

        foreach ($categories as $category) {
            // Filter data dari koleksi di memori (tanpa query database lagi)
            $currentSales = $allSales
                ->filter(function ($item) use ($category, $start) {
                    return $item->product->category_id == $category->id && $item->created_at >= $start;
                })
                ->sum('quantity');

            $prevSales = $allSales
                ->filter(function ($item) use ($category, $start, $prevStart) {
                    return $item->product->category_id == $category->id && $item->created_at >= $prevStart && $item->created_at < $start;
                })
                ->sum('quantity');

            $growth = $this->calculateGrowth($currentSales, $prevSales);

            $categoryGrowth[] = [
                'name' => $category->name,
                'sales' => (int) $currentSales,
                'growth' => $growth,
                'icon' => $category->icon ?? 'box',
                'color' => $category->color ?? 'blue',
            ];
        }

        // Urutkan dan ambil top 6
        usort($categoryGrowth, fn($a, $b) => $b['growth'] <=> $a['growth']);
        $this->topCategories = array_slice($categoryGrowth, 0, 6);
        // dd($this->topCategories);

        // --- 4. Growth History (Last 12 Months) ---
        $history = [];
        $now = Carbon::now();

        // Pre-fetch data for the last 13 months to allow growth calculation for the 12th month back
        // Range: From start of 12 months ago to end of current month
        // Gunakan startOfMonth agar perhitungan mundur bulan lebih stabil
        $now = now()->startOfMonth();

        $startDate = $now->copy()->subMonths(12)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        // Fetch Sales Data Grouped by Year-Month
        $salesData = Sale::selectRaw(
            '
        EXTRACT(YEAR FROM created_at) as year,
        EXTRACT(MONTH FROM created_at) as month,
        SUM(total_amount) as revenue,
        COUNT(*) as orders
    ',
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // Fetch Customer Data Grouped by Year-Month
        $customerData = Customer::selectRaw(
            '
        EXTRACT(YEAR FROM created_at) as year,
        EXTRACT(MONTH FROM created_at) as month,
        COUNT(*) as count
    ',
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        $history = [];

        for ($i = 0; $i < 12; $i++) {
            // subMonthsNoOverflow mencegah lonjakan tanggal jika hari ini tanggal 31
            $date = $now->copy()->subMonthsNoOverflow($i);
            $yearMonth = $date->format('Y-m');

            $prevDate = $date->copy()->subMonthNoOverflow();
            $prevYearMonth = $prevDate->format('Y-m');

            // Ambil data sales
            $currentSale = $salesData->get($yearMonth);
            $revenue = $currentSale ? $currentSale->revenue : 0;
            $orders = $currentSale ? $currentSale->orders : 0;

            // Ambil data sales bulan sebelumnya untuk growth rate
            $prevSale = $salesData->get($prevYearMonth);
            $prevRevenue = $prevSale ? $prevSale->revenue : 0;

            // Ambil data customer
            $currentCustomer = $customerData->get($yearMonth);
            $customers = $currentCustomer ? $currentCustomer->count : 0;

            // Kalkulasi
            $growthRate = $this->calculateGrowth($revenue, $prevRevenue);
            $avgOrder = $orders > 0 ? $revenue / $orders : 0;

            // Determine Status
            $status = 'Average';
            if ($growthRate >= 10) {
                $status = 'Excellent';
            } elseif ($growthRate > 0) {
                $status = 'Good';
            } elseif ($growthRate < -10) {
                $status = 'Poor';
            }

            $history[] = [
                'period' => $date->format('M Y'),
                'revenue' => $revenue,
                'growth_rate' => $growthRate,
                'customers' => $customers,
                'avg_order' => $avgOrder,
                'status' => $status,
            ];
        }

        // return $history;
        // dd($history);
        $this->growthHistory = $history;
        // dd($this->growthHistory);
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
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
class="min-h-screen bg-gray-50/50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 lg:p-8 transition-colors duration-300">

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700/50">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                    {{ __('Company Growth') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Track key performance indicators and growth metrics') }}
                </p>
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
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Revenue Growth Trend (5 Years)') }}</h3>
                <div class="relative h-72 w-full">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
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
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Customer Acquisition vs Churn') }}</h3>
                <div class="relative h-80 w-full">
                    <canvas id="customerChart"></canvas>
                </div>
            </div>

            <!-- Top Growing Categories -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Fastest Growing Categories') }}</h3>
                <div class="space-y-4">
                    @foreach($topCategories as $category)
                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
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
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Growth History') }}</h3>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 uppercase tracking-wider text-xs font-semibold">
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
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
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
</div>
