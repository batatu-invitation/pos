<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;

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
        $accounts = Account::orderBy('code')->get();
        
        $this->report = [
            'lines' => [],
            'total_debit' => 0,
            'total_credit' => 0,
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
            
            $netDebit = 0;
            $netCredit = 0;
            
            if ($debit > $credit) {
                $netDebit = $debit - $credit;
            } else {
                $netCredit = $credit - $debit;
            }
            
            if ($netDebit > 0 || $netCredit > 0) {
                $this->report['lines'][] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'debit' => $netDebit,
                    'credit' => $netCredit,
                ];
                
                $this->report['total_debit'] += $netDebit;
                $this->report['total_credit'] += $netCredit;
            }
        }
    }
};
?>

<div class="p-6 space-y-6 transition-colors duration-300">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Trial Balance') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Summary of ending balances of all general ledger accounts.') }}
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('As of Date:') }}</label>
                <input wire:model.live="asOfDate" type="date" class="rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
        </div>

        <!-- Report Section -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Trial Balance Report') }}</h3>
            </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                        <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Account') }}</th>
                        <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Debit') }}</th>
                        <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($report['lines'] as $line)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">{{ $line['code'] }} - {{ $line['name'] }}</td>
                            <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                {{ $line['debit'] > 0 ? 'Rp. ' . number_format($line['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                {{ $line['credit'] > 0 ? 'Rp. ' . number_format($line['credit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/50 dark:bg-gray-700/50 font-bold text-gray-900 dark:text-white border-t border-gray-100 dark:border-gray-700">
                    <tr>
                        <td class="p-4">{{ __('Total') }}</td>
                        <td class="p-4 text-right text-indigo-600 dark:text-indigo-400">Rp. {{ number_format($report['total_debit'], 0, ',', '.') }}</td>
                        <td class="p-4 text-right text-indigo-600 dark:text-indigo-400">Rp. {{ number_format($report['total_credit'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
