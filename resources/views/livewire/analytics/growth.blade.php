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
        $thisYearSales = Sale::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')->whereYear('created_at', $now->year)->where('status', 'completed')->groupBy('month')->pluck('total', 'month')->toArray();

        $lastYearSales = Sale::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', $now->copy()->subYear()->year)
            ->where('status', 'completed')
            ->groupBy('month')
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
        YEAR(created_at) as year,
        MONTH(created_at) as month,
        SUM(total_amount) as revenue,
        COUNT(*) as orders
    ',
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // Fetch Customer Data Grouped by Year-Month
        $customerData = Customer::selectRaw(
            '
        YEAR(created_at) as year,
        MONTH(created_at) as month,
        COUNT(*) as count
    ',
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
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
    initCharts() {
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
                        grid: { borderDash: [2, 4] },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
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
                labels: this.charts.monthly_labels,
                datasets: [{
                        label: this.charts.this_year,
                        data: this.charts.this_year_monthly,
                        backgroundColor: '#4f46e5',
                        borderRadius: 4
                    },
                    {
                        label: this.charts.last_year,
                        data: this.charts.last_year_monthly,
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
                labels: this.charts.customer_labels,
                datasets: [{
                    label: '{{ __('New Customers') }}',
                    data: this.charts.customer_data,
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
}" x-init="initCharts();
Livewire.hook('morph.updated', () => { initCharts(); });" class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- YoY Growth -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ __('Annual Revenue Growth') }}</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ $kpi['annual_revenue_growth'] >= 0 ? '+' : '' }}{{ number_format($kpi['annual_revenue_growth'], 1, ',', '.') }}%
                    </h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ __('Compared to last year') }}</p>
        </div>

        <!-- New Customers -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ __('New Customer Rate') }}</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ $kpi['new_customer_rate'] >= 0 ? '+' : '' }}{{ number_format($kpi['new_customer_rate'], 1, ',', '.') }}%
                    </h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
            </div>
            <p
                class="text-xs {{ $kpi['new_customer_change'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2 flex items-center">
                <i class="fas fa-arrow-{{ $kpi['new_customer_change'] >= 0 ? 'up' : 'down' }} mr-1"></i>
                {{ abs($kpi['new_customer_change']) }} {{ __('from last month') }}
            </p>
        </div>

        <!-- Retention -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ __('Customer Retention') }}</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ number_format($kpi['customer_retention'], 1, ',', '.') }}%</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <i class="fas fa-hand-holding-heart text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ __('Returning customers') }}</p>
        </div>

        <!-- Market Share (Used for AOV) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ __('Avg. Order Value') }}</p>
                    <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($kpi['aov'], 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
            </div>
            <p
                class="text-xs {{ $kpi['aov_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2 flex items-center">
                <i class="fas fa-arrow-{{ $kpi['aov_growth'] >= 0 ? 'up' : 'down' }} mr-1"></i>
                {{ number_format(abs($kpi['aov_growth']), 1, ',', '.') }}% {{ __('YoY') }}
            </p>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('Revenue Growth Trend (5 Years)') }}</h3>
            <div class="relative h-72 w-full">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

        <!-- Monthly Comparison -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">{{ __('Monthly Performance') }}</h3>
                <div class="flex space-x-2">
                    <span class="flex items-center text-xs text-gray-500">
                        <span class="w-3 h-3 bg-indigo-500 rounded-full mr-1"></span> {{ $charts['this_year'] }}
                    </span>
                    <span class="flex items-center text-xs text-gray-500">
                        <span class="w-3 h-3 bg-gray-300 rounded-full mr-1"></span> {{ $charts['last_year'] }}
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
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('Customer Acquisition vs Churn') }}</h3>
            <div class="relative h-64 w-full">
                <canvas id="customerChart"></canvas>
            </div>
        </div>

        <!-- Top Growing Categories -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('Top Growing Categories') }} 60 Days</h3>
            <div class="space-y-4">
                @foreach ($topCategories as $category)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 rounded-full {{ $category['color'] }} flex items-center justify-center text-{{ $category['color'] }}-600 mr-3">
                                {{ $category['icon'] }}
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">{{ $category['name'] }}</h4>
                                <p class="text-xs text-gray-500">{{ number_format($category['sales'], 0, ',', '.') }}
                                    {{ __('Sales') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span
                                class="block {{ $category['growth'] >= 0 ? 'text-green-600' : 'text-red-500' }} font-bold">
                                {{ $category['growth'] >= 0 ? '+' : '' }}{{ number_format($category['growth'], 1, ',', '.') }}%
                            </span>
                            <span class="text-xs text-gray-400">{{ __('Growth') }}</span>
                        </div>
                    </div>
                @endforeach
                @if (empty($topCategories))
                    <div class="text-center p-4 text-gray-500">
                        {{ __('No category data available') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">{{ __('Growth History') }}</h3>
            <button
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View Full Report') }}</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">{{ __('Period') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Revenue') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Growth Rate') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('New Customers') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Avg. Order Value') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($growthHistory as $history)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $history['period'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">Rp.
                                {{ number_format($history['revenue'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="{{ $history['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                    <i
                                        class="fas fa-{{ $history['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                                    {{ number_format(abs($history['growth_rate']), 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ number_format($history['customers'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">Rp.
                                {{ number_format($history['avg_order'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $history['status'] === 'Excellent'
                                    ? 'bg-green-100 text-green-800'
                                    : ($history['status'] === 'Good'
                                        ? 'bg-blue-100 text-blue-800'
                                        : ($history['status'] === 'Average'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-red-100 text-red-800')) }}">
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
