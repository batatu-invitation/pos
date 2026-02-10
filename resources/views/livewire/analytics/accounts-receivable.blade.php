<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Illuminate\Support\Str;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $statusFilter = ''; // 'unpaid', 'partial'

    // Payment Modal
    public $showPaymentModal = false;
    public $selectedSale;
    public $paymentAmount = 0;
    public $paymentDate;
    public $depositAccountId; // Debit Account (Cash/Bank)
    public $notes;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->paymentDate = date('Y-m-d');
    }

    public function with()
    {
        $query = Sale::query()
            ->with(['customer', 'user'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('status', 'completed');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function($subQ) {
                      $subQ->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(), 
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        if ($this->statusFilter) {
            $query->where('payment_status', $this->statusFilter);
        }

        // Get Asset accounts for Deposit
        $assetAccounts = Account::where('type', 'asset')->orderBy('code')->get();

        // Calculate Stats
        $baseQuery = Sale::whereIn('payment_status', ['unpaid', 'partial'])
                        ->where('status', 'completed');

        $totalReceivable = (clone $baseQuery)->sum(\DB::raw('total_amount - cash_received'));
        
        $overdueAmount = (clone $baseQuery)
                        ->where('due_date', '<', Carbon::today())
                        ->sum(\DB::raw('total_amount - cash_received'));

        $dueSoonAmount = (clone $baseQuery)
                        ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays(7)])
                        ->sum(\DB::raw('total_amount - cash_received'));

        return [
            'sales' => $query->latest()->paginate(10),
            'assetAccounts' => $assetAccounts,
            'totalReceivable' => $totalReceivable,
            'overdueAmount' => $overdueAmount,
            'dueSoonAmount' => $dueSoonAmount,
        ];
    }

    public function openPaymentModal($saleId)
    {
        $this->selectedSale = Sale::find($saleId);
        $this->paymentAmount = $this->selectedSale->total_amount - $this->selectedSale->cash_received;
        $this->paymentDate = date('Y-m-d');
        $this->notes = 'Payment for Invoice ' . $this->selectedSale->invoice_number;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->reset(['selectedSale', 'paymentAmount', 'depositAccountId', 'notes']);
    }

    public function recordPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'depositAccountId' => 'required|exists:accounts,id',
        ]);

        if (!$this->selectedSale) {
            return;
        }

        $remaining = $this->selectedSale->total_amount - $this->selectedSale->cash_received;

        if ($this->paymentAmount > $remaining) {
            $this->addError('paymentAmount', 'Amount cannot exceed remaining balance of Rp. ' . number_format($remaining, 0, ',', '.'));
            return;
        }

        \DB::transaction(function () {
            // 1. Update Sale
            $this->selectedSale->cash_received += $this->paymentAmount;
            
            if ($this->selectedSale->cash_received >= $this->selectedSale->total_amount) {
                $this->selectedSale->payment_status = 'paid';
            } else {
                $this->selectedSale->payment_status = 'partial';
            }
            
            $this->selectedSale->save();

            // 2. Create Journal Entry
            // Find AR Account
            $arAccount = Account::where('code', '1200')->first(); // Assuming 1200 is AR
            if (!$arAccount) {
                 // Fallback: Try to find by name or create
                 $arAccount = Account::firstOrCreate(
                    ['code' => '1200'],
                    [
                        'name' => 'Accounts Receivable',
                        'type' => 'asset',
                        'subtype' => 'Current Asset',
                        'description' => 'Unpaid customer invoices'
                    ]
                 );
            }

            $journalEntry = JournalEntry::create([
                'date' => $this->paymentDate,
                'reference' => $this->selectedSale->invoice_number,
                'description' => $this->notes ?? 'Payment received for Invoice ' . $this->selectedSale->invoice_number,
                'status' => 'posted', // Auto-post payments
                'type' => 'general',
            ]);

            // Debit Cash/Bank
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $this->depositAccountId,
                'debit' => $this->paymentAmount,
                'credit' => 0,
            ]);

            // Credit Accounts Receivable
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $this->paymentAmount,
            ]);
        });

        $this->dispatch('notify', 
            title: 'Payment Recorded',
            text: 'Payment has been successfully recorded.',
            icon: 'success'
        );

        $this->closePaymentModal();
    }
};

?>

