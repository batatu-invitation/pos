<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Purchase;
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
    public $statusFilter = ''; // 'pending', 'partial', 'overdue'

    // Payment Modal
    public $showPaymentModal = false;
    public $selectedPurchase;
    public $paymentAmount = 0;
    public $paymentDate;
    public $withdrawalAccountId; // Credit Account (Cash/Bank)
    public $notes;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->paymentDate = date('Y-m-d');
    }

    public function with()
    {
        $query = Purchase::query()
            ->with(['supplier', 'user'])
            ->whereIn('status', ['pending', 'partial', 'overdue']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('supplier', function($subQ) {
                      $subQ->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [
                $this->startDate, 
                $this->endDate
            ]);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Get Asset accounts for Withdrawal (Cash/Bank)
        $assetAccounts = Account::where('type', 'asset')->orderBy('code')->get();

        // Calculate Stats
        $baseQuery = Purchase::whereIn('status', ['pending', 'partial', 'overdue']);

        $totalPayable = (clone $baseQuery)->sum(\DB::raw('total_amount - paid_amount'));
        
        $overdueAmount = (clone $baseQuery)
                        ->where('due_date', '<', Carbon::today())
                        ->sum(\DB::raw('total_amount - paid_amount'));

        $dueSoonAmount = (clone $baseQuery)
                        ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays(7)])
                        ->sum(\DB::raw('total_amount - paid_amount'));

        return [
            'purchases' => $query->latest()->paginate(10),
            'assetAccounts' => $assetAccounts,
            'totalPayable' => $totalPayable,
            'overdueAmount' => $overdueAmount,
            'dueSoonAmount' => $dueSoonAmount,
        ];
    }

    public function openPaymentModal($purchaseId)
    {
        $this->selectedPurchase = Purchase::find($purchaseId);
        $this->paymentAmount = $this->selectedPurchase->total_amount - $this->selectedPurchase->paid_amount;
        $this->paymentDate = date('Y-m-d');
        $this->notes = 'Payment for Bill ' . $this->selectedPurchase->invoice_number;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->reset(['selectedPurchase', 'paymentAmount', 'withdrawalAccountId', 'notes']);
    }

    public function recordPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'withdrawalAccountId' => 'required|exists:accounts,id',
        ]);

        if (!$this->selectedPurchase) {
            return;
        }

        $remaining = $this->selectedPurchase->total_amount - $this->selectedPurchase->paid_amount;

        if ($this->paymentAmount > $remaining) {
            $this->addError('paymentAmount', 'Amount cannot exceed remaining balance of ' . number_format($remaining, 0, ',', '.'));
            return;
        }

        \DB::transaction(function () {
            // 1. Update Purchase
            $this->selectedPurchase->paid_amount += $this->paymentAmount;
            
            if ($this->selectedPurchase->paid_amount >= $this->selectedPurchase->total_amount) {
                $this->selectedPurchase->status = 'paid';
            } else {
                $this->selectedPurchase->status = 'partial';
            }
            
            $this->selectedPurchase->save();

            // 2. Create Journal Entry
            // Debit: Accounts Payable
            // Credit: Selected Asset Account (Cash/Bank)
            
            // Find AP Account
            $apAccount = Account::where('code', '2000')->first(); // Assuming 2000 is AP
            if (!$apAccount) {
                 // Fallback
                 $apAccount = Account::firstOrCreate(
                    ['code' => '2000'],
                    [
                        'name' => 'Accounts Payable',
                        'type' => 'liability',
                        'subtype' => 'Current Liability',
                        'description' => 'Unpaid supplier bills'
                    ]
                 );
            }

            $journalEntry = JournalEntry::create([
                'date' => $this->paymentDate,
                'reference' => $this->selectedPurchase->invoice_number,
                'description' => $this->notes ?? 'Payment made for Bill ' . $this->selectedPurchase->invoice_number,
                'status' => 'posted',
                'type' => 'general',
            ]);

            // Debit Accounts Payable
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccount->id,
                'debit' => $this->paymentAmount,
                'credit' => 0,
            ]);

            // Credit Cash/Bank
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $this->withdrawalAccountId,
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

