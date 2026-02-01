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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfitLossExport;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // --- Cashier Sales ---
        $this->revenueItems = [];

        $salesBySeller = Sale::query()
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->select(
                'users.first_name',
                'users.last_name',
                DB::raw('SUM(sales.subtotal) as total_sales')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->get();

        foreach ($salesBySeller as $sellerSale) {
            if ($sellerSale->total_sales > 0) {
                $this->revenueItems[] = [
                    'name' => $sellerSale->first_name . ' ' . $sellerSale->last_name,
                    'amount' => $sellerSale->total_sales
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
        $prevItemRevenue = Sale::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', 'completed')
            ->sum('subtotal');

        $prevDiscounts = Sale::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', 'completed')
            ->sum('discount');

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

    public function exportExcel()
    {
        $this->loadData();
        return Excel::download(new ProfitLossExport(
            $this->revenueItems,
            $this->expenseItems,
            $this->getTotalRevenue(),
            $this->getTotalExpenses(),
            $this->getNetProfit(),
            $this->startDate,
            $this->endDate
        ), 'profit-loss.xlsx');
    }

    public function exportPdf()
    {
        $this->loadData();
        $pdf = Pdf::loadView('pdf.profit-loss', [
            'revenueItems' => $this->revenueItems,
            'expenseItems' => $this->expenseItems,
            'totalRevenue' => $this->getTotalRevenue(),
            'totalExpenses' => $this->getTotalExpenses(),
            'netProfit' => $this->getNetProfit(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'profit-loss.pdf');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Profit & Loss Statement') }}</h2>
        <div class="flex gap-2">
            <input wire:model.live="startDate" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <span class="self-center text-gray-500">-</span>
            <input wire:model.live="endDate" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">

            <div x-data="{ open: false }" class="relative ml-2">
                <button @click="open = !open" @click.away="open = false" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
                    <i class="fas fa-file-export mr-2"></i> Export
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
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
                <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Cashier Sales') }}</h3>
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
