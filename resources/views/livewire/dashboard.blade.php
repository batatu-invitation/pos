<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] #[Title('Dashboard - Modern POS')] class extends Component {
    public $welcomeMessage = '';
    public $balance = 0;
    public $userBalance = 0;
    public $isManager = false;

    // Stats
    public $totalSalesAmount = 0;
    public $salesGrowth = 0;
    public $totalOrdersCount = 0;
    public $ordersGrowth = 0;
    public $totalProductsCount = 0;
    public $productsGrowth = 0;
    public $totalCustomersCount = 0;
    public $customersGrowth = 0;

    // Chart Data
    public $chartLabels = [];
    public $chartData = [];

    // Lists
    public $topProducts = [];
    public $recentTransactions = [];

    public function mount()
    {
        if (!session()->has('locale')) {
            session(['locale' => 'id']);
        }

        // Welcome Message Logic
        $user = auth()->user();
        if ($user) {
            $this->isManager = $user->hasRole('Manager');
            if ($this->isManager) {
                $this->userBalance = $user->balance;
            }

            $createdAt = Carbon::parse($user->created_at);
            if ($createdAt->diffInDays(now()) > 1) {
                $this->welcomeMessage = __('Welcome back') . ', ' . $user->name;
            } else {
                $this->welcomeMessage = __('Welcome') . ', ' . $user->name;
            }
        }

        // Balance Logic (Cash on Hand)
        $cashInSales = Sale::where('status', 'completed')->sum('total_amount');
        $cashInTrans = Transaction::where('type', 'income')->where('status', 'completed')->sum('amount');
        $cashOutTrans = Transaction::where('type', 'expense')->where('status', 'completed')->sum('amount');
        $this->balance = ($cashInSales + $cashInTrans) - $cashOutTrans;

        // --- Statistics (This Month vs Last Month) ---
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // 1. Sales (This Month)
        $this->totalSalesAmount = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');
        
        $lastMonthSales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_amount');

        $this->salesGrowth = $lastMonthSales > 0 ? (($this->totalSalesAmount - $lastMonthSales) / $lastMonthSales) * 100 : ($this->totalSalesAmount > 0 ? 100 : 0);

        // 2. Orders (This Month)
        $this->totalOrdersCount = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        
        $lastMonthOrders = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $this->ordersGrowth = $lastMonthOrders > 0 ? (($this->totalOrdersCount - $lastMonthOrders) / $lastMonthOrders) * 100 : ($this->totalOrdersCount > 0 ? 100 : 0);

        // 3. Products (Total Active)
        $this->totalProductsCount = Product::count();
        $newProductsThisMonth = Product::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $totalLastMonthProducts = $this->totalProductsCount - $newProductsThisMonth;
        $this->productsGrowth = $totalLastMonthProducts > 0 ? (($this->totalProductsCount - $totalLastMonthProducts) / $totalLastMonthProducts) * 100 : 0;

        // 4. Customers (Total)
        $this->totalCustomersCount = Customer::count();
        $newCustomersThisMonth = Customer::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $totalLastMonthCustomers = $this->totalCustomersCount - $newCustomersThisMonth;
        $this->customersGrowth = $totalLastMonthCustomers > 0 ? (($this->totalCustomersCount - $totalLastMonthCustomers) / $totalLastMonthCustomers) * 100 : 0;

        // --- Chart Data (Last 7 Days Sales) ---
        $this->chartLabels = [];
        $this->chartData = [];
        
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        
        $salesData = Sale::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $this->chartLabels[] = $date->format('D');
            $this->chartData[] = $salesData[$dateString] ?? 0;
        }

        // --- Top 3 Products ---
        $topProductsData = SaleItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->take(3)
            ->get();

        $this->topProducts = $topProductsData->map(function($item) {
            return [
                'name' => $item->product_name,
                'sales' => (int)$item->total_quantity,
                'revenue' => (float)$item->total_revenue,
                'icon' => '📦'
            ];
        })->toArray();

        // --- Recent Transactions ---
        $recentSales = Sale::with('customer')
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $this->recentTransactions = $recentSales->map(function($sale) {
            $statusColor = match($sale->status) {
                'completed' => 'green',
                'pending' => 'yellow',
                'refunded' => 'red',
                default => 'gray'
            };
            
            return [
                'id' => $sale->invoice_number,
                'customer' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                'date' => $sale->created_at->diffForHumans(),
                'items' => $sale->items_count . ' Items',
                'total' => $sale->total_amount,
                'status' => ucfirst($sale->status),
                'status_color' => $statusColor
            ];
        })->toArray();
    // End of mount
    }
};
?>

