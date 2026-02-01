<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\InventoryValuationExport;

new
#[Layout('components.layouts.app')]
#[Title('Inventory Report - Modern POS')]
class extends Component
{
    public function getProductsProperty()
    {
        return Product::with('category')->get();
    }

    public function getTotalCostValueProperty()
    {
        return $this->products->sum(fn($p) => $p->stock * $p->cost);
    }

    public function getTotalSalesValueProperty()
    {
        return $this->products->sum(fn($p) => $p->stock * $p->price);
    }

    public function exportExcel()
    {
        return Excel::download(new InventoryValuationExport, 'inventory-valuation.xlsx');
    }

    public function exportPdf()
    {
        $products = $this->products;
        $totalCostValue = $this->totalCostValue;
        $totalSalesValue = $this->totalSalesValue;

        $pdf = Pdf::loadView('pdf.inventory-valuation', compact('products', 'totalCostValue', 'totalSalesValue'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inventory-valuation.pdf');
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
                <h1 class="text-2xl font-semibold text-gray-800">Inventory Valuation Report</h1>
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
             <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Inventory Valuation</h2>
                    <p class="text-sm text-gray-500 mt-1">Total Stock Value (Cost): <span class="font-bold text-gray-800">{{ number_format($this->totalCostValue, 2) }}</span></p>
                </div>
                
                <div class="flex gap-2" x-data="{ open: false }">
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

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Items</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $this->products->count() }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Sales Value</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ number_format($this->totalSalesValue, 2) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Cost Value</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ number_format($this->totalCostValue, 2) }}</h3>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Product Name</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4 text-right">Stock</th>
                                <th class="px-6 py-4 text-right">Cost Price</th>
                                <th class="px-6 py-4 text-right">Selling Price</th>
                                <th class="px-6 py-4 text-right">Total Cost Value</th>
                                <th class="px-6 py-4 text-right">Total Sales Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($this->products as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $product->name }}</td>
                                <td class="px-6 py-4">{{ $product->category->name ?? 'Uncategorized' }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ $product->stock <= 10 ? 'text-red-600' : 'text-gray-800' }}">{{ $product->stock }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format($product->cost, 2) }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4 text-right font-medium">{{ number_format($product->stock * $product->cost, 2) }}</td>
                                <td class="px-6 py-4 text-right font-medium">{{ number_format($product->stock * $product->price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right">Totals:</td>
                                <td class="px-6 py-4 text-right">{{ number_format($this->totalCostValue, 2) }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format($this->totalSalesValue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
