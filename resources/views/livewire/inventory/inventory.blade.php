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
        return Excel::download(new ProductsExport, 'inventory.xlsx');
    }

    public function exportPdf()
    {
        $products = Product::all()->map(fn($product) => $this->sanitizeProduct($product));
        $pdf = Pdf::loadView('pdf.inventory', compact('products'));
        return $pdf->download('inventory.pdf');
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

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Inventory Stock</h2>
        <div class="flex space-x-2">
            <div class="relative">
                <input wire:model.live.debounce.500ms="search" type="text" placeholder="Search products..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-export mr-2"></i> Export
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 py-1" style="display: none;">
                    <button wire:click="exportExcel" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-excel mr-2 text-green-600"></i> Excel
                    </button>
                    <button wire:click="exportPdf" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF
                    </button>
                </div>
            </div>
            <button wire:click="openStockAdjustment" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-sync mr-2"></i> Stock Adjustment
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Current Stock</th>
                        <th class="px-6 py-4">Low Stock Limit</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        @php
                            $limit = 10; // Default limit
                            $isLowStock = $product->stock <= $limit;
                            $stockColor = $isLowStock ? 'text-red-600' : 'text-green-600';
                            $statusColor = $isLowStock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                            $statusText = $isLowStock ? 'Low Stock' : 'In Stock';
                            if ($product->stock == 0) {
                                $statusText = 'Out of Stock';
                                $statusColor = 'bg-gray-100 text-gray-800';
                                $stockColor = 'text-gray-600';
                            }
                        @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800">
                            <div class="flex items-center">
                                <span class="mr-2">{{ $product->icon }}</span>
                                {{ $product->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $product->sku }}</td>
                        <td class="px-6 py-4 {{ $stockColor }} font-bold">{{ $product->stock }}</td>
                        <td class="px-6 py-4">{{ $limit }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">{{ $statusText }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    @if($showStockAdjustmentModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showStockAdjustmentModal', false)"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-sync text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Stock Adjustment
                            </h3>
                            <div class="mt-2 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Product</label>
                                    <select wire:model="selectedProductId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">Select a product</option>
                                        @foreach($allProducts as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) - Current: {{ $p->stock }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedProductId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Adjustment Type</label>
                                    <select wire:model="adjustmentType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="add">Add Stock</option>
                                        <option value="subtract">Subtract Stock</option>
                                        <option value="set">Set Quantity</option>
                                    </select>
                                    @error('adjustmentType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" wire:model="adjustmentQuantity" min="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    @error('adjustmentQuantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="saveStockAdjustment" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" wire:click="$set('showStockAdjustmentModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
