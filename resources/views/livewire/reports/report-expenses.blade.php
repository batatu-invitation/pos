<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Transaction;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ExpensesExport;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Expenses Report - Modern POS')]
class extends Component
{
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getExpensesProperty()
    {
        return Transaction::where('type', 'expense')
            ->whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate)
            ->latest('date')
            ->get();
    }

    public function exportExcel()
    {
        return Excel::download(new ExpensesExport($this->startDate, $this->endDate), 'expenses.xlsx');
    }

    public function exportPdf()
    {
        $expenses = $this->expenses;
        $totalAmount = $expenses->sum('amount');
        
        $pdf = Pdf::loadView('pdf.expenses', [
            'expenses' => $expenses,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalAmount' => $totalAmount
        ]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'expenses.pdf');
    }
}; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none mr-4 md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-semibold text-gray-800">Expenses Report</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-cash-register mr-2"></i>
                    Open POS
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
             <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Expenses Report</h2>
                
                <div class="flex items-center gap-2">
                    <input wire:model.live="startDate" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <span class="text-gray-500">-</span>
                    <input wire:model.live="endDate" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    
                    <div class="flex gap-2 ml-2" x-data="{ open: false }">
                        <div class="relative">
                            <button @click="open = !open" @click.away="open = false" class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 hover:bg-green-700 transition-colors">
                                <i class="fas fa-file-export"></i> Export
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border py-1" style="display: none;">
                                <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-excel text-green-600 mr-2"></i> Export Excel
                                </button>
                                <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-pdf text-red-600 mr-2"></i> Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->expenses as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $expense->date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $expense->description }}</td>
                                <td class="px-6 py-4">{{ $expense->category }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">Rp. {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $expense->status === 'paid' || $expense->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($expense->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No expenses found for the selected period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>
</div>
