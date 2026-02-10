<?php

use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceSheetExport;
use Barryvdh\DomPDF\Facade\Pdf;

new #[Layout('components.layouts.app')]
    #[Title('Balance Sheet')]
    class extends Component {
    public $asOfDate;
    
    // Structured Data for View & Export
    public $assets = [];
    public $liabilities = [];
    public $equity = [];
    
    public $totalAssets = 0;
    public $totalLiabilities = 0;
    public $totalEquity = 0;

    public function mount()
    {
        $this->asOfDate = Carbon::now()->format('Y-m-d');
        $this->generateReport();
    }

    public function updatedAsOfDate()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        $accounts = Account::whereIn('type', ['asset', 'liability', 'equity'])->orderBy('code')->get();
        
        $this->assets = [];
        $this->liabilities = [];
        $this->equity = [];
        $this->totalAssets = 0;
        $this->totalLiabilities = 0;
        $this->totalEquity = 0;

        foreach ($accounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function($q) {
                    $q->where('date', '<=', $this->asOfDate)
                      ->where('status', 'posted');
                })
                ->get();
            
            $debit = $items->sum('debit');
            $credit = $items->sum('credit');
            
            $balance = 0;
            $groupName = $account->subtype ?? 'Other';
            
            if ($account->type === 'asset') {
                $balance = $debit - $credit;
                if (abs($balance) > 0.001) {
                    $this->assets[$groupName][] = ['name' => $account->name, 'code' => $account->code, 'amount' => $balance];
                    $this->totalAssets += $balance;
                }
            } elseif ($account->type === 'liability') {
                $balance = $credit - $debit;
                if (abs($balance) > 0.001) {
                    $this->liabilities[$groupName][] = ['name' => $account->name, 'code' => $account->code, 'amount' => $balance];
                    $this->totalLiabilities += $balance;
                }
            } elseif ($account->type === 'equity') {
                $balance = $credit - $debit;
                if (abs($balance) > 0.001) {
                    $this->equity[$groupName][] = ['name' => $account->name, 'code' => $account->code, 'amount' => $balance];
                    $this->totalEquity += $balance;
                }
            }
        }
        
        // Calculate Retained Earnings (Net Income)
        $incomeAccounts = Account::where('type', 'income')->pluck('id');
        $expenseAccounts = Account::where('type', 'expense')->pluck('id');
        
        $income = JournalEntryItem::whereIn('account_id', $incomeAccounts)
            ->whereHas('journalEntry', fn($q) => $q->where('date', '<=', $this->asOfDate)->where('status', 'posted'))
            ->sum(DB::raw('credit - debit'));
            
        $expenses = JournalEntryItem::whereIn('account_id', $expenseAccounts)
            ->whereHas('journalEntry', fn($q) => $q->where('date', '<=', $this->asOfDate)->where('status', 'posted'))
            ->sum(DB::raw('debit - credit'));
            
        $netIncome = $income - $expenses;
        
        if (abs($netIncome) > 0.001) {
            $this->equity['Retained Earnings'][] = ['name' => 'Net Income (Retained Earnings)', 'code' => 'RE', 'amount' => $netIncome];
            $this->totalEquity += $netIncome;
        }
    }

    public function exportExcel()
    {
        $this->generateReport();
        return Excel::download(new BalanceSheetExport(
            $this->assets,
            $this->liabilities,
            $this->equity,
            $this->totalAssets,
            $this->totalLiabilities,
            $this->totalEquity,
            $this->asOfDate
        ), 'balance-sheet.xlsx');
    }

    public function exportPdf()
    {
        $this->generateReport();
        $pdf = Pdf::loadView('pdf.balance-sheet', [
            'assets' => $this->assets,
            'liabilities' => $this->liabilities,
            'equity' => $this->equity,
            'totalAssets' => $this->totalAssets,
            'totalLiabilities' => $this->totalLiabilities,
            'totalEquity' => $this->totalEquity,
            'date' => $this->asOfDate,
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'balance-sheet.pdf');
    }
};
?>

