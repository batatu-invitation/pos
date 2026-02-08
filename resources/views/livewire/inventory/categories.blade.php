<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Category;
use App\Models\Emoji;
use App\Models\Color;
use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CategoriesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApplicationSetting;

new #[Layout('components.layouts.app')]
    #[Title('Categories - Modern POS')]
    class extends Component {
    use WithPagination;

    public $name = '';
    public $icon = '🍔';
    public $color = 'bg-orange-100';
    public $description = '';
    public $editingCategoryId = null;
    public $search = '';

    protected function rules()
    {
        return [
            'name' => 'required|min:2',
            'icon' => 'required',
            'color' => 'required',
            'description' => 'nullable|string',
        ];
    }

    public function with()
    {
        $user = auth()->user();
        
        $totalCategories = Category::count();
        $totalProducts = Product::count(); // Global count or scoped? Let's use global for category context usually.
        
        $categoriesQuery = Category::withCount('products')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        return [
            'categories' => $categoriesQuery->orderBy('id', 'desc')->paginate(12),
            'emojis' => Emoji::all(),
            'colors' => Color::all(),
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
        ];
    }

    public function exportExcel()
    {
        return Excel::download(new CategoriesExport, 'categories.xlsx');
    }

    public function exportPdf()
    {
        $categories = Category::withCount('products')->latest()->get();
        $pdf = Pdf::loadView('pdf.categories', compact('categories'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'categories.pdf');
    }

    public function create()
    {
        $this->reset(['name', 'icon', 'color', 'description', 'editingCategoryId']);
        $this->icon = '🍔'; // Default icon
        $this->color = 'bg-orange-100'; // Default color
        $this->dispatch('open-modal', 'category-modal');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->description = $category->description;

        $this->dispatch('open-modal', 'category-modal');
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();

        $data = [
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
            'user_id' => $user->created_by ? $user->created_by : $user->id,
            'input_id' => $user->id,
        ];

        if ($this->editingCategoryId) {
            Category::findOrFail($this->editingCategoryId)->update($data);
            $message = __('Category updated successfully!');
        } else {
            Category::create($data);
            $message = __('Category created successfully!');
        }

        $this->dispatch('close-modal', 'category-modal');
        $this->reset(['name', 'icon', 'color', 'description', 'editingCategoryId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Category::findOrFail($id)->delete();
        $this->dispatch('notify', __('Category deleted successfully!'));
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">{{ __('Categories') }}</h2>
            <p class="text-gray-500 mt-2 text-sm">{{ __('Organize your products into categories for easier management.') }}</p>
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
                <i class="fas fa-plus mr-2"></i> {{ __('Add Category') }}
            </button>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow duration-300">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('Total Categories') }}</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $totalCategories }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                <i class="fas fa-th-large text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow duration-300">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('Total Products Linked') }}</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $totalProducts }}</h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i class="fas fa-box-open text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 sm:text-sm" placeholder="{{ __('Search categories...') }}">
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($categories as $category)
            <div class="group bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-lg transition-all duration-300 relative overflow-hidden">
                
                <!-- Background Decoration -->
                <div class="absolute top-0 left-0 w-full h-1/2 {{ $category->color }} opacity-20 rounded-b-3xl transform -translate-y-full group-hover:translate-y-0 transition-transform duration-500 ease-out"></div>

                <!-- Actions (Visible on Hover) -->
                <div class="absolute top-4 right-4 flex space-x-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 z-10">
                    <button wire:click="edit('{{ $category->id }}')" class="p-2 bg-white text-indigo-600 rounded-xl shadow-md hover:bg-indigo-50 transition-colors" title="{{ __('Edit') }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" x-on:click="$dispatch('swal:confirm', {
                                    title: '{{ __('Delete Category?') }}',
                                    text: '{{ __('Are you sure you want to delete this category?') }}',
                                    icon: 'warning',
                                    method: 'delete',
                                    params: ['{{ $category->id }}'],
                                    componentId: '{{ $this->getId() }}'
                                })" class="p-2 bg-white text-red-500 rounded-xl shadow-md hover:bg-red-50 transition-colors" title="{{ __('Delete') }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="w-20 h-20 {{ $category->color }} rounded-full flex items-center justify-center text-4xl mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-sm relative z-10">
                    {{ $category->icon }}
                </div>
                
                <h3 class="font-bold text-gray-800 text-lg mb-1 relative z-10">{{ $category->name }}</h3>
                
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-100 mt-2 relative z-10">
                    <i class="fas fa-box mr-1.5 text-gray-400"></i> {{ $category->products_count }} {{ Str::plural('Product', $category->products_count) }}
                </div>

                @if($category->description)
                    <p class="text-xs text-gray-400 mt-3 line-clamp-2 relative z-10">{{ $category->description }}</p>
                @endif
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('No categories found') }}</h3>
                    <p class="text-gray-500 mb-6">{{ __('Get started by creating a new category.') }}</p>
                    <button wire:click="create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i> {{ __('Add Category') }}
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $categories->links() }}
    </div>

    <!-- Category Modal -->
    <x-modal name="category-modal" focusable>
        <div class="bg-white rounded-3xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 class="text-xl font-bold text-gray-800">
                    {{ $editingCategoryId ? __('Edit Category') : __('Create New Category') }}
                </h2>
                <button x-on:click="$dispatch('close-modal', 'category-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form x-on:submit.prevent="$dispatch('swal:confirm', {
                title: '{{ $editingCategoryId ? __('Update Category?') : __('Create Category?') }}',
                text: '{{ $editingCategoryId ? __('Are you sure you want to update this category?') : __('Are you sure you want to create this new category?') }}',
                icon: 'question',
                confirmButtonText: '{{ $editingCategoryId ? __('Yes, update it!') : __('Yes, create it!') }}',
                method: 'save',
                params: [],
                componentId: '{{ $this->getId() }}'
            })" class="p-6">

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Category Name')" class="text-gray-700 font-medium mb-1" />
                        <x-text-input wire:model="name" id="name" class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5" type="text"
                            placeholder="{{ __('e.g. Food & Beverages') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Icon -->
                        <div>
                            <x-input-label for="icon" :value="__('Icon (Emoji)')" class="text-gray-700 font-medium mb-1" />
                            <div class="relative">
                                <select wire:model="icon" id="icon"
                                    class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700 font-emoji">
                                    @foreach ($emojis as $emoji)
                                        <option value="{{ $emoji->icon }}">{{ $emoji->icon }} {{ ucfirst($emoji->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('icon')" class="mt-1" />
                        </div>

                        <!-- Color -->
                        <div>
                            <x-input-label for="color" :value="__('Color Theme')" class="text-gray-700 font-medium mb-1" />
                            <div class="relative">
                                <select wire:model="color" id="color"
                                    class="block w-full px-3 py-2.5 border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-gray-700">
                                    @foreach ($colors as $colorItem)
                                        <option value="{{ $colorItem->class }}">{{ $colorItem->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('color')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <x-input-label for="description" :value="__('Description (Optional)')" class="text-gray-700 font-medium mb-1" />
                        <textarea wire:model="description" id="description"
                            class="block w-full rounded-xl border-gray-200 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 shadow-sm"
                            rows="3" placeholder="{{ __('Add a short description...') }}"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'category-modal')"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 font-medium text-sm">
                        {{ $editingCategoryId ? __('Update Category') : __('Create Category') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>