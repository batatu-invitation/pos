<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Balance Sheet')]
class extends Component
{
    public $date;
    public $assets = [];
    public $liabilities = [];
    public $equity = [];
    public $totalAssets = 0;
    public $totalLiabilities = 0;
    public $totalEquity = 0;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $asOfDate = Carbon::parse($this->date)->endOfDay();

        // --- ASSETS ---
        $this->assets = [];

        // 1. Cash & Equivalents
        // Cash from Sales (Inflow)
        $cashInSales = Sale::where('status', 'completed')
            ->where('created_at', '<=', $asOfDate)
            ->sum('total_amount');

        // Cash from Income Transactions (Inflow)
        $cashInTrans = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        // Cash Out (Expense Transactions)
        $cashOutTrans = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        // Net Cash
        $cashOnHand = ($cashInSales + $cashInTrans) - $cashOutTrans;

        $this->assets['Current Assets'][] = [
            'name' => __('Cash & Bank'),
            'amount' => $cashOnHand
        ];

        // 2. Accounts Receivable (Pending Sales)
        $receivables = Sale::where('status', 'pending')
            ->where('created_at', '<=', $asOfDate)
            ->sum('total_amount');

        if ($receivables > 0) {
            $this->assets['Current Assets'][] = [
                'name' => __('Accounts Receivable'),
                'amount' => $receivables
            ];
        }

        // 3. Inventory Value
        // Note: Using current snapshot as proxy
        $inventoryValue = Product::where('status', 'active')->sum(DB::raw('cost * stock'));

        if ($inventoryValue > 0) {
            $this->assets['Current Assets'][] = [
                'name' => __('Inventory Asset'),
                'amount' => $inventoryValue
            ];
        }

        // Calculate Total Assets
        $this->totalAssets = 0;
        foreach ($this->assets as $group) {
            foreach ($group as $item) {
                $this->totalAssets += $item['amount'];
            }
        }

        // --- LIABILITIES ---
        $this->liabilities = [];

        // 1. Accounts Payable (Pending Expenses)
        $payables = Transaction::where('type', 'expense')
            ->where('status', 'pending')
            ->where('date', '<=', $this->date)
            ->sum('amount');

        if ($payables > 0) {
            $this->liabilities['Current Liabilities'][] = [
                'name' => __('Accounts Payable'),
                'amount' => $payables
            ];
        }

        // Calculate Total Liabilities
        $this->totalLiabilities = 0;
        foreach ($this->liabilities as $group) {
            foreach ($group as $item) {
                $this->totalLiabilities += $item['amount'];
            }
        }

        // --- EQUITY ---
        $this->equity = [];

        // Retained Earnings = Assets - Liabilities
        $retainedEarnings = $this->totalAssets - $this->totalLiabilities;

        $this->equity['Equity'][] = [
            'name' => __('Retained Earnings'),
            'amount' => $retainedEarnings
        ];

        $this->totalEquity = $retainedEarnings;
    }
}; ?>

<div class="p-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Balance Sheet') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Financial position snapshot') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ __('As of:') }}</label>
            <input type="date" wire:model="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            <button wire:click="loadData" class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                {{ __('Apply') }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assets Side -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-semibold text-gray-800">{{ __('Assets') }}</h3>
                </div>
                <div class="p-4">
                    @forelse($assets as $category => $items)
                        <div class="mb-6 last:mb-0">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $category }}</h4>
                            <div class="space-y-3">
                                @foreach($items as $item)
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-700">{{ $item['name'] }}</span>
                                        <span class="font-medium text-gray-900">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic mb-4">{{ __('No assets recorded.') }}</p>
                    @endforelse
                    
                    <div class="pt-4 mt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-800">{{ __('Total Assets') }}</span>
                        <span class="font-bold text-indigo-600">Rp. {{ number_format($totalAssets, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liabilities & Equity Side -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-semibold text-gray-800">{{ __('Liabilities & Equity') }}</h3>
                </div>
                <div class="p-4">
                    <!-- Liabilities -->
                    @if(!empty($liabilities))
                        @foreach($liabilities as $category => $items)
                            <div class="mb-6">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $category }}</h4>
                                <div class="space-y-3">
                                    @foreach($items as $item)
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-700">{{ $item['name'] }}</span>
                                            <span class="font-medium text-gray-900">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="flex justify-between items-center mt-2 text-sm font-medium text-gray-600">
                                    <span>{{ __('Total Liabilities') }}</span>
                                    <span>Rp. {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                         <div class="mb-6">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('Liabilities') }}</h4>
                            <p class="text-sm text-gray-400 italic">{{ __('No liabilities recorded.') }}</p>
                        </div>
                    @endif

                    <!-- Equity -->
                    <div class="pt-4 border-t border-gray-100">
                         @foreach($equity as $category => $items)
                            <div class="mb-4">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $category }}</h4>
                                <div class="space-y-3">
                                    @foreach($items as $item)
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-700">{{ $item['name'] }}</span>
                                            <span class="font-medium text-gray-900">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-800">{{ __('Total Liabilities & Equity') }}</span>
                        <span class="font-bold text-indigo-600">Rp. {{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
