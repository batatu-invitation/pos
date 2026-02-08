<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Product;
use App\Models\Category;
use App\Models\Emoji;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Buglinjo\LaravelWebp\Webp;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\ApplicationSetting;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

new #[Layout('components.layouts.app')]
    #[Title('Products - Modern POS')]
    class extends Component {
    use WithPagination, WithFileUploads;

    public $name = '';
    public $sku = '';
    public $category_id = '';
    public $price = '';
    public $cost = '';
    public $margin = '';
    public $stock = '';
    public $status = 'Active';
    public $icon_id = '';
    public $image;
    public $existingImage;
    public $editingProductId = null;
    public $search = '';
    public $categoryFilter = '';

    public function with()
    {
        $user = auth()->user();
        $baseQuery = Product::query();

        if ($user->hasRole('Super Admin')) {
            // No restriction
        } elseif (!$user->created_by) {
            $baseQuery->where('user_id', $user->id);
        } elseif ($user->hasRole(['Manager', 'Admin', 'Inventory Manager'])) {
            $baseQuery->where('user_id', $user->created_by);
        } else {
            abort(403);
        }

        // Clone query for stats to avoid modifying the main query instance used for pagination later? 
        // Actually simpler to just apply scopes to new instances or reuse logic.
        
        $totalProducts = (clone $baseQuery)->count();
        $lowStock = (clone $baseQuery)->where('stock', '<', 10)->where('stock', '>', 0)->count();
        $outOfStock = (clone $baseQuery)->where('stock', '<=', 0)->count();
        $activeProducts = (clone $baseQuery)->where('status', 'Active')->count();

        return [
            'products' => $baseQuery->with(['category', 'emoji'])
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                }))
                ->when($this->categoryFilter, fn($q) => $q->whereHas('category', fn($c) => $c->where('name', $this->categoryFilter)))
                ->latest()
                ->paginate(9),
            'categories' => Category::all(),
            'emojis' => Emoji::all(),
            'totalProducts' => $totalProducts,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'activeProducts' => $activeProducts,
        ];
    }

    public function create()
    {
        $this->reset(['name', 'sku', 'category_id', 'price', 'cost', 'margin', 'stock', 'status', 'icon_id', 'image', 'existingImage', 'editingProductId']);
        $this->status = 'Active';
        $this->dispatch('open-modal', 'product-modal');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->category_id = $product->category_id;
        $this->price = number_format($product->price, 0, '', '.');
        $this->cost = number_format($product->cost, 0, '', '.');
        $this->margin = $product->margin;
        $this->stock = $product->stock;
        $this->status = $product->status;
        $this->icon_id = $product->icon_id;
        $this->existingImage = $product->image;
        $this->reset('image');

        $this->dispatch('open-modal', 'product-modal');
    }

    public function updatedPrice($value)
    {
        $this->calculateMargin();
    }

    public function updatedCost($value)
    {
        $this->calculateMargin();
    }

    public function calculateMargin()
    {
        $price = (float) str_replace('.', '', $this->price);
        $cost = (float) str_replace('.', '', $this->cost);

        if ($price > 0) {
            // Margin formula: ((Price - Cost) / Price) * 100
            $this->margin = number_format((($price - $cost) / $price) * 100, 2);
        } else {
            $this->margin = 0;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:2',
            'sku' => 'required|unique:products,sku' . ($this->editingProductId ? ',' . $this->editingProductId : ''),
            'category_id' => 'required|exists:categories,id',
            'price' => 'required',
            'cost' => 'required',
            'stock' => 'required|integer|min:0',
            'status' => 'required',
            'icon_id' => 'nullable|exists:emojis,id',
            'image' => 'nullable|image|max:2048', // 2MB Max
        ]);
        $user = auth()->user();

        $price = str_replace('.', '', $this->price);
        $cost = str_replace('.', '', $this->cost);

        // Ensure margin is calculated before saving
        $this->calculateMargin();

        $data = [
            'name' => $this->name,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'price' => $price,
            'cost' => $cost,
            'margin' => $this->margin,
            'stock' => $this->stock,
            'status' => $this->status,
            'icon_id' => $this->icon_id,
            'user_id' => $user->created_by ? $user->created_by : $user->id,
            'input_id' => $user->id,
        ];

        // Handle Image Upload with WebP Conversion
        if ($this->image) {
            $imageName = time() . '_' . uniqid() . '.webp';
            $path = storage_path('app/public/products');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            Webp::make($this->image)->save($path . '/' . $imageName);
            $data['image'] = 'products/' . $imageName;

            // Delete old image if editing
            if ($this->editingProductId && $this->existingImage) {
                if (Storage::disk('public')->exists($this->existingImage)) {
                    Storage::disk('public')->delete($this->existingImage);
                }
            }
        }

        if ($this->editingProductId) {
            Product::findOrFail($this->editingProductId)->update($data);
            $message = __('Product updated successfully!');
        } else {
            Product::create($data);
            $message = __('Product created successfully!');
        }

        $this->dispatch('close-modal', 'product-modal');
        $this->reset(['name', 'sku', 'category_id', 'price', 'cost', 'margin', 'stock', 'status', 'icon_id', 'image', 'existingImage', 'editingProductId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('notify', __('Product deleted successfully!'));
    }

    public function exportExcel()
    {
        return Excel::download(new ProductsExport($this->search, $this->categoryFilter), 'products.xlsx');
    }

    public function exportPdf()
    {
        $products = Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('sku', 'like', '%' . $this->search . '%'))
            ->when($this->categoryFilter, fn($q) => $q->whereHas('category', fn($c) => $c->where('name', $this->categoryFilter)))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('pdf.products', ['products' => $products]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'products.pdf');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">{{ __('Products') }}</h2>
            <p class="text-gray-500 mt-2 text-sm">{{ __('Manage your inventory, prices, and stock levels.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-file-export mr-2 text-gray-400"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs text-gray-400"></i>
                </button>
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-50 py-2 border border-gray-100" 
                     style="display: none;">
                    <button wire:click="exportExcel" @click="open = false" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-file-excel mr-2 text-green-500"></i> {{ __('Export Excel') }}
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-file-pdf mr-2 text-red-500"></i> {{ __('Export PDF') }}
                    </button>
                </div>
            </div>
            <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Product') }}
            </button>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 rounded-3xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-indigo-100 font-medium mb-1">{{ __('Total Products') }}</p>
                <h3 class="text-3xl font-bold">{{ $totalProducts }}</h3>
                <p class="text-indigo-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-warehouse mr-1"></i> {{ __('In inventory') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-indigo-400/30 text-5xl">
                <i class="fas fa-box-open"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-3xl shadow-lg shadow-green-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-green-100 font-medium mb-1">{{ __('Active Items') }}</p>
                <h3 class="text-3xl font-bold">{{ $activeProducts }}</h3>
                <p class="text-green-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-check-circle mr-1"></i> {{ __('Ready for sale') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-green-400/30 text-5xl">
                <i class="fas fa-tags"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-6 rounded-3xl shadow-lg shadow-yellow-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-yellow-50 font-medium mb-1">{{ __('Low Stock') }}</p>
                <h3 class="text-3xl font-bold">{{ $lowStock }}</h3>
                <p class="text-yellow-50 text-sm mt-2 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ __('Reorder needed') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-yellow-200/30 text-5xl">
                <i class="fas fa-battery-quarter"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-rose-600 p-6 rounded-3xl shadow-lg shadow-red-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-red-100 font-medium mb-1">{{ __('Out of Stock') }}</p>
                <h3 class="text-3xl font-bold">{{ $outOfStock }}</h3>
                <p class="text-red-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-times-circle mr-1"></i> {{ __('Restock now') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-red-400/30 text-5xl">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/50 to-purple-50/50 opacity-50"></div>
        <div class="relative z-10 w-full md:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 sm:text-sm shadow-sm group-hover:shadow-md" placeholder="{{ __('Search products by name or SKU...') }}">
        </div>
        <div class="relative z-10 flex items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64 group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-filter text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>
                <select wire:model.live="categoryFilter" class="block w-full pl-10 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm rounded-2xl bg-white text-gray-700 shadow-sm group-hover:shadow-md appearance-none transition-all">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
        @forelse($products as $product)
            <div class="group bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col relative">
                <!-- Status & Stock Badges -->
                <div class="absolute top-4 left-4 z-10 flex gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $product->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} border {{ $product->status === 'Active' ? 'border-green-200' : 'border-gray-200' }} shadow-sm">
                        {{ $product->status }}
                    </span>
                    @if($product->stock <= 0)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 shadow-sm">
                            {{ __('Out of Stock') }}
                        </span>
                    @elseif($product->stock < 10)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 shadow-sm">
                            {{ __('Low Stock') }}
                        </span>
                    @endif
                </div>

                <!-- Product Image/Icon -->
                <div class="h-48 w-full bg-gray-50 flex items-center justify-center relative overflow-hidden group-hover:bg-indigo-50/30 transition-colors">
                    @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="text-6xl transform group-hover:scale-110 transition-transform duration-300 filter drop-shadow-sm">
                            {{ $product->emoji->icon ?? '📦' }}
                        </div>
                    @endif
                    
                    <!-- Quick Actions Overlay -->
                    <div class="absolute bottom-4 right-4 flex space-x-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                         <button wire:click="edit('{{ $product->id }}')" class="p-2 bg-white text-indigo-600 rounded-xl shadow-md hover:bg-indigo-50 transition-colors" title="{{ __('Edit') }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" x-on:click="$dispatch('swal:confirm', {
                                    title: '{{ __('Delete Product?') }}',
                                    text: '{{ __('Are you sure you want to delete this product?') }}',
                                    icon: 'warning',
                                    method: 'delete',
                                    params: ['{{ $product->id }}'],
                                    componentId: '{{ $this->getId() }}'
                                })" class="p-2 bg-white text-red-500 rounded-xl shadow-md hover:bg-red-50 transition-colors" title="{{ __('Delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">
                            {{ $product->category->name ?? __('Uncategorized') }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono">{{ $product->sku }}</span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-1 line-clamp-1" title="{{ $product->name }}">{{ $product->name }}</h3>
                    
                    <div class="flex items-end justify-between mt-auto pt-4 border-t border-gray-50">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">{{ __('Price') }}</p>
                            <p class="text-xl font-bold text-gray-800">
                                <span class="text-sm font-normal text-gray-500 align-top mr-0.5">Rp</span>{{ number_format($product->price, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 mb-1">{{ __('Stock') }}</p>
                            <p class="text-sm font-bold {{ $product->stock < 10 ? 'text-red-500' : 'text-gray-700' }}">
                                {{ $product->stock }} <span class="font-normal text-gray-400 text-xs">{{ __('units') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-1 md:col-span-2 lg:col-span-3">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i class="fas fa-box-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('No products found') }}</h3>
                    <p class="text-gray-500 mb-6">{{ __('Try adjusting your search or filters, or add a new product.') }}</p>
                    <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i> {{ __('Add Product') }}
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>

    <!-- Product Modal -->
    <x-modal name="product-modal" focusable>
        <div class="bg-white rounded-3xl overflow-hidden">
             <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 class="text-xl font-bold text-gray-800">
                    {{ $editingProductId ? __('Edit Product') : __('Create New Product') }}
                </h2>
                <button x-on:click="$dispatch('close-modal', 'product-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form x-on:submit.prevent="$dispatch('swal:confirm', {
                title: '{{ $editingProductId ? __('Update Product?') : __('Create Product?') }}',
                text: '{{ $editingProductId ? __('Are you sure you want to update this product?') : __('Are you sure you want to create this new product?') }}',
                icon: 'question',
                confirmButtonText: '{{ $editingProductId ? __('Yes, update it!') : __('Yes, create it!') }}',
                method: 'save',
                params: [],
                componentId: '{{ $this->getId() }}'
            })" class="p-6">

                <div class="space-y-6">
                    <!-- Image Upload -->
                    <div class="flex flex-col items-center justify-center pb-4 border-b border-gray-100">
                        <div class="relative group">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}"
                                    class="h-32 w-32 rounded-2xl object-cover border-4 border-white shadow-lg">
                            @elseif ($existingImage)
                                <img src="{{ Storage::url($existingImage) }}"
                                    class="h-32 w-32 rounded-2xl object-cover border-4 border-white shadow-lg">
                            @else
                                <div
                                    class="h-32 w-32 rounded-2xl bg-indigo-50 flex items-center justify-center border-4 border-white shadow-lg text-indigo-300">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            @endif

                            <label for="image"
                                class="absolute -bottom-2 -right-2 bg-indigo-600 rounded-xl p-3 text-white hover:bg-indigo-700 cursor-pointer shadow-lg shadow-indigo-200 transition-all transform hover:scale-105 active:scale-95">
                                <i class="fas fa-camera"></i>
                                <input wire:model="image" id="image" type="file" class="hidden" accept="image/*">
                            </label>
                        </div>
                        <div wire:loading wire:target="image" class="text-xs text-indigo-500 mt-3 font-medium flex items-center">
                            <i class="fas fa-spinner fa-spin mr-1"></i> {{ __('Uploading...') }}
                        </div>
                        <x-input-error :messages="$errors->get('image')" class="mt-2 text-center" />
                        <p class="text-xs text-gray-400 mt-2">{{ __('Allowed: jpg, png, webp. Max: 2MB') }}</p>
                    </div>

                    <!-- Name & SKU -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" :value="__('Product Name')" class="text-gray-700 font-medium mb-1" />
                            <x-text-input wire:model="name" id="name" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                                placeholder="{{ __('e.g. Double Burger') }}" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="sku" :value="__('SKU')" class="text-gray-700 font-medium mb-1" />
                            <x-text-input wire:model="sku" id="sku" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                                placeholder="{{ __('e.g. BUR-001') }}" />
                            <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Category & Icon -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="category_id" :value="__('Category')" class="text-gray-700 font-medium mb-1" />
                            <div class="relative">
                                <select wire:model="category_id" id="category_id"
                                    class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700">
                                    <option value="">{{ __('Select Category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="icon_id" :value="__('Icon (Emoji)')" class="text-gray-700 font-medium mb-1" />
                            <div class="relative">
                                <select wire:model="icon_id" id="icon_id"
                                    class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700">
                                    <option value="">{{ __('Select Icon') }}</option>
                                    @foreach($emojis as $emoji)
                                        <option value="{{ $emoji->id }}">{{ $emoji->icon }} {{ $emoji->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('icon_id')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Pricing Section -->
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                        <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-tag mr-2 text-indigo-500"></i> {{ __('Pricing & Stock') }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="cost" :value="__('Cost Price')" class="text-gray-700 font-medium mb-1" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <x-text-input wire:model.live="cost" id="cost" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                                        placeholder="0" />
                                </div>
                                <x-input-error :messages="$errors->get('cost')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="price" :value="__('Selling Price')" class="text-gray-700 font-medium mb-1" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <x-text-input wire:model.live="price" id="price" class="block w-full pl-10 rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                                        placeholder="0" />
                                </div>
                                <x-input-error :messages="$errors->get('price')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="margin" :value="__('Margin (%)')" class="text-gray-700 font-medium mb-1" />
                                <x-text-input wire:model="margin" id="margin" class="block w-full rounded-xl border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed py-2.5" type="text" readonly />
                            </div>
                        </div>
                    </div>

                    <!-- Stock & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="stock" :value="__('Stock Quantity')" class="text-gray-700 font-medium mb-1" />
                            <x-text-input wire:model="stock" id="stock" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="number"
                                placeholder="0" />
                            <x-input-error :messages="$errors->get('stock')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" class="text-gray-700 font-medium mb-1" />
                            <div class="relative">
                                <select wire:model="status" id="status"
                                    class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700">
                                    <option value="Active">{{ __('Active') }}</option>
                                    <option value="Inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('status')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'product-modal')"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 font-medium text-sm">
                        {{ $editingProductId ? __('Update Product') : __('Create Product') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>