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

new #[Layout('components.layouts.app')]
#[Title('Produk - Modern POS')]
class extends Component
{
    use WithPagination, WithFileUploads;

    public $name = '';
    public $sku = '';
    public $category_id = '';
    public $price = '';
    public $cost = '';
    public $margin = '';
    public $stock = '';
    public $status = 'Active';
    public $icon = '';
    public $image;
    public $existingImage;
    public $editingProductId = null;
    public $search = '';
    public $categoryFilter = '';

    public function with()
    {
        return [
            'products' => Product::with('category')
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%'))
                ->when($this->categoryFilter, fn($q) => $q->whereHas('category', fn($c) => $c->where('name', $this->categoryFilter)))
                ->latest()
                ->paginate(10),
            'categories' => Category::all(),
            'emojis' => Emoji::all(),
        ];
    }

    public function create()
    {
        $this->reset(['name', 'sku', 'category_id', 'price', 'cost', 'margin', 'stock', 'status', 'icon', 'image', 'existingImage', 'editingProductId']);
        $this->icon = '🍔'; // Default
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
        $this->icon = $product->icon;
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
            'sku' => 'required|unique:products,sku,' . $this->editingProductId,
            'category_id' => 'required|exists:categories,id',
            'price' => 'required',
            'cost' => 'required',
            'stock' => 'required|integer|min:0',
            'status' => 'required',
            'icon' => 'nullable',
            'image' => 'nullable|image|max:2048', // 2MB Max
        ]);

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
            'margin' => $this->margin,
            'stock' => $this->stock,
            'status' => $this->status,
            'icon' => $this->icon,
            'user_id' => auth()->user()->id,
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
        $this->reset(['name', 'sku', 'category_id', 'price', 'cost', 'margin', 'stock', 'status', 'icon', 'image', 'existingImage', 'editingProductId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('notify', __('Product deleted successfully!'));
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Products') }}</h2>
        <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> {{ __('Add Product') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative max-w-sm w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-search text-gray-400"></i>
                </span>
                <input wire:model.live="search" type="text" class="w-full py-2 pl-10 pr-4 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500" placeholder="{{ __('Search products...') }}">
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="categoryFilter" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">{{ __('Product') }}</th>
                        <th class="px-6 py-4">{{ __('Category') }}</th>
                        <th class="px-6 py-4">{{ __('Price') }}</th>
                        <th class="px-6 py-4">{{ __('Stock') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($product->image)
                                    <img class="h-10 w-10 rounded-lg object-cover mr-3" src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}">
                                @else
                                    <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-xl mr-3">{{ $product->icon }}</div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">SKU: {{ $product->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $product->category->name ?? __('Uncategorized') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="{{ $product->stock < 10 ? 'text-red-600 font-bold' : '' }}">
                                {{ $product->stock }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $product->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ __($product->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit('{{ $product->id }}')" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                            <button type="button" x-on:click="$dispatch('swal:confirm', {
                                title: '{{ __('Delete Product?') }}',
                                text: '{{ __('Are you sure you want to delete this product?') }}',
                                icon: 'warning',
                                method: 'delete',
                                params: ['{{ $product->id }}'],
                                componentId: '{{ $this->getId() }}'
                            })" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            {{ __('No products found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Product Modal -->
    <x-modal name="product-modal" focusable>
        <form x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingProductId ? __('Update Product?') : __('Create Product?') }}',
            text: '{{ $editingProductId ? __('Are you sure you want to update this product?') : __('Are you sure you want to create this new product?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingProductId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingProductId ? __('Edit Product') : __('Create New Product') }}
            </h2>

            <div class="space-y-6">
                <!-- Image Upload -->
                <div class="flex flex-col items-center justify-center">
                    <div class="relative group">
                         @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-lg">
                        @elseif ($existingImage)
                            <img src="{{ Storage::url($existingImage) }}" class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-lg">
                        @else
                            <div class="h-24 w-24 rounded-full bg-indigo-50 flex items-center justify-center border-4 border-white shadow-lg text-indigo-300">
                                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <label for="image" class="absolute bottom-0 right-0 bg-indigo-600 rounded-full p-2 text-white hover:bg-indigo-700 cursor-pointer shadow-md transition-all transform hover:scale-110">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <input wire:model="image" id="image" type="file" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div wire:loading wire:target="image" class="text-xs text-indigo-500 mt-2 font-medium">{{ __('Uploading...') }}</div>
                    <x-input-error :messages="$errors->get('image')" class="mt-2 text-center" />
                </div>

                <!-- Name & SKU -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="name" :value="__('Product Name')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. Double Burger') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sku" :value="__('SKU')" />
                        <x-text-input wire:model="sku" id="sku" class="block mt-1 w-full" type="text" placeholder="{{ __('e.g. BUR-001') }}" />
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>
                </div>

                <!-- Category & Icon -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="category_id" :value="__('Category')" />
                        <select wire:model="category_id" id="category_id" class="block w-full px-3 py-2 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="icon" :value="__('Icon (Emoji)')" />
                        <select wire:model="icon" id="icon" class="block w-full px-3 py-2 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('Select Icon') }}</option>
                            @foreach($emojis as $emoji)
                                <option value="{{ $emoji->icon }}">{{ $emoji->icon }} {{ $emoji->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
                    </div>
                </div>

                <!-- Price & Cost -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="price" :value="__('Price (IDR)')" />
                        <x-text-input
                            wire:model="price"
                            x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                            id="price"
                            class="block mt-1 w-full"
                            type="text"
                            placeholder="0"
                        />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="cost" :value="__('Cost (IDR)')" />
                        <x-text-input
                            wire:model="cost"
                            x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                            id="cost"
                            class="block mt-1 w-full"
                            type="text"
                            placeholder="0"
                        />
                        <x-input-error :messages="$errors->get('cost')" class="mt-2" />
                    </div>
                </div>

                <!-- Margin & Stock -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="stock" :value="__('Stock')" />
                        <x-text-input wire:model="stock" id="stock" class="block mt-1 w-full" type="number" placeholder="0" />
                        <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select wire:model="status" id="status" class="block w-full px-3 py-2 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Active">{{ __('Active') }}</option>
                        <option value="Inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingProductId ? __('Update Product') : __('Create Product') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- SweetAlert2 Script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (message) => {
                const msg = Array.isArray(message) ? message[0] : message;
                Swal.fire({
                    title: '{{ __('Success!') }}',
                    text: msg,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            Livewire.on('swal:confirm', (data) => {
                const options = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    title: options.title,
                    text: options.text,
                    icon: options.icon,
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: options.confirmButtonText || '{{ __('Yes, proceed!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (options.componentId) {
                            Livewire.find(options.componentId).call(options.method, ...options.params);
                        } else {
                            Livewire.dispatch(options.method, { id: options.params });
                        }
                    }
                });
            });
        });
    </script>
</div>
