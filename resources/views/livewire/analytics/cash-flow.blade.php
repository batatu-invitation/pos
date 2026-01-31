<?php

use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Cash Flow - Modern POS')]
class extends Component
{
    public $operatingActivities = [];
    public $investingActivities = [];
    public $financingActivities = [];
    public $startDate;
    public $endDate;

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

        // --- Operating Activities ---
        $this->operatingActivities = [];

        // 1. Cash from Sales (Inflow)
        // We use total_amount as that represents the actual cash/payment obligation
        $cashFromSales = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_amount');

        if ($cashFromSales > 0) {
            $this->operatingActivities[] = [
                'name' => __('Cash Receipts from Customers'),
                'amount' => $cashFromSales,
                'type' => 'positive'
            ];
        }

        // 2. Other Income (Inflow)
        $otherIncome = Transaction::where('type', 'income')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($otherIncome as $income) {
            $this->operatingActivities[] = [
                'name' => $income->category ?? __('Other Income'),
                'amount' => $income->total,
                'type' => 'positive'
            ];
        }

        // 3. Operational Expenses (Outflow)
        $expenses = Transaction::where('type', 'expense')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        foreach ($expenses as $expense) {
            $this->operatingActivities[] = [
                'name' => $expense->category ?? __('Other Expense'),
                'amount' => $expense->total,
                'type' => 'negative'
            ];
        }

        // --- Investing Activities ---
        // Currently no dedicated model for assets/investments, so we leave empty or placeholder
        $this->investingActivities = [];

        // --- Financing Activities ---
        // Currently no dedicated model for loans/equity, so we leave empty or placeholder
        $this->financingActivities = [];
    }

    public function calculateTotal($activities)
    {
        $total = 0;
        foreach ($activities as $activity) {
            if ($activity['type'] === 'positive') {
                $total += $activity['amount'];
            } else {
                $total -= $activity['amount'];
            }
        }
        return $total;
    }
}; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none mr-4 md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-semibold text-gray-800">{{ __('Cash Flow Statement') }}</h1>
            </div>

            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                <a href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-cash-register mr-2"></i>
                    {{ __('Open POS') }}
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Start Date') }}</label>
                            <input type="date" wire:model="startDate" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('End Date') }}</label>
                            <input type="date" wire:model="endDate" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        </div>
                        <div class="relative self-end">
                            <button wire:click="loadData" class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-download mr-2"></i> {{ __('Export') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cash Flow Statement -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">{{ __('Cash Flow Statement') }}</h2>
                    <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
                </div>

                <div class="p-6">
                    <!-- Operating Activities -->
                    <div class="mb-8">
                        <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Operating Activities') }}</h3>
                        <div class="space-y-3">
                            @foreach($operatingActivities as $activity)
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-700">{{ $activity['name'] }}</span>
                                <span class="font-medium {{ $activity['type'] === 'negative' ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                </span>
                            </div>
                            @endforeach

                            <div class="flex justify-between items-center py-3 bg-indigo-50 px-4 rounded-lg mt-2">
                                <span class="font-bold text-indigo-900">{{ __('Net Cash from Operating Activities') }}</span>
                                @php $totalOperating = $this->calculateTotal($operatingActivities); @endphp
                                <span class="font-bold {{ $totalOperating < 0 ? 'text-red-700' : 'text-indigo-900' }}">
                                    {{ $totalOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalOperating), 0, ',', '.') }}{{ $totalOperating < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Investing Activities -->
                    <div class="mb-8">
                        <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Investing Activities') }}</h3>
                        <div class="space-y-3">
                            @if(count($investingActivities) > 0)
                                @foreach($investingActivities as $activity)
                                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                    <span class="text-gray-700">{{ $activity['name'] }}</span>
                                    <span class="font-medium {{ $activity['type'] === 'negative' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 text-sm italic">{{ __('No investing activities recorded.') }}</div>
                            @endif

                            <div class="flex justify-between items-center py-3 bg-indigo-50 px-4 rounded-lg mt-2">
                                <span class="font-bold text-indigo-900">{{ __('Net Cash from Investing Activities') }}</span>
                                @php $totalInvesting = $this->calculateTotal($investingActivities); @endphp
                                <span class="font-bold {{ $totalInvesting < 0 ? 'text-red-700' : 'text-indigo-900' }}">
                                    {{ $totalInvesting < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalInvesting), 0, ',', '.') }}{{ $totalInvesting < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financing Activities -->
                    <div class="mb-8">
                        <h3 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-4">{{ __('Financing Activities') }}</h3>
                        <div class="space-y-3">
                            @if(count($financingActivities) > 0)
                                @foreach($financingActivities as $activity)
                                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                    <span class="text-gray-700">{{ $activity['name'] }}</span>
                                    <span class="font-medium {{ $activity['type'] === 'negative' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                                    </span>
                                </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 text-sm italic">{{ __('No financing activities recorded.') }}</div>
                            @endif

                            <div class="flex justify-between items-center py-3 bg-indigo-50 px-4 rounded-lg mt-2">
                                <span class="font-bold text-indigo-900">{{ __('Net Cash from Financing Activities') }}</span>
                                @php $totalFinancing = $this->calculateTotal($financingActivities); @endphp
                                <span class="font-bold {{ $totalFinancing < 0 ? 'text-red-700' : 'text-indigo-900' }}">
                                    {{ $totalFinancing < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalFinancing), 0, ',', '.') }}{{ $totalFinancing < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Cash Flow -->
                    <div class="mt-8 border-t-2 border-gray-200 pt-6">
                        <div class="flex justify-between items-center py-4 bg-gray-900 text-white px-6 rounded-xl shadow-lg">
                            <span class="text-lg font-bold">{{ __('Net Increase (Decrease) in Cash') }}</span>
                            @php $netCash = $totalOperating + $totalInvesting + $totalFinancing; @endphp
                            <span class="text-xl font-bold {{ $netCash < 0 ? 'text-red-400' : 'text-green-400' }}">
                                {{ $netCash < 0 ? '(' : '' }}Rp. {{ number_format(abs($netCash), 0, ',', '.') }}{{ $netCash < 0 ? ')' : '' }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>