<div>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    <!-- Welcome & Balance Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Welcome Card -->
        <div class="md:col-span-2 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl shadow-lg p-8 relative overflow-hidden text-white group hover:shadow-indigo-500/30 transition-all duration-300">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl group-hover:blur-2xl transition-all duration-500"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/20 rounded-full -ml-10 -mb-10 blur-2xl"></div>
            
            <div class="relative z-10 h-full flex flex-col justify-center">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-2xl">
                        <i class="fas fa-hand-sparkles text-2xl text-yellow-300"></i>
                    </div>
                    <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-medium border border-white/10">
                        {{ now()->format('l, d F Y') }}
                    </span>
                </div>
                
                <h1 class="text-3xl md:text-4xl font-bold mb-2 tracking-tight">
                    {{ $welcomeMessage }} <span class="animate-pulse">👋</span>
                </h1>
                <p class="text-indigo-100 max-w-lg text-lg">
                    {{ __('Here is what is happening with your store today.') }}
                </p>
            </div>
        </div>

        <!-- Balance Card -->
        @if($isManager)
        <div class="md:col-span-1 bg-white rounded-3xl shadow-sm p-8 border border-gray-100 hover:shadow-lg transition-all duration-300 relative overflow-hidden dark:bg-gray-800 dark:border-gray-700 group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110 dark:bg-indigo-900/20"></div>
            
            <div class="relative z-10 h-full flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-400">
                            {{ __('My Balance') }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Current Balance') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1">
                        Rp. {{ number_format($userBalance, 0, ",", ".") }}
                    </h3>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Personal Balance') }}
                    </span>
                </div>
            </div>
        </div>
        @else
        <div class="md:col-span-1 bg-white rounded-3xl shadow-sm p-8 border border-gray-100 hover:shadow-lg transition-all duration-300 relative overflow-hidden dark:bg-gray-800 dark:border-gray-700 group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110 dark:bg-emerald-900/20"></div>
            
            <div class="relative z-10 h-full flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-50 rounded-2xl text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-400">
                            {{ __('Cash on Hand') }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Balance') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1">
                        Rp. {{ number_format($balance, 0, ',', '.') }}
                    </h3>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <a href="{{ route('analytics.balance-sheet') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 dark:text-emerald-400 dark:hover:text-emerald-300">
                        {{ __('View Report') }} <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-indigo-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Sales') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Rp. {{ number_format($totalSalesAmount, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-2xl text-indigo-600 shadow-lg shadow-indigo-200/30 dark:from-indigo-900/30 dark:to-indigo-800/30 dark:text-indigo-400">
                    <i class="fas fa-dollar-sign text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="{{ $salesGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center font-medium">
                    <i class="fas fa-arrow-{{ $salesGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($salesGrowth), 1) }}%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Orders') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalOrdersCount, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl text-blue-600 shadow-lg shadow-blue-200/30 dark:from-blue-900/30 dark:to-blue-800/30 dark:text-blue-400">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="{{ $ordersGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center font-medium">
                    <i class="fas fa-arrow-{{ $ordersGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-orange-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Products') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalProductsCount, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl text-orange-600 shadow-lg shadow-orange-200/30 dark:from-orange-900/30 dark:to-orange-800/30 dark:text-orange-400">
                    <i class="fas fa-box text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="{{ $productsGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center font-medium">
                    <i class="fas fa-arrow-{{ $productsGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($productsGrowth), 1) }}%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition-all duration-300 group relative overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-teal-500/10 to-transparent rounded-bl-3xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Customers') }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalCustomersCount, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-gradient-to-br from-teal-50 to-teal-100 rounded-2xl text-teal-600 shadow-lg shadow-teal-200/30 dark:from-teal-900/30 dark:to-teal-800/30 dark:text-teal-400">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <div class="flex items-center text-sm">
                <span class="{{ $customersGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center font-medium">
                    <i class="fas fa-arrow-{{ $customersGrowth >= 0 ? 'up' : 'down' }} mr-1"></i> {{ number_format(abs($customersGrowth), 1) }}%
                </span>
                <span class="text-gray-400 ml-2 dark:text-gray-500">{{ __('vs last month') }}</span>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Chart -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 lg:col-span-2 hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Sales Overview') }}</h3>
                <select
                    class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    <option>{{ __('Last 7 Days') }}</option>
                    <option>{{ __('Last 30 Days') }}</option>
                    <option>{{ __('This Year') }}</option>
                </select>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 mb-4 dark:text-gray-100">{{ __('Top Selling Products') }}</h3>
            <div class="space-y-4">
                @foreach ($topProducts as $product)
                    <div class="flex items-center justify-between group p-2 rounded-xl hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/50">
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center text-xl mr-4 shadow-sm dark:from-gray-700 dark:to-gray-600">
                                {{ $product['icon'] }}</div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $product['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product['sales'] }} {{ __('sales') }}</p>
                            </div>
                        </div>
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-gray-100">Rp. {{ number_format($product['revenue'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <button
                class="w-full mt-6 py-3 bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl text-sm font-medium text-indigo-700 hover:from-indigo-100 hover:to-blue-100 transition-all duration-300 dark:from-indigo-900/30 dark:to-blue-900/30 dark:border-indigo-700 dark:text-indigo-300">{{ __('View All Products') }}</button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Recent Transactions') }}</h3>
            <a href="#"
                class="text-indigo-600 text-sm font-medium hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('View All') }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">{{ __('Order ID') }}</th>
                        <th class="px-6 py-4">{{ __('Customer') }}</th>
                        <th class="px-6 py-4">{{ __('Date') }}</th>
                        <th class="px-6 py-4">{{ __('Items') }}</th>
                        <th class="px-6 py-4">{{ __('Total') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100">{{ $transaction['id'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['date'] }}</td>
                            <td class="px-6 py-4">{{ $transaction['items'] }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-100">
                                Rp. {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction['status_color'] }}-100 text-{{ $transaction['status_color'] }}-800 dark:bg-{{ $transaction['status_color'] }}-900/30 dark:text-{{ $transaction['status_color'] }}-300">{{ $transaction['status'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    @script
        <script>
            const initSalesChart = () => {
                const canvas = document.getElementById('salesChart');
                if (!canvas) return;

                // Destroy existing chart if it exists to prevent "Canvas is already in use" error
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }

                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Sales (Rp)',
                            data: [1200000, 1900000, 3000000, 2500000, 2200000, 3200000, 4000000],
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

            // Initialize on load
            initSalesChart();

            // Re-initialize on Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                // Only try to init if the canvas actually exists on the current page
                if (document.getElementById('salesChart')) {
                    initSalesChart();
                }
            });
        </script>
    @endscript
</div>
