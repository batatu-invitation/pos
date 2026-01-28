<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')]
#[Title('Transactions - Modern POS')]
class extends Component
{
    public $transactions = [
        ['id' => '#TRX-001', 'date' => 'Oct 24, 2023, 10:30 AM', 'customer' => 'Walk-in Customer', 'method' => 'Cash', 'total' => 39.60, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-002', 'date' => 'Oct 24, 2023, 11:15 AM', 'customer' => 'John Doe', 'method' => 'Credit Card', 'total' => 120.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-003', 'date' => 'Oct 24, 2023, 12:00 PM', 'customer' => 'Sarah Smith', 'method' => 'E-Wallet', 'total' => 15.50, 'status' => 'Refunded', 'status_color' => 'bg-red-100 text-red-800'],
        ['id' => '#TRX-004', 'date' => 'Oct 24, 2023, 01:20 PM', 'customer' => 'Mike Johnson', 'method' => 'Cash', 'total' => 45.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-005', 'date' => 'Oct 24, 2023, 02:45 PM', 'customer' => 'Emily Brown', 'method' => 'Credit Card', 'total' => 89.90, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-006', 'date' => 'Oct 24, 2023, 03:10 PM', 'customer' => 'Walk-in Customer', 'method' => 'E-Wallet', 'total' => 22.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-007', 'date' => 'Oct 24, 2023, 04:00 PM', 'customer' => 'David Wilson', 'method' => 'Cash', 'total' => 12.50, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-008', 'date' => 'Oct 24, 2023, 05:15 PM', 'customer' => 'Walk-in Customer', 'method' => 'Credit Card', 'total' => 150.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-009', 'date' => 'Oct 24, 2023, 06:30 PM', 'customer' => 'Jessica Lee', 'method' => 'E-Wallet', 'total' => 28.75, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-010', 'date' => 'Oct 24, 2023, 07:45 PM', 'customer' => 'Robert Taylor', 'method' => 'Cash', 'total' => 55.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
        ['id' => '#TRX-011', 'date' => 'Oct 24, 2023, 08:20 PM', 'customer' => 'Walk-in Customer', 'method' => 'Credit Card', 'total' => 18.00, 'status' => 'Completed', 'status_color' => 'bg-green-100 text-green-800'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Transactions</h2>
        <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-download mr-2"></i> Export
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4">
            <input type="date" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
            <select class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
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
                    @foreach($transactions as $transaction)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $transaction['id'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['date'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                        <td class="px-6 py-4">{{ $transaction['method'] }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">${{ number_format($transaction['total'], 2) }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full {{ $transaction['status_color'] }}">{{ $transaction['status'] }}</span></td>
                        <td class="px-6 py-4">
                            <button class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
