<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Stok Inventaris - Modern POS')]
class extends Component
{
    use WithPagination;

    public $search = '';

    // Stock Adjustment Modal
    // public $showStockAdjustmentModal = false; // Replaced with x-modal
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
            'stockItems' => Product::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%'))
                ->orderByRaw('CASE WHEN stock <= 10 THEN 0 ELSE 1 END')
                ->orderBy('name')
                ->paginate(10)
                ->through(fn($product) => $this->sanitizeProduct($product)),
            'allProducts' => Product::orderBy('name')->get()->map(fn($product) => $this->sanitizeProduct($product))
        ];
    }

    public function exportExcel()
    {
        return Excel::download(new StockExport($this->search), 'stock_inventory.xlsx');
    }

    public function exportPdf()
    {
        $products = Product::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%'))
            ->orderByRaw('CASE WHEN stock <= 10 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get()
            ->map(fn($product) => $this->sanitizeProduct($product));

        $pdf = Pdf::loadView('pdf.inventory', compact('products'));
        $time = now()->format('H:i:s-d-m-Y');
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "stock-inventory-{$time}.pdf");
    }

    public function openStockAdjustment()
    {
        $this->reset(['selectedProductId', 'adjustmentType', 'adjustmentQuantity']);
        $this->dispatch('open-modal', 'stock-adjustment-modal');
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
                $this->addError('adjustmentQuantity', __('Cannot subtract more than current stock.'));
                return;
            }
            $product->decrement('stock', $this->adjustmentQuantity);
        } elseif ($this->adjustmentType === 'set') {
            $product->update(['stock' => $this->adjustmentQuantity]);
        }

        $this->dispatch('close-modal', 'stock-adjustment-modal');
        $this->dispatch('notify', $message = 'Stock updated successfully.');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6 transition-colors duration-300">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-gray-800 to-gray-600 dark:from-white dark:to-gray-300">{{ __('Inventory Stock') }}</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">{{ __('Monitor and adjust your product stock levels.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="relative w-full md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search products...') }}" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 sm:text-sm">
            </div>
            
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition ease-in-out duration-150">
                    <i class="fas fa-file-export mr-2 text-gray-400 dark:text-gray-500"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs text-gray-400 dark:text-gray-500"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg z-50 py-2 border border-gray-100 dark:border-gray-700" style="display: none;">
                    <button @click="
                        Swal.fire({
                            title: 'Export Excel?',
                            text: 'Do you want to export the inventory to Excel?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, export!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.exportExcel();
                            }
                        })
                    " class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        <i class="fas fa-file-excel mr-2 text-green-500"></i> Excel
                    </button>
                    <button @click="
                        Swal.fire({
                            title: '{{ __('Export PDF?') }}',
                            text: '{{ __('Do you want to export the inventory to PDF?') }}',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '{{ __('Yes, export!') }}',
                            cancelButtonText: '{{ __('Cancel') }}'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.exportPdf();
                            }
                        })
                    " class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        <i class="fas fa-file-pdf mr-2 text-red-500"></i> PDF
                    </button>
                </div>
            </div>
            
            <button wire:click="openStockAdjustment" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="fas fa-sync mr-2"></i> {{ __('Adjust Stock') }}
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl relative mb-6 shadow-sm" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/50 dark:bg-gray-700/30 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Current Stock</th>
                        <th class="px-6 py-4">Low Stock Limit</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($stockItems as $item)
                        @php
                            $limit = 10; // Default limit or from DB if available
                            $isLowStock = $item->stock <= $limit;
                            $stockColor = $isLowStock ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                            $statusColor = $isLowStock ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800/30';
                            $statusText = $isLowStock ? 'Low Stock' : 'In Stock';
                             if ($item->stock == 0) {
                                $statusText = 'Out of Stock';
                                $statusColor = 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600';
                                $stockColor = 'text-gray-600 dark:text-gray-400';
                            }
                        @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">
                            <div class="flex items-center">
                                <span class="mr-3 text-2xl">{{ $item->icon }}</span>
                                {{ $item->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $item->sku }}</td>
                        <td class="px-6 py-4 {{ $stockColor }} font-bold text-base">{{ $item->stock }}</td>
                        <td class="px-6 py-4">{{ $limit }}</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">{{ $statusText }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-box-open text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>{{ __('No products found matching your search.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800">
            {{ $stockItems->links() }}
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <x-modal name="stock-adjustment-modal" focusable>
        <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ __('Stock Adjustment') }}
                </h2>
                <button x-on:click="$dispatch('close-modal', 'stock-adjustment-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6">
                <div class="space-y-6">
                    <div>
                        <x-input-label for="selectedProductId" :value="__('Product')" class="text-gray-700 dark:text-gray-300 font-medium mb-1" />
                        <select wire:model="selectedProductId" id="selectedProductId" class="block w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl">
                            <option value="">{{ __('Select a product') }}</option>
                            @foreach($allProducts as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) - Current: {{ $p->stock }}</option>
                            @endforeach
                        </select>
                        @error('selectedProductId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <x-input-label for="adjustmentType" :value="__('Adjustment Type')" class="text-gray-700 dark:text-gray-300 font-medium mb-1" />
                        <select wire:model="adjustmentType" id="adjustmentType" class="block w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl">
                            <option value="add">{{ __('Add Stock') }}</option>
                            <option value="subtract">{{ __('Subtract Stock') }}</option>
                            <option value="set">{{ __('Set Quantity') }}</option>
                        </select>
                        @error('adjustmentType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <x-input-label for="adjustmentQuantity" :value="__('Quantity')" class="text-gray-700 dark:text-gray-300 font-medium mb-1" />
                        <input type="number" wire:model="adjustmentQuantity" id="adjustmentQuantity" min="0" class="block w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white rounded-xl shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('adjustmentQuantity') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'stock-adjustment-modal')"
                        class="px-5 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors font-medium text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" @click="
                        Swal.fire({
                            title: 'Confirm Adjustment?',
                            text: 'Are you sure you want to update the stock?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, save it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.saveStockAdjustment();
                            }
                        })
                    " class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30 font-medium text-sm">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
