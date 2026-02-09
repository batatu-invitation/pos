<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;

new class extends Component {
    public $startDate;
    public $endDate;
    public $report = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->generateReport();
    }

    public function updatedStartDate() { $this->generateReport(); }
    public function updatedEndDate() { $this->generateReport(); }

    public function generateReport()
    {
        $this->report = [
            'income' => [],
            'expenses' => [],
            'total_income' => 0,
            'total_expenses' => 0,
            'net_profit' => 0,
        ];

        // Income
        $incomeAccounts = Account::where('type', 'income')->orderBy('code')->get();
        foreach ($incomeAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                })
                ->get();
            
            // Income is Credit normal
            $balance = $items->sum('credit') - $items->sum('debit');
            
            if (abs($balance) > 0.001) {
                $this->report['income'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                $this->report['total_income'] += $balance;
            }
        }

        // Expenses
        $expenseAccounts = Account::where('type', 'expense')->orderBy('code')->get();
        foreach ($expenseAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate])
                      ->where('status', 'posted');
                })
                ->get();
            
            // Expense is Debit normal
            $balance = $items->sum('debit') - $items->sum('credit');
            
            if (abs($balance) > 0.001) {
                $this->report['expenses'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                $this->report['total_expenses'] += $balance;
            }
        }

        $this->report['net_profit'] = $this->report['total_income'] - $this->report['total_expenses'];
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Profit & Loss</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Monitor business profit performance in real time.</p>
        </div>
        
        <div class="flex items-center gap-2">
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                <input wire:model.live="startDate" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">End Date</label>
                <input wire:model.live="endDate" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 space-y-8">
            <!-- Income Section -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Income</h3>
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($report['income'] as $inc)
                            <tr>
                                <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $inc['code'] }} - {{ $inc['name'] }}</td>
                                <td class="py-2 text-sm text-right font-medium text-gray-900 dark:text-white">Rp. {{ number_format($inc['balance'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td class="py-3 pl-2 text-base font-bold text-gray-900 dark:text-white">Total Income</td>
                            <td class="py-3 pr-2 text-base font-bold text-right text-gray-900 dark:text-white">Rp. {{ number_format($report['total_income'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Expense Section -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Expenses</h3>
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($report['expenses'] as $exp)
                            <tr>
                                <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $exp['code'] }} - {{ $exp['name'] }}</td>
                                <td class="py-2 text-sm text-right font-medium text-gray-900 dark:text-white">Rp. {{ number_format($exp['balance'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-red-50 dark:bg-red-900/20">
                            <td class="py-3 pl-2 text-base font-bold text-gray-900 dark:text-white">Total Expenses</td>
                            <td class="py-3 pr-2 text-base font-bold text-right text-gray-900 dark:text-white">Rp. {{ number_format($report['total_expenses'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Profit -->
            <div class="border-t-4 border-gray-300 dark:border-gray-600 pt-4">
                <div class="flex justify-between items-center p-4 rounded-lg {{ $report['net_profit'] >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200' }}">
                    <span class="text-xl font-bold">Net Profit</span>
                    <span class="text-xl font-bold">Rp. {{ number_format($report['net_profit'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