<div class="mx-auto p-6 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Accounts Payable</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage supplier bills and track upcoming payments.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm border border-gray-100 dark:border-gray-700">
                {{ \Carbon\Carbon::today()->format('d M Y') }}
            </span>
        </div>
    </div>

    <!-- Summary Cards (Bento Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Payable -->
        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-3xl p-6 text-white shadow-lg shadow-red-200 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Total Payable</span>
                    <i class="fas fa-file-invoice-dollar text-red-100 text-xl"></i>
                </div>
                <div class="text-3xl font-bold mb-1">
                    Rp. {{ number_format($totalPayable, 0, ',', '.') }}
                </div>
                <div class="text-red-100 text-sm opacity-90">
                    All unpaid & partial bills
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
        <!-- Filters Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Filters</h3>
                
                <div class="space-y-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input wire:model.live="search" type="text" placeholder="Invoice or Supplier..." 
                                class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white transition-all text-sm">
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select wire:model.live="statusFilter" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 focus:border-red-500 focus:ring-red-500 dark:text-white text-sm">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="overdue">Overdue</option>
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
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($purchases as $purchase)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-red-600 transition-colors">
                                                {{ $purchase->invoice_number }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $purchase->date->format('M d, Y') }}
                                            </span>
                                            @if($purchase->due_date && \Carbon\Carbon::parse($purchase->due_date)->isPast())
                                                <span class="text-[10px] text-red-500 font-medium mt-1">
                                                    Overdue: {{ \Carbon\Carbon::parse($purchase->due_date)->diffForHumans() }}
                                                </span>
                                            @elseif($purchase->due_date)
                                                <span class="text-[10px] text-gray-400 mt-1">
                                                    Due: {{ \Carbon\Carbon::parse($purchase->due_date)->format('M d') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $purchase->supplier->name ?? 'Unknown' }}</span>
                                            <span class="text-xs text-gray-400">{{ $purchase->supplier->contact_person ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            Rp. {{ number_format($purchase->total_amount, 0, ',', '.') }}
                                        </div>
                                        @if($purchase->paid_amount > 0)
                                            <div class="text-xs text-green-600">
                                                Paid: {{ number_format($purchase->paid_amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                            Rp. {{ number_format($purchase->total_amount - $purchase->paid_amount, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($purchase->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800
                                            @elseif($purchase->status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800
                                            @elseif($purchase->status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 border border-gray-200 dark:border-gray-800 @endif">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button wire:click="openPaymentModal('{{ $purchase->id }}')" 
                                            class="inline-flex items-center justify-center px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-wallet mr-2"></i> Pay
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
                                            <p class="text-gray-500 dark:text-gray-400 text-base font-medium">No payables found</p>
                                            <p class="text-gray-400 text-sm">Try adjusting your filters or search terms</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    {{ $purchases->links() }}
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
                                <i class="fas fa-wallet text-red-600 dark:text-red-400 text-sm"></i>
                            </div>
                            Record Payment (Outgoing)
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
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $selectedPurchase->invoice_number }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Supplier</span>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $selectedPurchase->supplier->name ?? 'Unknown' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-red-200 dark:border-red-800/50 my-2 pt-2 flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Balance Due</span>
                                <span class="text-lg font-bold text-red-600 dark:text-red-400">
                                    Rp. {{ number_format($selectedPurchase->total_amount - $selectedPurchase->paid_amount, 0, ',', '.') }}
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
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Withdraw From</label>
                                    <select wire:model="withdrawalAccountId" 
                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:border-red-500 focus:ring-red-500 dark:text-white shadow-sm">
                                        <option value="">Select Account</option>
                                        @foreach($assetAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('withdrawalAccountId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
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
