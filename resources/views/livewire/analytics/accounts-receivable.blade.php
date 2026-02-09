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

        return [
            'sales' => $query->latest()->paginate(10),
            'assetAccounts' => $assetAccounts,
            'totalReceivable' => Sale::whereIn('payment_status', ['unpaid', 'partial'])
                                    ->where('status', 'completed')
                                    ->sum(\DB::raw('total_amount - cash_received'))
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
            $this->addError('paymentAmount', 'Amount cannot exceed remaining balance of ' . number_format($remaining, 2));
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
            // Debit: Selected Asset Account (Cash/Bank)
            // Credit: Accounts Receivable (Need to find or create this account)
            
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

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Accounts Receivable</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Monitor and manage unpaid customer invoices.</p>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800">
            <span class="text-sm font-medium text-blue-600 dark:text-blue-300">Total Receivables</span>
            <div class="text-2xl font-bold text-blue-800 dark:text-blue-100">
                {{ number_format($totalReceivable, 2) }}
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input wire:model.live="search" type="text" placeholder="Search invoice or customer..." 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select wire:model.live="statusFilter" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Status</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance Due</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $sale->invoice_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->created_at->format('M d, Y') }}</div>
                                @if($sale->due_date)
                                    <div class="text-xs text-red-500">Due: {{ \Carbon\Carbon::parse($sale->due_date)->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                {{ number_format($sale->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400">
                                {{ number_format($sale->cash_received, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-red-600 dark:text-red-400">
                                {{ number_format($sale->total_amount - $sale->cash_received, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($sale->payment_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($sale->payment_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="openPaymentModal('{{ $sale->id }}')" 
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    Record Payment
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No receivables found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $sales->links() }}
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
                                    Record Payment
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice</label>
                                        <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded-md text-gray-900 dark:text-white">
                                            {{ $selectedSale->invoice_number }} ({{ $selectedSale->customer->name ?? 'Guest' }})
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Balance Due</label>
                                        <div class="mt-1 text-lg font-bold text-red-600 dark:text-red-400">
                                            {{ number_format($selectedSale->total_amount - $selectedSale->cash_received, 2) }}
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deposit To Account</label>
                                        <select wire:model="depositAccountId" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select Account</option>
                                            @foreach($assetAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('depositAccountId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Record Payment
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
