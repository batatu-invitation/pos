<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GeneralLedgerExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;

new #[Title('General Ledger')] class extends Component {
    public $accountId;
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getLedgerData()
    {
        $accounts = Account::orderBy('code')->get();
        
        $openingBalance = 0;
        $ledgerItems = [];
        $selectedAccount = null;

        $totalPeriodDebits = 0;
        $totalPeriodCredits = 0;
        $closingBalance = 0;

        if ($this->accountId) {
            $selectedAccount = Account::find($this->accountId);
            
            if ($selectedAccount) {
                // Determine account normal balance
                $isDebitNormal = in_array($selectedAccount->type, ['asset', 'expense']);

                // Calculate Opening Balance (only posted entries)
                $prevItems = JournalEntryItem::where('account_id', $this->accountId)
                    ->whereHas('journalEntry', function($q) {
                        $q->where('date', '<', $this->startDate)
                          ->where('status', 'posted');
                    })
                    ->get();

                $prevDebits = $prevItems->sum('debit');
                $prevCredits = $prevItems->sum('credit');

                if ($isDebitNormal) {
                    $openingBalance = $prevDebits - $prevCredits;
                } else {
                    $openingBalance = $prevCredits - $prevDebits;
                }

                // Get Period Items
                $periodItems = JournalEntryItem::with('journalEntry')
                    ->where('account_id', $this->accountId)
                    ->whereHas('journalEntry', function($q) {
                        $q->whereBetween('date', [$this->startDate, $this->endDate])
                           ->where('status', 'posted');
                    })
                    ->get()
                    ->sortBy(function($item) {
                        return $item->journalEntry->date->format('Y-m-d') . '-' . $item->created_at->format('H:i:s');
                    });

                // Calculate Running Balances
                $runningBalance = $openingBalance;
                
                foreach ($periodItems as $item) {
                    $totalPeriodDebits += $item->debit;
                    $totalPeriodCredits += $item->credit;

                    if ($isDebitNormal) {
                        $runningBalance += ($item->debit - $item->credit);
                    } else {
                        $runningBalance += ($item->credit - $item->debit);
                    }
                    
                    $ledgerItems[] = [
                        'date' => $item->journalEntry->date,
                        'reference' => $item->journalEntry->reference,
                        'description' => $item->journalEntry->description,
                        'debit' => $item->debit,
                        'credit' => $item->credit,
                        'balance' => $runningBalance,
                    ];
                }
                $closingBalance = $runningBalance;
            }
        }

        return [
            'accounts' => $accounts,
            'ledgerItems' => $ledgerItems,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'totalPeriodDebits' => $totalPeriodDebits,
            'totalPeriodCredits' => $totalPeriodCredits,
            'selectedAccount' => $selectedAccount,
        ];
    }

    public function with()
    {
        return $this->getLedgerData();
    }

    public function exportExcel()
    {
        $data = $this->getLedgerData();
        return Excel::download(new GeneralLedgerExport(
            $data['ledgerItems'],
            $data['openingBalance'],
            $data['closingBalance'],
            $data['selectedAccount'],
            $this->startDate,
            $this->endDate
        ), 'general-ledger.xlsx');
    }

    public function exportPdf()
    {
        $data = $this->getLedgerData();
        $pdf = Pdf::loadView('pdf.general-ledger', array_merge($data, [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'general-ledger.pdf');
    }
};
?>

<div class="mx-auto p-6 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ __('General Ledger') }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ __('Detailed transaction history and account balances.') }}</p>
        </div>
        
        <!-- Export Button -->
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

    <!-- Filters (Top Bar Style) -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col lg:flex-row gap-4 items-end lg:items-center">
        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">{{ __('Account') }}</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <select wire:model.live="accountId" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm">
                    <option value="">{{ __('Select Account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="w-full lg:w-auto flex gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">{{ __('From') }}</label>
                <input wire:model.live="startDate" type="date" 
                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm py-2.5">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">{{ __('To') }}</label>
                <input wire:model.live="endDate" type="date" 
                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm py-2.5">
            </div>
        </div>
    </div>

    @if($selectedAccount)
        <!-- Bento Grid Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Opening Balance -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Opening Balance') }}</span>
                        <i class="fas fa-wallet text-blue-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($openingBalance, 0, ',', '.') }}
                    </div>
                    <div class="text-blue-100 text-sm opacity-90">
                        {{ __('As of') }} {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                    </div>
                </div>
            </div>

            <!-- Period Activity -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Period Activity') }}</span>
                        <i class="fas fa-exchange-alt text-emerald-100 text-xl"></i>
                    </div>
                    <div class="flex justify-between items-end">
                        <div>
                            <div class="text-xs text-emerald-100 uppercase tracking-wider mb-1">{{ __('Debit') }}</div>
                            <div class="text-xl font-bold">Rp. {{ number_format($totalPeriodDebits, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-emerald-100 uppercase tracking-wider mb-1">{{ __('Credit') }}</div>
                            <div class="text-xl font-bold">Rp. {{ number_format($totalPeriodCredits, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Closing Balance -->
            <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-3xl p-6 text-white shadow-lg shadow-purple-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">{{ __('Closing Balance') }}</span>
                        <i class="fas fa-scale-balanced text-purple-100 text-xl"></i>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        Rp. {{ number_format($closingBalance, 0, ',', '.') }}
                    </div>
                    <div class="text-purple-100 text-sm opacity-90">
                        {{ __('As of') }} {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list-ul text-indigo-500"></i>
                    {{ $selectedAccount->code }} - {{ $selectedAccount->name }}
                </h3>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                    {{ ucfirst($selectedAccount->type) }} Account
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700">
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Date') }}</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Reference') }}</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Description') }}</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Debit') }}</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Credit') }}</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr class="bg-gray-50/80 dark:bg-gray-800/50 font-medium">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white italic" colspan="5">{{ __('Opening Balance') }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-right text-gray-900 dark:text-white">
                                Rp. {{ number_format($openingBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                        @forelse($ledgerItems as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $item['date']->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">{{ $item['reference'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $item['description'] }}">{{ $item['description'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 text-right font-medium">
                                    @if($item['debit'] > 0)
                                        <span class="text-gray-900 dark:text-white">Rp. {{ number_format($item['debit'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 text-right font-medium">
                                    @if($item['credit'] > 0)
                                        <span class="text-gray-900 dark:text-white">Rp. {{ number_format($item['credit'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white text-right bg-gray-50/30 dark:bg-gray-800/30">
                                    Rp. {{ number_format($item['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                            <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 text-base font-medium">{{ __('No transactions found in this period.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-book-open text-3xl text-indigo-500 dark:text-indigo-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Select an Account') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">{{ __('Please select an account from the dropdown above to view its general ledger and transaction history.') }}</p>
        </div>
    @endif
</div>
