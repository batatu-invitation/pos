<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $asOfDate;
    public $report = [];

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
        
        $this->report = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
        ];

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
            if ($account->type === 'asset') {
                $balance = $debit - $credit;
                if (abs($balance) > 0.001) {
                    $this->report['assets'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                    $this->report['total_assets'] += $balance;
                }
            } elseif ($account->type === 'liability') {
                $balance = $credit - $debit;
                if (abs($balance) > 0.001) {
                    $this->report['liabilities'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                    $this->report['total_liabilities'] += $balance;
                }
            } elseif ($account->type === 'equity') {
                $balance = $credit - $debit;
                if (abs($balance) > 0.001) {
                    $this->report['equity'][] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
                    $this->report['total_equity'] += $balance;
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
            $this->report['equity'][] = ['name' => 'Retained Earnings (Net Income)', 'code' => 'RE', 'balance' => $netIncome];
            $this->report['total_equity'] += $netIncome;
        }
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Balance Sheet</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Statement of financial position as of a specific date.</p>
        </div>
        
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">As of Date:</label>
            <input wire:model.live="asOfDate" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Assets</h3>
            </div>
            <div class="p-6">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($report['assets'] as $asset)
                            <tr>
                                <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $asset['code'] }} - {{ $asset['name'] }}</td>
                                <td class="py-2 text-sm text-right font-medium text-gray-900 dark:text-white">{{ number_format($asset['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="py-3 text-base font-bold text-gray-900 dark:text-white">Total Assets</td>
                            <td class="py-3 text-base font-bold text-right text-gray-900 dark:text-white">{{ number_format($report['total_assets'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Liabilities & Equity -->
        <div class="space-y-6">
            <!-- Liabilities -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Liabilities</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($report['liabilities'] as $liab)
                                <tr>
                                    <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $liab['code'] }} - {{ $liab['name'] }}</td>
                                    <td class="py-2 text-sm text-right font-medium text-gray-900 dark:text-white">{{ number_format($liab['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td class="py-3 text-base font-bold text-gray-900 dark:text-white">Total Liabilities</td>
                                <td class="py-3 text-base font-bold text-right text-gray-900 dark:text-white">{{ number_format($report['total_liabilities'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Equity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Equity</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($report['equity'] as $eq)
                                <tr>
                                    <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $eq['code'] }} - {{ $eq['name'] }}</td>
                                    <td class="py-2 text-sm text-right font-medium text-gray-900 dark:text-white">{{ number_format($eq['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td class="py-3 text-base font-bold text-gray-900 dark:text-white">Total Equity</td>
                                <td class="py-3 text-base font-bold text-right text-gray-900 dark:text-white">{{ number_format($report['total_equity'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
             <!-- Total L+E -->
             <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
                 <div class="flex justify-between items-center">
                     <span class="text-lg font-bold text-gray-900 dark:text-white">Total Liabilities & Equity</span>
                     <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}</span>
                 </div>
             </div>
        </div>
    </div>
</div>
