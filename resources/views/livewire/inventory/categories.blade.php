<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Category;
use App\Models\Emoji;
use App\Models\Color;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CategoriesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApplicationSetting;

new #[Layout('components.layouts.app', ['header' => 'Categories'])] #[Title('Kategori - Modern POS')] class extends Component {
    use WithPagination;

    public $name = '';
    public $icon = '🍔';
    public $color = 'bg-orange-100';
    public $description = '';
    public $editingCategoryId = null;

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
            'emojis' => Emoji::all(),
            'colors' => Color::all(),
        ];
    }

    public function exportExcel()
    {
        return Excel::download(new CategoriesExport, 'categories.xlsx');
    }

    public function exportPdf()
    {
        $categories = Category::latest()->get();
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

        $hasSettings = ApplicationSetting::where('user_id', $user->created_by)->exists();

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
    <!-- Load jQuery (Select2 not strictly needed for categories but keeping consistency if needed later) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800"></h2>
        <div class="flex space-x-2">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-export mr-2"></i> {{ __('Export') }}
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 py-1" style="display: none;">
                    <button @click="
                        Swal.fire({
                            title: 'Export Excel?',
                            text: 'Do you want to export the categories to Excel?',
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
                    " class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-excel mr-2 text-green-600"></i> Excel
                    </button>
                    <button @click="
                        Swal.fire({
                            title: '{{ __('Export PDF?') }}',
                            text: '{{ __('Do you want to export the categories to PDF?') }}',
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
                    " class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF
                    </button>
                </div>
            </div>
            <button wire:click="create"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> {{ __('Add Category') }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($categories as $category)
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center group hover:shadow-md transition-shadow relative">
                <!-- Actions (Visible on Hover) -->
                <div class="absolute top-3 right-3 flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button wire:click="edit('{{ $category->id }}')"
                        class="text-gray-400 hover:text-indigo-600 transition-colors" title="{{ __('Edit') }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                        x-on:click="$dispatch('swal:confirm', {
                        title: '{{ __('Delete Category?') }}',
                        text: '{{ __('Are you sure you want to delete this category?') }}',
                        icon: 'warning',
                        method: 'delete',
                        params: ['{{ $category->id }}'],
                        componentId: '{{ $this->getId() }}'
                    })"
                        class="text-gray-400 hover:text-red-500 transition-colors" title="{{ __('Delete') }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div
                    class="w-16 h-16 {{ $category->color }} rounded-full flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                    {{ $category->icon }}
                </div>
                <h3 class="font-bold text-gray-800 text-lg">{{ $category->name }}</h3>
                <p class="text-gray-500 text-sm mt-1">{{ __('0 Items') }}</p>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-folder-open text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ __('No categories') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating a new category.') }}</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <i class="fas fa-plus -ml-0.5 mr-1.5" aria-hidden="true"></i>
                        {{ __('Add Category') }}
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
            title: '{{ $editingCategoryId ? __('Update Category?') : __('Create Category?') }}',
            text: '{{ $editingCategoryId ? __('Are you sure you want to update this category?') : __('Are you sure you want to create this new category?') }}',
            icon: 'question',
            confirmButtonText: '{{ $editingCategoryId ? __('Yes, update it!') : __('Yes, create it!') }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingCategoryId ? __('Edit Category') : __('Create New Category') }}
            </h2>

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" value="{{ __('Name') }}" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="{{ __('e.g. Food') }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Icon -->
                    <div>
                        <x-input-label for="icon" value="{{ __('Icon (Emoji)') }}" />
                        <select wire:model="icon" id="icon"
                            class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($emojis as $emoji)
                                <option value="{{ $emoji->icon }}">{{ ucfirst($emoji->name) }} {{ $emoji->icon }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
                    </div>

                    <!-- Color -->
                    <div>
                        <x-input-label for="color" value="{{ __('Color Theme') }}" />
                        <select wire:model="color" id="color"
                            class="block w-full px-4 py-4 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($colors as $colorItem)
                                <option value="{{ $colorItem->class }}">{{ $colorItem->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('color')" class="mt-2" />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <x-input-label for="description" value="{{ __('Description (Optional)') }}" />
                    <textarea wire:model="description" id="description"
                        class="block mt-1 p-4 w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows="3">
</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingCategoryId ? __('Update Category') : __('Create Category') }}
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
                    title: '{{ __('Success!') }}',
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
                    confirmButtonText: options.confirmButtonText || '{{ __('Yes, proceed!') }}'
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
