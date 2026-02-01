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
            'tenant_id' => auth()->id(),
            'user_id' => auth()->user()->id,
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

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800"></h2>
        <div class="flex gap-2">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center shadow-sm">
                    <i class="fas fa-file-export mr-2"></i> Export
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
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
            <button wire:click="create"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Add Color
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        @forelse($colors as $colorItem)
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center group hover:shadow-md transition-shadow relative">

                @if($colorItem->tenant_id === auth()->id())
                <div class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button wire:click="edit('{{ $colorItem->id }}')"
                        class="text-gray-400 hover:text-indigo-600 transition-colors p-1" title="Edit">
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
                        class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Delete">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
                @endif

                <div class="w-16 h-16 {{ $colorItem->class }} rounded-full mb-4 shadow-inner group-hover:scale-110 transition-transform"></div>
                <h3 class="font-medium text-gray-800 text-sm truncate w-full">{{ $colorItem->name }}</h3>
                <p class="text-xs text-gray-400 mt-1">{{ $colorItem->class }}</p>
                 @if(!$colorItem->tenant_id)
                    <span class="text-xs text-gray-400 mt-1 bg-gray-100 px-2 py-0.5 rounded-full">Global</span>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-palette text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No colors</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new color.</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <i class="fas fa-plus -ml-0.5 mr-1.5" aria-hidden="true"></i>
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
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
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
                    <p class="text-sm text-gray-500 mt-1">Enter a valid Tailwind CSS background class.</p>
                    <x-input-error :messages="$errors->get('class')" class="mt-2" />
                </div>

                <div class="mt-4">
                     <p class="text-sm text-gray-700 mb-2">Preview:</p>
                     <div class="w-full h-12 rounded-lg border border-gray-200" :class="$wire.class"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingColorId ? 'Update Color' : 'Create Color' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
