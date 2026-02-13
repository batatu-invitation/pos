<?php

use App\Models\Sale;
use App\Models\SaleItem;
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
        // Default ke bulan berjalan
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadData();
    }

    public function updatedStartDate() { $this->loadData(); }
    public function updatedEndDate() { $this->loadData(); }

    public function loadData()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 1. Kalkulasi Periode Sebelumnya untuk Growth (YoY/MoM sesuai range)
        $days = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($days);
        $prevEnd = $end->copy()->subDays($days);

        // Reset data
        $this->revenueItems = [];
        $this->expenseItems = [];

        // --- 2. OPTIMASI REVENUE & DISCOUNTS ---
        // Menghitung Penjualan per Kasir, Total Diskon, dan Penjualan Periode Lalu dalam SATU QUERY
        $salesData = Sale::query()
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.status', 'completed')
            ->selectRaw("
                users.first_name, users.last_name,
                SUM(CASE WHEN sales.created_at BETWEEN '{$start}' AND '{$end}' THEN sales.subtotal ELSE 0 END) as current_subtotal,
                SUM(CASE WHEN sales.created_at BETWEEN '{$start}' AND '{$end}' THEN sales.discount ELSE 0 END) as current_discount,
                SUM(CASE WHEN sales.created_at BETWEEN '{$prevStart}' AND '{$prevEnd}' THEN sales.subtotal - sales.discount ELSE 0 END) as prev_net_revenue
            ")
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->get();

        $totalCurrentDiscount = 0;
        $totalPrevNetRevenue = 0;

        foreach ($salesData as $row) {
            if ($row->current_subtotal > 0) {
                $this->revenueItems[] = [
                    'name' => "{$row->first_name} {$row->last_name}",
                    'amount' => (float) $row->current_subtotal
                ];
            }
            $totalCurrentDiscount += $row->current_discount;
            $totalPrevNetRevenue += $row->prev_net_revenue;
        }

        if ($totalCurrentDiscount > 0) {
            $this->revenueItems[] = [
                'name' => __('Discounts & Promotions'),
                'amount' => -(float) $totalCurrentDiscount
            ];
        }

        // --- 3. OPTIMASI TRANSAKSI (INCOME & EXPENSE) ---
        $transactions = Transaction::query()
            ->where('status', 'completed')
            ->selectRaw("
                type, category,
                SUM(CASE WHEN date BETWEEN '{$start->format('Y-m-d')}' AND '{$end->format('Y-m-d')}' THEN amount ELSE 0 END) as current_total,
                SUM(CASE WHEN date BETWEEN '{$prevStart->format('Y-m-d')}' AND '{$prevEnd->format('Y-m-d')}' THEN amount ELSE 0 END) as prev_total
            ")
            ->groupBy('type', 'category')
            ->get();

        $prevOtherIncome = 0;
        $prevOtherExpense = 0;

        foreach ($transactions as $trans) {
            if ($trans->type === 'income') {
                if ($trans->current_total > 0) {
                    $this->revenueItems[] = ['name' => $trans->category ?? __('Other Income'), 'amount' => (float)$trans->current_total];
                }
                $prevOtherIncome += $trans->prev_total;
            } else {
                if ($trans->current_total > 0) {
                    $this->expenseItems[] = ['name' => $trans->category ?? __('Other Expense'), 'amount' => (float)$trans->current_total];
                }
                $prevOtherExpense += $trans->prev_total;
            }
        }

        // --- 4. OPTIMASI COGS (HPP) ---
        $cogsData = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->selectRaw("
                SUM(CASE WHEN sales.created_at BETWEEN '{$start}' AND '{$end}' THEN sale_items.quantity * products.cost ELSE 0 END) as current_cogs,
                SUM(CASE WHEN sales.created_at BETWEEN '{$prevStart}' AND '{$prevEnd}' THEN sale_items.quantity * products.cost ELSE 0 END) as prev_cogs
            ")
            ->first();

        if ($cogsData->current_cogs > 0) {
            $this->expenseItems[] = [
                'name' => __('Cost of Goods Sold (COGS)'),
                'amount' => (float) $cogsData->current_cogs
            ];
        }

        // --- 5. FINAL GROWTH & MARGIN CALCULATIONS ---
        $currentRevenue = $this->getTotalRevenue();
        $prevTotalRevenue = (float)$totalPrevNetRevenue + $prevOtherIncome;
        $this->revenueGrowth = $this->calculateGrowth($currentRevenue, $prevTotalRevenue);

        $currentExpenses = $this->getTotalExpenses();
        $prevTotalExpenses = (float)$cogsData->prev_cogs + $prevOtherExpense;
        $this->expenseGrowth = $this->calculateGrowth($currentExpenses, $prevTotalExpenses);

        $netProfit = $currentRevenue - $currentExpenses;
        $this->netProfitMargin = $currentRevenue != 0 ? ($netProfit / $currentRevenue) * 100 : 0;
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        // Menggunakan abs() pada pembagi untuk menangani pertumbuhan dari nilai negatif
        return (($current - $previous) / abs($previous)) * 100;
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

    // --- EXPORT HANDLERS ---
    public function exportExcel()
    {
        return Excel::download(new ProfitLossExport(
            $this->revenueItems,
            $this->expenseItems,
            $this->getTotalRevenue(),
            $this->getTotalExpenses(),
            $this->getNetProfit(),
            $this->startDate,
            $this->endDate
        ), 'profit-loss-' . $this->startDate . '.xlsx');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.profit-loss', [
            'revenueItems' => $this->revenueItems,
            'expenseItems' => $this->expenseItems,
            'totalRevenue' => $this->getTotalRevenue(),
            'totalExpenses' => $this->getTotalExpenses(),
            'netProfit' => $this->getNetProfit(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'netProfitMargin' => $this->netProfitMargin
        ]);

        return response()->streamDownload(fn() => print($pdf->output()), 'profit-loss.pdf');
    }
}; ?>

<div class="p-6 space-y-6 transition-colors duration-300">
    <div class="mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Profit & Loss Statement') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Financial performance overview') }}
                </p>
            </div>
            
             <!-- Date Filters & Export -->
            <div class="flex flex-col sm:flex-row gap-3 items-center">
                 <div class="flex items-center gap-2 bg-white dark:bg-gray-700 p-1 rounded-xl border border-gray-200 dark:border-gray-600">
                    <input wire:model.live="startDate" type="date" class="border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-200 p-2">
                    <span class="text-gray-400">-</span>
                    <input wire:model.live="endDate" type="date" class="border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-200 p-2">
                 </div>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/20">
                        <i class="fas fa-file-export mr-2"></i> {{ __('Export') }}
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 z-50 py-1" style="display: none;">
                        <button wire:click="exportExcel" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <i class="fas fa-file-excel mr-2 text-green-600 dark:text-green-400"></i> Export Excel
                        </button>
                        <button wire:click="exportPdf" @click="open = false" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <i class="fas fa-file-pdf mr-2 text-red-600 dark:text-red-400"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Revenue -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 group hover:border-green-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</p>
                        <h3 class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            Rp. {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-2xl text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm {{ $revenueGrowth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    <span class="flex items-center font-medium bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-lg mr-2">
                         <i class="fas fa-{{ $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i> {{ number_format(abs($revenueGrowth), 1, ',', '.') }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('vs last period') }}</span>
                </div>
            </div>

            <!-- Total Expenses -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 group hover:border-red-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Expenses') }}</p>
                        <h3 class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            Rp. {{ number_format($this->getTotalExpenses(), 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-2xl text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm {{ $expenseGrowth <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    <span class="flex items-center font-medium bg-red-100 dark:bg-red-900/30 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-{{ $expenseGrowth > 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i> {{ number_format(abs($expenseGrowth), 1, ',', '.') }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('vs last period') }}</span>
                </div>
            </div>

            <!-- Net Profit -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 group hover:border-indigo-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Net Profit') }}</p>
                        <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                            Rp. {{ number_format($this->getNetProfit(), 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm text-indigo-600 dark:text-indigo-400">
                     <span class="flex items-center font-medium bg-indigo-100 dark:bg-indigo-900/30 px-2 py-0.5 rounded-lg mr-2">
                        <i class="fas fa-chart-pie mr-1"></i> {{ number_format($netProfitMargin, 1, ',', '.') }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Net Margin') }}</span>
                </div>
            </div>
        </div>

        <!-- Detailed P&L Statement -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Income Statement') }}</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </span>
            </div>

            <div class="p-6 space-y-8">
                <!-- Revenue Section -->
                <div>
                    <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-4">{{ __('Cashier Sales') }}</h3>
                    <div class="space-y-3">
                        @foreach($revenueItems as $item)
                        <div class="flex justify-between items-center py-2 border-b border-dashed border-gray-100 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">{{ $item['name'] }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                        <div class="flex justify-between items-center py-4 bg-green-50 dark:bg-green-900/20 px-6 rounded-2xl mt-4">
                            <span class="font-bold text-green-800 dark:text-green-300">{{ __('Total Revenue') }}</span>
                            <span class="font-bold text-green-800 dark:text-green-300">Rp. {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Expenses Section -->
                <div>
                    <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-4">{{ __('Expenses') }}</h3>
                    <div class="space-y-3">
                        @foreach($expenseItems as $item)
                        <div class="flex justify-between items-center py-2 border-b border-dashed border-gray-100 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">{{ $item['name'] }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                        <div class="flex justify-between items-center py-4 bg-red-50 dark:bg-red-900/20 px-6 rounded-2xl mt-4">
                            <span class="font-bold text-red-800 dark:text-red-300">{{ __('Total Expenses') }}</span>
                            <span class="font-bold text-red-800 dark:text-red-300">Rp. {{ number_format($this->getTotalExpenses(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Net Profit -->
                <div class="flex justify-between items-center py-6 bg-gray-900 dark:bg-black text-white px-8 rounded-2xl shadow-lg mt-6">
                    <span class="text-xl font-bold">{{ __('Net Profit') }}</span>
                    <span class="text-3xl font-bold text-indigo-400">Rp. {{ number_format($this->getNetProfit(), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
