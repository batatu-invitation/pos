<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Category;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['header' => 'Categories'])] #[Title('Categories - Modern POS')] class extends Component {
    use WithPagination;

    public $name = '';
    public $icon = '🍔';
    public $color = 'bg-orange-100';
    public $description = '';
    public $editingCategoryId = null;

    // Predefined colors for UI consistency
    public $colors = [
        'bg-orange-100' => 'Orange',
        'bg-blue-100' => 'Blue',
        'bg-pink-100' => 'Pink',
        'bg-purple-100' => 'Purple',
        'bg-yellow-100' => 'Yellow',
        'bg-amber-100' => 'Amber',
        'bg-red-100' => 'Red',
        'bg-green-100' => 'Green',
        'bg-rose-100' => 'Rose',
        'bg-cyan-100' => 'Cyan',
        'bg-sky-100' => 'Sky',
        'bg-indigo-100' => 'Indigo',
        'bg-teal-100' => 'Teal',
    ];

    // Icon mapping
    public $icons = [
        'foods' => '🍔',
        'drinks' => '🥤',
        'desserts' => '🍰',
        'electronics' => '🔌',
        'snacks' => '🍿',
        'beverages' => '☕',
        'fruits' => '🍎',
        'vegetables' => '🥦',
        'meats' => '🥩',
        'seafoods' => '🦐',
        'bakery' => '🥐',
        'frozen' => '🍦',
        'households' => '🏠',
        'stationery' => '✏️',
        'others' => '📝',
    ];

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
        return [
            'categories' => Category::select('id', 'name', 'icon', 'color', 'description')->orderBy('id', 'desc')->paginate(12), // Changed to 9 for better grid layout (3x3)
        ];
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

        $data = [
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
        ];

        if ($this->editingCategoryId) {
            Category::findOrFail($this->editingCategoryId)->update($data);
            $message = 'Category updated successfully!';
        } else {
            Category::create($data);
            $message = 'Category created successfully!';
        }

        $this->dispatch('close-modal', 'category-modal');
        $this->reset(['name', 'icon', 'color', 'description', 'editingCategoryId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        Category::findOrFail($id)->delete();
        $this->dispatch('notify', 'Category deleted successfully!');
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <!-- Load jQuery (Select2 not strictly needed for categories but keeping consistency if needed later) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800"></h2>
        <button wire:click="create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Category
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($categories as $category)
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center group hover:shadow-md transition-shadow relative">
                <!-- Actions (Visible on Hover) -->
                <div class="absolute top-3 right-3 flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button wire:click="edit('{{ $category->id }}')"
                        class="text-gray-400 hover:text-indigo-600 transition-colors" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                        x-on:click="$dispatch('swal:confirm', {
                        title: 'Delete Category?',
                        text: 'Are you sure you want to delete this category?',
                        icon: 'warning',
                        method: 'delete',
                        params: ['{{ $category->id }}'],
                        componentId: '{{ $this->getId() }}'
                    })"
                        class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div
                    class="w-16 h-16 {{ $category->color }} rounded-full flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                    {{ $category->icon }}
                </div>
                <h3 class="font-bold text-gray-800 text-lg">{{ $category->name }}</h3>
                <p class="text-gray-500 text-sm mt-1">0 Items</p>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-folder-open text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No categories</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new category.</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <i class="fas fa-plus -ml-0.5 mr-1.5" aria-hidden="true"></i>
                        Add Category
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
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingCategoryId ? 'Update Category?' : 'Create Category?' }}',
            text: '{{ $editingCategoryId ? 'Are you sure you want to update this category?' : 'Are you sure you want to create this new category?' }}',
            icon: 'question',
            confirmButtonText: '{{ $editingCategoryId ? 'Yes, update it!' : 'Yes, create it!' }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingCategoryId ? 'Edit Category' : 'Create New Category' }}
            </h2>

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. Food" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Icon -->
                    <div>
                        <x-input-label for="icon" value="Icon (Emoji)" />
                        <select wire:model="icon" id="icon"
                            class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($icons as $key => $value)
                                <option value="{{ $value }}">{{ ucfirst($key) }} {{ $value }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
                    </div>

                    <!-- Color -->
                    <div>
                        <x-input-label for="color" value="Color Theme" />
                        <select wire:model="color" id="color"
                            class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($colors as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('color')" class="mt-2" />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <x-input-label for="description" value="Description (Optional)" />
                    <textarea wire:model="description" id="description"
                        class="block mt-1 p-4 w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows="3">
</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingCategoryId ? 'Update Category' : 'Create Category' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- SweetAlert2 Script (Global Listener) -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (message) => {
                const msg = Array.isArray(message) ? message[0] : message;
                Swal.fire({
                    title: 'Success!',
                    text: msg,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            Livewire.on('swal:confirm', (data) => {
                // Handle both array (from backend dispatch) and object (from frontend dispatch)
                const options = Array.isArray(data) ? data[0] : data;

                Swal.fire({
                    title: options.title,
                    text: options.text,
                    icon: options.icon,
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: options.confirmButtonText || 'Yes, proceed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (options.componentId) {
                            Livewire.find(options.componentId).call(options.method, ...options
                                .params);
                        } else {
                            Livewire.dispatch(options.method, {
                                id: options.params
                            });
                        }
                    }
                });
            });
        });
    </script>
</div>