<div class=" mx-auto p-6 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Accounts Receivable</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor unpaid invoices and manage collections efficiently.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm border border-gray-100 dark:border-gray-700">
                {{ \Carbon\Carbon::today()->format('d M Y') }}
            </span>
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Receivable -->
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-blue-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Receivable</span>
                    <i class="fas fa-file-invoice-dollar text-blue-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($totalReceivable, 0, ',', '.') }}
                </div>
                <div class="text-blue-100 text-sm opacity-90">
                    All unpaid & partial invoices
                </div>
            </div>
        </div>

        <!-- Overdue -->
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-purple-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Overdue</span>
                    <i class="fas fa-clock text-purple-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($overdueAmount, 0, ',', '.') }}
                </div>
                <div class="text-purple-100 text-sm opacity-90">
                    Past due date
                </div>
            </div>
        </div>

        <!-- Due Soon -->
        <div class="bg-gradient-to-br from-orange-400 to-amber-500 rounded-3xl p-6 text-white shadow-lg shadow-orange-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Due Soon</span>
                    <i class="fas fa-exclamation-circle text-orange-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($dueSoonAmount, 0, ',', '.') }}
                </div>
                <div class="text-orange-100 text-sm opacity-90">
                    Due within 7 days
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Filters Sidebar (Mobile: Top, Desktop: Left) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Filters</h3>
                
                <div class="space-y-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input wire:model.live="search" type="text" placeholder="Invoice or Customer..." 
                                class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white transition-all text-sm">
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select wire:model.live="statusFilter" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white text-sm">
                            <option value="">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period</label>
                        <div class="space-y-2">
                            <input wire:model.live="startDate" type="date" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white text-sm">
                            <input wire:model.live="endDate" type="date" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice Info</th>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($sales as $sale)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-red-600 transition-colors">
                                                {{ $sale->invoice_number }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $sale->created_at->format('M d, Y') }}
                                            </span>
                                            @if($sale->due_date && \Carbon\Carbon::parse($sale->due_date)->isPast())
                                                <span class="text-[10px] text-red-500 font-medium mt-1">
                                                    Overdue: {{ \Carbon\Carbon::parse($sale->due_date)->diffForHumans() }}
                                                </span>
                                            @elseif($sale->due_date)
                                                <span class="text-[10px] text-gray-400 mt-1">
                                                    Due: {{ \Carbon\Carbon::parse($sale->due_date)->format('M d') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $sale->customer->name ?? 'Walk-in' }}</span>
                                            <span class="text-xs text-gray-400">{{ $sale->customer->phone ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            Rp. {{ number_format($sale->total_amount, 0, ',', '.') }}
                                        </div>
                                        @if($sale->cash_received > 0)
                                            <div class="text-xs text-green-600">
                                                Paid: {{ number_format($sale->cash_received, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                            Rp. {{ number_format($sale->total_amount - $sale->cash_received, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($sale->payment_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800
                                            @elseif($sale->payment_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800
                                            @else bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800 @endif">
                                            {{ ucfirst($sale->payment_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button wire:click="openPaymentModal('{{ $sale->id }}')" 
                                            class="inline-flex items-center justify-center px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-money-bill-wave mr-2"></i> Pay
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                                <i class="fas fa-file-invoice text-gray-400 text-2xl"></i>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-base font-medium">No outstanding invoices found</p>
                                            <p class="text-gray-400 text-sm">Try adjusting your filters or search terms</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    {{ $sales->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closePaymentModal"></div>

            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                    <!-- Modal Header -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <i class="fas fa-cash-register text-red-600 dark:text-red-400 text-sm"></i>
                            </div>
                            Record Payment
                        </h3>
                        <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-5">
                        <!-- Invoice Details Card -->
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/30">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Invoice</span>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $selectedSale->invoice_number }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Customer</span>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $selectedSale->customer->name ?? 'Guest' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-red-200 dark:border-red-800/50 my-2 pt-2 flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Balance Due</span>
                                <span class="text-lg font-bold text-red-600 dark:text-red-400">
                                    Rp. {{ number_format($selectedSale->total_amount - $selectedSale->cash_received, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Amount</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                                    <input type="number" step="0.01" wire:model="paymentAmount" 
                                        class="pl-10 block w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:border-red-500 focus:ring-red-500 dark:text-white shadow-sm">
                                </div>
                                @error('paymentAmount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Date</label>
                                    <input type="date" wire:model="paymentDate" 
                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:border-red-500 focus:ring-red-500 dark:text-white shadow-sm">
                                    @error('paymentDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deposit To</label>
                                    <select wire:model="depositAccountId" 
                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:border-red-500 focus:ring-red-500 dark:text-white shadow-sm">
                                        <option value="">Select Account</option>
                                        @foreach($assetAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('depositAccountId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                <textarea wire:model="notes" rows="2" placeholder="Optional notes..."
                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:border-red-500 focus:ring-red-500 dark:text-white shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-row-reverse gap-3">
                        <button type="button" wire:click="recordPayment" 
                            class="inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            Confirm Payment
                        </button>
                        <button type="button" wire:click="closePaymentModal" 
                            class="inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
