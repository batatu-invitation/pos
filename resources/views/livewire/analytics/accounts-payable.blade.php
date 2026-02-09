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

        return [
            'purchases' => $query->latest()->paginate(10),
            'assetAccounts' => $assetAccounts,
            'totalPayable' => Purchase::whereIn('status', ['pending', 'partial', 'overdue'])
                                    ->sum(\DB::raw('total_amount - paid_amount'))
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

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Accounts Payable</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Manage payment obligations to suppliers or vendors.</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900 p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
            <span class="text-sm font-medium text-red-600 dark:text-red-300">Total Payables</span>
            <div class="text-2xl font-bold text-red-800 dark:text-red-100">
                Rp. {{ number_format($totalPayable, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input wire:model.live="search" type="text" placeholder="Search invoice or supplier..." 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select wire:model.live="statusFilter" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="partial">Partial</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
            <input wire:model.live="startDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
            <input wire:model.live="endDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice / Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance Due</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $purchase->invoice_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->date->format('M d, Y') }}</div>
                                @if($purchase->due_date)
                                    <div class="text-xs {{ \Carbon\Carbon::parse($purchase->due_date)->isPast() ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                                        Due: {{ \Carbon\Carbon::parse($purchase->due_date)->format('M d, Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $purchase->supplier->name ?? 'Unknown Supplier' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->supplier->contact_person ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                Rp. {{ number_format($purchase->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400">
                                Rp. {{ number_format($purchase->paid_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-red-600 dark:text-red-400">
                                Rp. {{ number_format($purchase->total_amount - $purchase->paid_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($purchase->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($purchase->status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($purchase->status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="openPaymentModal('{{ $purchase->id }}')" 
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    Record Payment
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No payables found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $purchases->links() }}
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closePaymentModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Record Payment (Outgoing)
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bill Invoice</label>
                                        <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded-md text-gray-900 dark:text-white">
                                            {{ $selectedPurchase->invoice_number }} ({{ $selectedPurchase->supplier->name ?? 'Unknown' }})
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Balance Due</label>
                                        <div class="mt-1 text-lg font-bold text-red-600 dark:text-red-400">
                                            Rp. {{ number_format($selectedPurchase->total_amount - $selectedPurchase->paid_amount, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Amount</label>
                                        <input type="number" step="0.01" wire:model="paymentAmount" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('paymentAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                                        <input type="date" wire:model="paymentDate" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('paymentDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Withdraw From Account</label>
                                        <select wire:model="withdrawalAccountId" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select Account</option>
                                            @foreach($assetAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('withdrawalAccountId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                        <textarea wire:model="notes" rows="2" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="recordPayment" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm Payment
                        </button>
                        <button type="button" wire:click="closePaymentModal" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
