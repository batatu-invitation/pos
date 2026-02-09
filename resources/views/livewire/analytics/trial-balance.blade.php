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

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Trial Balance</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Summary of ending balances of all general ledger accounts.</p>
        </div>
        
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">As of Date:</label>
            <input wire:model.live="asOfDate" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Trial Balance Report</h3>
        </div>
        <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Debit</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($report['lines'] as $line)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $line['code'] }} - {{ $line['name'] }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                {{ $line['debit'] > 0 ? 'Rp. ' . number_format($line['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                {{ $line['credit'] > 0 ? 'Rp. ' . number_format($line['credit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 dark:bg-gray-900/50 font-bold">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">Total</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                            Rp. {{ number_format($report['total_debit'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                            Rp. {{ number_format($report['total_credit'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
