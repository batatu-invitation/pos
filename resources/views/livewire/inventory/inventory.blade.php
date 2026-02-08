<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Inventory - Modern POS')]
class extends Component
{
    use WithPagination;

    public $search = '';

    // Stock Adjustment Modal
    public $showStockAdjustmentModal = false;
    public $selectedProductId = '';
    public $adjustmentType = 'add'; // add, subtract, set
    public $adjustmentQuantity = 0;

    private function sanitize($value)
    {
        if (is_string($value)) {
            // mb_scrub is the most robust way to fix malformed UTF-8 sequences
            if (function_exists('mb_scrub')) {
                return mb_scrub($value, 'UTF-8');
            }
            // Fallback for older PHP versions or missing extensions
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        return $value;
    }

    private function sanitizeProduct($product)
    {
        $attributes = $product->getAttributes();
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $product->$key = $this->sanitize($value);
            }
        }
        return $product;
    }

    public function with()
    {
        return [
            'products' => Product::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->paginate(10)
                ->through(fn($product) => $this->sanitizeProduct($product)),
            'allProducts' => Product::orderBy('name')->get()->map(fn($product) => $this->sanitizeProduct($product))
        ];
    }

    public function exportExcel()
    {
        return Excel::download(new ProductsExport($this->search), 'inventory.xlsx');
    }

    public function exportPdf()
    {
        $products = Product::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get()
            ->map(fn($product) => $this->sanitizeProduct($product));

        $pdf = Pdf::loadView('pdf.inventory', compact('products'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inventory.pdf');
    }

    public function openStockAdjustment()
    {
        $this->reset(['selectedProductId', 'adjustmentType', 'adjustmentQuantity']);
        $this->showStockAdjustmentModal = true;
    }

    public function saveStockAdjustment()
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'adjustmentQuantity' => 'required|integer|min:0',
            'adjustmentType' => 'required|in:add,subtract,set',
        ]);

        $product = Product::find($this->selectedProductId);

        if ($this->adjustmentType === 'add') {
            $product->increment('stock', $this->adjustmentQuantity);
        } elseif ($this->adjustmentType === 'subtract') {
            if ($product->stock < $this->adjustmentQuantity) {
                $this->addError('adjustmentQuantity', 'Cannot subtract more than current stock.');
                return;
            }
            $product->decrement('stock', $this->adjustmentQuantity);
        } elseif ($this->adjustmentType === 'set') {
            $product->update(['stock' => $this->adjustmentQuantity]);
        }

        $this->showStockAdjustmentModal = false;
        // You might want to add a flash message here
        session()->flash('message', 'Stock updated successfully.');
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 space-y-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
            Inventory Stock
        </h2>
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 dark:text-gray-500 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>
                <input wire:model.live.debounce.500ms="search" 
                       type="text" 
                       placeholder="Search products..." 
                       class="pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-full sm:w-64 text-sm shadow-sm transition-all dark:text-white dark:placeholder-gray-500">
            </div>
            
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm font-medium flex items-center gap-2">
                    <i class="fas fa-file-export text-gray-400 dark:text-gray-500"></i> 
                    <span>Export</span>
                    <i class="fas fa-chevron-down text-xs ml-1 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" 
                     @click.away="open = false" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 z-50 py-1 overflow-hidden" 
                     style="display: none;">
                    <button wire:click="exportExcel" class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i class="fas fa-file-excel mr-3 text-green-500"></i> Excel Export
                    </button>
                    <button wire:click="exportPdf" class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i class="fas fa-file-pdf mr-3 text-red-500"></i> PDF Export
                    </button>
                </div>
            </div>

            <button wire:click="openStockAdjustment" 
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all shadow-md hover:shadow-lg font-medium flex items-center gap-2">
                <i class="fas fa-sync-alt"></i>
                <span>Stock Adjustment</span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm" role="alert">
            <i class="fas fa-check-circle"></i>
            <span class="font-medium">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider">
                    <tr>
                        <th class="px-6 py-5">Product</th>
                        <th class="px-6 py-5">SKU</th>
                        <th class="px-6 py-5">Current Stock</th>
                        <th class="px-6 py-5">Low Stock Limit</th>
                        <th class="px-6 py-5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($products as $product)
                        @php
                            $limit = 10; // Default limit
                            $isLowStock = $product->stock <= $limit;
                            $stockColor = $isLowStock ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                            $statusColor = $isLowStock ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                            $statusText = $isLowStock ? 'Low Stock' : 'In Stock';
                            if ($product->stock == 0) {
                                $statusText = 'Out of Stock';
                                $statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                $stockColor = 'text-gray-600 dark:text-gray-400';
                            }
                        @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ $product->icon }}</span>
                                <span class="font-semibold">{{ $product->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-6 py-4 {{ $stockColor }} font-bold text-base">{{ $product->stock }}</td>
                        <td class="px-6 py-4">{{ $limit }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $statusColor }} border border-transparent shadow-sm">
                                {{ $statusText }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-box-open text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p class="text-lg font-medium">No products found</p>
                                <p class="text-sm opacity-75">Try adjusting your search criteria</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    @if($showStockAdjustmentModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" 
                 aria-hidden="true" 
                 wire:click="$set('showStockAdjustmentModal', false)"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 sm:mx-0 sm:h-12 sm:w-12">
                            <i class="fas fa-sync text-indigo-600 dark:text-indigo-400 text-lg"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-xl leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                                Stock Adjustment
                            </h3>
                            <div class="mt-4 space-y-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Product</label>
                                    <select wire:model="selectedProductId" class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl transition-colors">
                                        <option value="">Select a product</option>
                                        @foreach($allProducts as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) - Current: {{ $p->stock }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedProductId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Adjustment Type</label>
                                    <select wire:model="adjustmentType" class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl transition-colors">
                                        <option value="add">Add Stock (+)</option>
                                        <option value="subtract">Subtract Stock (-)</option>
                                        <option value="set">Set Quantity (=)</option>
                                    </select>
                                    @error('adjustmentType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                    <input type="number" wire:model="adjustmentQuantity" min="0" class="block w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl shadow-sm py-2.5 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                    @error('adjustmentQuantity') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                    <button type="button" wire:click="saveStockAdjustment" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm transition-all hover:shadow-lg">
                        Save Changes
                    </button>
                    <button type="button" wire:click="$set('showStockAdjustmentModal', false)" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
