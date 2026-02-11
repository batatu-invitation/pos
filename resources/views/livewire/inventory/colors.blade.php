<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Color;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ColorsExport;
use Barryvdh\DomPDF\Facade\Pdf;

new #[Layout('components.layouts.app', ['header' => 'Colors'])] #[Title('Colors - Modern POS')] class extends Component {
    use WithPagination;

    public $name = '';
    public $class = '';
    public $editingColorId = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:255',
        ];
    }

    public function with()
    {
        return [
            'colors' => Color::where('tenant_id', auth()->id())
                ->orWhereNull('tenant_id')
                ->orderBy('created_at', 'desc')
                ->paginate(24),
        ];
    }

    public function create()
    {
        $this->reset(['name', 'class', 'editingColorId']);
        $this->dispatch('open-modal', 'color-modal');
    }

    public function edit($id)
    {
        $color = Color::findOrFail($id);

        $this->editingColorId = $color->id;
        $this->name = $color->name;
        $this->class = $color->class;

        $this->dispatch('open-modal', 'color-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'class' => $this->class,
        ];

        if ($this->editingColorId) {
            $color = Color::findOrFail($this->editingColorId);
            if ($color->tenant_id === null) {
                 $this->dispatch('notify-error', 'You cannot edit global colors.');
                 return;
            }
             if ($color->tenant_id !== auth()->id()) {
                abort(403);
            }

            $color->update($data);
            $message = 'Color updated successfully!';
        } else {
            Color::create($data);
            $message = 'Color created successfully!';
        }

        $this->dispatch('close-modal', 'color-modal');
        $this->reset(['name', 'class', 'editingColorId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        $color = Color::findOrFail($id);
        if ($color->tenant_id !== auth()->id()) {
             $this->dispatch('notify-error', 'You cannot delete global colors.');
             return;
        }
        $color->delete();
        $this->dispatch('notify', 'Color deleted successfully!');
    }

    public function exportExcel()
    {
        return Excel::download(new ColorsExport, 'colors.xlsx');
    }

    public function exportPdf()
    {
        $colors = Color::where('tenant_id', auth()->id())
                ->orWhereNull('tenant_id')
                ->orderBy('created_at', 'desc')
                ->get();

        $pdf = Pdf::loadView('pdf.colors', compact('colors'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'colors.pdf');
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 space-y-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Colors</h2>
        <div class="flex flex-wrap items-center gap-3">
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
                    <button wire:click="exportExcel" @click="open = false" class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i class="fas fa-file-excel text-green-500 mr-3"></i> Excel Export
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i class="fas fa-file-pdf text-red-500 mr-3"></i> PDF Export
                    </button>
                </div>
            </div>
            <button wire:click="create"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all shadow-md hover:shadow-lg font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Add Color</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        @forelse($colors as $colorItem)
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                
                @if($colorItem->tenant_id === auth()->id())
                <div class="absolute top-3 right-3 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full p-1 shadow-sm">
                    <button wire:click="edit('{{ $colorItem->id }}')"
                        class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-1.5" title="Edit">
                        <i class="fas fa-edit text-xs"></i>
                    </button>
                    <button type="button"
                        x-on:click="$dispatch('swal:confirm', {
                        title: 'Delete Color?',
                        text: 'Are you sure you want to delete this color?',
                        icon: 'warning',
                        method: 'delete',
                        params: ['{{ $colorItem->id }}'],
                        componentId: '{{ $this->getId() }}'
                    })"
                        class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors p-1.5" title="Delete">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
                @endif

                <div class="w-16 h-16 {{ $colorItem->class }} rounded-full mb-4 shadow-inner ring-4 ring-gray-50 dark:ring-gray-700 group-hover:scale-110 transition-transform duration-300"></div>
                
                <h3 class="font-bold text-gray-800 dark:text-white text-sm truncate w-full mb-1">{{ $colorItem->name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md">{{ $colorItem->class }}</p>
                
                 @if(!$colorItem->tenant_id)
                    <span class="absolute top-3 left-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">Global</span>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4">
                    <i class="fas fa-palette text-5xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">No colors found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new color.</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                        <i class="fas fa-plus -ml-0.5 mr-2" aria-hidden="true"></i>
                        Add Color
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $colors->links() }}
    </div>

    <x-modal name="color-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingColorId ? 'Update Color?' : 'Create Color?' }}',
            text: '{{ $editingColorId ? 'Are you sure you want to update this color?' : 'Are you sure you want to create this new color?' }}',
            icon: 'question',
            confirmButtonText: '{{ $editingColorId ? 'Yes, update it!' : 'Yes, create it!' }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6 dark:bg-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                {{ $editingColorId ? 'Edit Color' : 'Create New Color' }}
            </h2>

            <div class="space-y-6">
                <div>
                    <x-input-label for="name" value="Color Name" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. Sunset Orange" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="class" value="Tailwind Class" />
                    <x-text-input wire:model="class" id="class" class="block mt-1 w-full" type="text"
                        placeholder="e.g. bg-orange-500" />
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter a valid Tailwind CSS background class.</p>
                    <x-input-error :messages="$errors->get('class')" class="mt-2" />
                </div>

                <div class="mt-4">
                     <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</p>
                     <div class="w-full h-16 rounded-xl border border-gray-200 dark:border-gray-600 shadow-inner flex items-center justify-center" :class="$wire.class">
                        <span class="text-xs text-white/50 font-mono" x-text="$wire.class"></span>
                     </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button>
                    {{ $editingColorId ? 'Update Color' : 'Create Color' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
