<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Transactions - Modern POS')]
class extends Component
{
    use WithPagination;

    public $dateFilter = '';
    public $statusFilter = 'All Statuses';

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
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Transactions</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4">
            <input type="date" wire:model.live="dateFilter" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
            <select wire:model.live="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                <option>All Statuses</option>
                <option>Completed</option>
                <option>Pending</option>
                <option>Refunded</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
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
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $transaction->invoice_number ?? $transaction->id }}</td>
                        <td class="px-6 py-4">{{ $transaction->created_at->format('M d, Y, h:i A') }}</td>
                        <td class="px-6 py-4">
                            @if($transaction->customer)
                                {{ $transaction->customer->name }}
                            @else
                                <span class="text-gray-400">Walk-in Customer</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize">{{ $transaction->payment_method }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">${{ number_format($transaction->total_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @php
                                $statusColor = match($transaction->status) {
                                    'completed' => 'bg-green-100 text-green-800',
                                    'refunded' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">{{ ucfirst($transaction->status) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <button class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
