<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
    #[Title('Profit & Loss')]
    class extends Component
{
    public $revenueItems = [];
    public $expenseItems = [];
    public $startDate;
    public $endDate;
    public $revenueGrowth = 0;
    public $expenseGrowth = 0;
    public $netProfitMargin = 0;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Previous Period Calculation
        $days = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($days);
        $prevEnd = $end->copy()->subDays($days);

        // --- Revenue (Sales by Category) ---
        $this->revenueItems = [];
        $categories = Category::lazy();

        foreach ($categories as $category) {
            $amount = SaleItem::whereHas('product', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                ->whereHas('sale', function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end])
                      ->where('status', 'completed');
                })
                ->sum('total_price');

            if ($amount > 0) {
                $this->revenueItems[] = [
                    'name' => $category->name,
                    'amount' => $amount
                ];
            }
        }

        // Discounts (Negative Revenue)
        $discounts = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('discount');

        if ($discounts > 0) {
            $this->revenueItems[] = [
                'name' => __('Discounts & Promotions'),
                'amount' => -$discounts
            ];
        }

        // Other Income (Transactions)
        $otherIncome = Transaction::where('type', 'income')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($otherIncome as $income) {
            $this->revenueItems[] = [
                'name' => $income->category ?? __('Other Income'),
                'amount' => $income->total
            ];
        }

        // Revenue Growth
        $currentRevenue = $this->getTotalRevenue();
        $prevItemRevenue = SaleItem::whereHas('sale', function ($q) use ($prevStart, $prevEnd) {
            $q->whereBetween('created_at', [$prevStart, $prevEnd])->where('status', 'completed');
        })->sum('total_price');
        $prevDiscounts = Sale::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', 'completed')->sum('discount');

        $prevOtherIncome = Transaction::where('type', 'income')
            ->whereBetween('date', [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')])
            ->where('status', 'completed')
            ->sum('amount');

        $prevRevenue = ($prevItemRevenue - $prevDiscounts) + $prevOtherIncome;

        $this->revenueGrowth = $prevRevenue != 0 ? (($currentRevenue - $prevRevenue) / abs($prevRevenue)) * 100 : ($currentRevenue > 0 ? 100 : 0);

        // --- Expenses ---
        $this->expenseItems = [];

        // Cost of Goods Sold (COGS)
        $cogs = SaleItem::join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->sum(DB::raw('sale_items.quantity * products.cost'));

        if ($cogs > 0) {
            $this->expenseItems[] = [
                'name' => __('Cost of Goods Sold'),
                'amount' => $cogs
            ];
        }

        // Operating Expenses (Transactions)
        $operatingExpenses = Transaction::where('type', 'expense')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($operatingExpenses as $expense) {
            $this->expenseItems[] = [
                'name' => $expense->category ?? __('Other Expense'),
                'amount' => $expense->total
            ];
        }

        // Expense Growth
        $currentExpenses = $this->getTotalExpenses();
        $prevCOGS = SaleItem::join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$prevStart, $prevEnd])
            ->where('sales.status', 'completed')
            ->sum(DB::raw('sale_items.quantity * products.cost'));

        $prevOperatingExpenses = Transaction::where('type', 'expense')
            ->whereBetween('date', [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')])
            ->where('status', 'completed')
            ->sum('amount');

        $prevExpenses = $prevCOGS + $prevOperatingExpenses;

        $this->expenseGrowth = $prevExpenses != 0 ? (($currentExpenses - $prevExpenses) / abs($prevExpenses)) * 100 : ($currentExpenses > 0 ? 100 : 0);

        // Net Profit Margin
        $netProfit = $currentRevenue - $currentExpenses;
        $this->netProfitMargin = $currentRevenue != 0 ? ($netProfit / $currentRevenue) * 100 : 0;
    }

    public function getTotalRevenue()
    {
        return array_sum(array_column($this->revenueItems, 'amount'));
    }

    public function getTotalExpenses()
    {
        return array_sum(array_column($this->expenseItems, 'amount'));
    }

    public function getNetProfit()
    {
        return $this->getTotalRevenue() - $this->getTotalExpenses();
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Start Date') }}</label>
                    <input type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                </div>
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('End Date') }}</label>
                    <input type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                </div>
                <div class="relative self-end">
                    <button class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        {{ __('Apply') }}
                    </button>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-print mr-2"></i> {{ __('Print') }}
                </button>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i> {{ __('Excel') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">{{ __('Total Revenue') }}</p>
            <h3 class="text-3xl font-bold text-green-600 mt-2">Rp. {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}</h3>
            <p class="text-xs {{ $revenueGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} mt-1 flex items-center">
                <i class="fas fa-{{ $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i> {{ number_format(abs($revenueGrowth), 1, ',', '.') }}% {{ __('vs last period') }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">{{ __('Total Expenses') }}</p>
            <h3 class="text-3xl font-bold text-red-600 mt-2">Rp. {{ number_format($this->getTotalExpenses(), 0, ',', '.') }}</h3>
            <p class="text-xs {{ $expenseGrowth <= 0 ? 'text-green-500' : 'text-red-500' }} mt-1 flex items-center">
                <i class="fas fa-{{ $expenseGrowth > 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i> {{ number_format(abs($expenseGrowth), 1, ',', '.') }}% {{ __('vs last period') }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm font-medium text-gray-500">{{ __('Net Profit') }}</p>
            <h3 class="text-3xl font-bold text-indigo-600 mt-2">Rp. {{ number_format($this->getNetProfit(), 0, ',', '.') }}</h3>
            <p class="text-xs text-green-500 mt-1 flex items-center">
                <i class="fas fa-chart-line mr-1"></i> {{ number_format($netProfitMargin, 1, ',', '.') }}% {{ __('Net Margin') }}
            </p>
        </div>
    </div>

    <!-- Detailed P&L Statement -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">{{ __('Income Statement') }}</h2>
            <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
        </div>

        <div class="p-6">
            <!-- Revenue Section -->
            <div class="mb-8">
                <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Revenue') }}</h3>
                <div class="space-y-3">
                    @foreach($revenueItems as $item)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-700">{{ $item['name'] }}</span>
                        <span class="font-medium text-gray-900">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between items-center py-3 bg-green-50 px-4 rounded-lg mt-4">
                        <span class="font-bold text-green-800">{{ __('Total Revenue') }}</span>
                        <span class="font-bold text-green-800">Rp. {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Expenses Section -->
            <div class="mb-8">
                <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Expenses') }}</h3>
                <div class="space-y-3">
                    @foreach($expenseItems as $item)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-700">{{ $item['name'] }}</span>
                        <span class="font-medium text-gray-900">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between items-center py-3 bg-red-50 px-4 rounded-lg mt-4">
                        <span class="font-bold text-red-800">{{ __('Total Expenses') }}</span>
                        <span class="font-bold text-red-800">Rp. {{ number_format($this->getTotalExpenses(), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Net Profit -->
            <div class="flex justify-between items-center py-4 bg-gray-900 text-white px-6 rounded-xl shadow-lg">
                <span class="text-lg font-bold">{{ __('Net Profit') }}</span>
                <span class="text-2xl font-bold">Rp. {{ number_format($this->getNetProfit(), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
