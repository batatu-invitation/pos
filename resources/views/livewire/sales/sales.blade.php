<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;

new #[Layout('components.layouts.app')]
#[Title('Transactions - Modern POS')]
class extends Component
{
    use WithPagination;

    public $dateFilter = '';
    public $statusFilter = 'All Statuses';
    public $selectedSale = null;
    public $showViewModal = false;

    public function with()
    {
        $query = Sale::with(['customer', 'user'])->latest();

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        if ($this->statusFilter !== 'All Statuses') {
            $query->where('status', strtolower($this->statusFilter));
        }

        return [
            'transactions' => $query->paginate(10),
        ];
    }

    public function updatedDateFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function viewSale($id)
    {
        $this->selectedSale = Sale::with(['items', 'customer', 'user'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedSale = null;
    }

    public function resendEmail($id)
    {
        // Placeholder for email sending logic
        // Mail::to($sale->customer->email)->send(new ReceiptMail($sale));
        $this->dispatch('notify', 'Receipt email has been resent to the customer.');
    }

    public function continueSale($id)
    {
        return redirect()->route('pos.visual', ['restore' => $id]);
    }

    public function exportExcel()
    {
        return Excel::download(new SalesExport($this->dateFilter, $this->statusFilter), 'sales-transactions.xlsx');
    }

    public function exportPdf()
    {
        $query = Sale::with(['customer', 'user'])->latest();

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        if ($this->statusFilter && $this->statusFilter !== 'All Statuses') {
            $query->where('status', strtolower($this->statusFilter));
        }

        $sales = $query->get();

        $pdf = Pdf::loadView('pdf.sales-report', compact('sales'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'sales-transactions.pdf');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Transactions</h2>
        <div class="flex items-center space-x-4">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                    <i class="fas fa-download"></i> Export
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5" style="display: none;">
                    <button wire:click="exportExcel" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-excel mr-2 text-green-600"></i> Export Excel
                    </button>
                    <button wire:click="exportPdf" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-pdf mr-2 text-red-600"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4">
            <input type="date" wire:model.live="dateFilter" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
            <select wire:model.live="statusFilter" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                <option>All Statuses</option>
                <option>Completed</option>
                <option>Pending</option>
                <option>Refunded</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">Transaction ID</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Payment Method</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100">{{ $transaction->invoice_number ?? $transaction->id }}</td>
                        <td class="px-6 py-4">{{ $transaction->created_at->format('M d, Y, h:i A') }}</td>
                        <td class="px-6 py-4">
                            @if($transaction->customer)
                                {{ $transaction->customer->name }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500">Walk-in Customer</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize">{{ $transaction->payment_method }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-100">${{ number_format($transaction->total_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @php
                                $statusColor = match($transaction->status) {
                                    'completed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'refunded' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                    'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">{{ ucfirst($transaction->status) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <button wire:click="viewSale('{{ $transaction->id }}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($transaction->status === 'held')
                                <button wire:click="continueSale('{{ $transaction->id }}')" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Continue Sale">
                                    <i class="fas fa-play"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 dark:text-gray-300">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- View Sale Modal -->
    @if($showViewModal && $selectedSale)
    <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeViewModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                            Transaction Details <span class="text-gray-500 dark:text-gray-400 text-sm">#{{ $selectedSale->invoice_number }}</span>
                        </h3>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</p>
                            <p class="text-gray-900 dark:text-gray-100">{{ $selectedSale->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ match($selectedSale->status) {
                                    'completed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'refunded' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                    'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                } }}">
                                {{ ucfirst($selectedSale->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</p>
                            <p class="text-gray-900 dark:text-gray-100">{{ $selectedSale->customer ? $selectedSale->customer->name : 'Walk-in Customer' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</p>
                            <p class="text-gray-900 dark:text-gray-100 capitalize">{{ $selectedSale->payment_method }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 py-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                    <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                                    <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                    <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($selectedSale->items as $item)
                                <tr>
                                    <td class="py-2 text-sm text-gray-900 dark:text-gray-100">{{ $item->product_name }}</td>
                                    <td class="py-2 text-right text-sm text-gray-900 dark:text-gray-100">{{ $item->quantity }}</td>
                                    <td class="py-2 text-right text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($item->price, 2) }}</td>
                                    <td class="py-2 text-right text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($selectedSale->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Tax</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($selectedSale->tax, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Discount</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">- Rp {{ number_format($selectedSale->discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold">
                            <span class="text-gray-900 dark:text-gray-100">Total</span>
                            <span class="text-indigo-600 dark:text-indigo-400">Rp {{ number_format($selectedSale->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button onclick="printReceipt('{{ route('pos.receipt.print', $selectedSale->id) }}')"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        <i class="fas fa-print mr-2"></i> Print Receipt
                    </button>
                    <button type="button" wire:click="resendEmail('{{ $selectedSale->id }}')"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        <i class="fas fa-envelope mr-2"></i> Resend Email
                    </button>
                    <button type="button" wire:click="closeViewModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        function printReceipt(url) {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
            // Clean up after 1 minute to allow time for loading and printing
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 60000);
        }
    </script>
</div>