<div class="min-h-screen bg-gray-50/50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 lg:p-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700/50">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-400 dark:to-cyan-400 bg-clip-text text-transparent">
                    {{ __('Balance Sheet') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Statement of financial position') }}
                </p>
            </div>
            
            <!-- Date Filters & Export -->
            <div class="flex flex-col sm:flex-row gap-3 items-center">
                 <div class="flex items-center gap-2 bg-white dark:bg-gray-700 p-1 rounded-xl border border-gray-200 dark:border-gray-600">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2">{{ __('As of') }}:</label>
                    <input wire:model.live="asOfDate" type="date" class="border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-200 p-2">
                 </div>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20">
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

        <!-- Summary Cards (Bento Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Assets -->
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Assets</span>
                        <i class="fas fa-building text-blue-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($totalAssets, 0, ',', '.') }}
                    </div>
                    <div class="text-blue-100 text-sm opacity-90">
                        Owned resources
                    </div>
                </div>
            </div>

            <!-- Total Liabilities -->
            <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-3xl p-6 text-white shadow-lg shadow-red-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Liabilities</span>
                        <i class="fas fa-file-invoice-dollar text-red-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($totalLiabilities, 0, ',', '.') }}
                    </div>
                    <div class="text-red-100 text-sm opacity-90">
                        Obligations owed
                    </div>
                </div>
            </div>

            <!-- Total Equity -->
            <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-3xl p-6 text-white shadow-lg shadow-purple-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Equity</span>
                        <i class="fas fa-balance-scale text-purple-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($totalEquity, 0, ',', '.') }}
                    </div>
                    <div class="text-purple-100 text-sm opacity-90">
                        Net value
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Assets Column -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="w-2 h-6 bg-blue-500 rounded-full"></span>
                        {{ __('Assets') }}
                    </h3>
                </div>
                <div class="p-6 flex-1 overflow-y-auto">
                    <div class="space-y-6">
                        @foreach($assets as $group => $items)
                        <div>
                            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $group }}</h4>
                            <div class="space-y-2">
                                @foreach($items as $item)
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $item['name'] }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 px-6 py-4 border-t border-blue-100 dark:border-blue-800/30">
                     <div class="flex justify-between items-center">
                        <span class="font-bold text-blue-900 dark:text-blue-300">{{ __('Total Assets') }}</span>
                        <span class="font-bold text-xl text-blue-900 dark:text-blue-300">Rp. {{ number_format($totalAssets, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Liabilities & Equity Column -->
            <div class="space-y-6">
                <!-- Liabilities -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-6 bg-red-500 rounded-full"></span>
                            {{ __('Liabilities') }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            @foreach($liabilities as $group => $items)
                            <div>
                                <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $group }}</h4>
                                <div class="space-y-2">
                                    @foreach($items as $item)
                                    <div class="flex justify-between items-center py-2 border-b border-dashed border-gray-100 dark:border-gray-700 last:border-0">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $item['name'] }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 px-6 py-4 border-t border-red-100 dark:border-red-800/30">
                         <div class="flex justify-between items-center">
                            <span class="font-bold text-red-900 dark:text-red-300">{{ __('Total Liabilities') }}</span>
                            <span class="font-bold text-xl text-red-900 dark:text-red-300">Rp. {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Equity -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-6 bg-purple-500 rounded-full"></span>
                            {{ __('Equity') }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            @foreach($equity as $group => $items)
                            <div>
                                <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $group }}</h4>
                                <div class="space-y-2">
                                    @foreach($items as $item)
                                    <div class="flex justify-between items-center py-2 border-b border-dashed border-gray-100 dark:border-gray-700 last:border-0">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $item['name'] }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 px-6 py-4 border-t border-purple-100 dark:border-purple-800/30">
                         <div class="flex justify-between items-center">
                            <span class="font-bold text-purple-900 dark:text-purple-300">{{ __('Total Equity') }}</span>
                            <span class="font-bold text-xl text-purple-900 dark:text-purple-300">Rp. {{ number_format($totalEquity, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                
                 <!-- Grand Total -->
                 <div class="bg-gray-900 dark:bg-black rounded-2xl shadow-xl p-6 flex justify-between items-center">
                     <span class="text-lg font-bold text-white">{{ __('Total Liabilities & Equity') }}</span>
                     <span class="text-2xl font-bold text-emerald-400">Rp. {{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</span>
                 </div>
            </div>
        </div>
    </div>
</div>
