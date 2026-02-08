<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Emoji;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['header' => 'Emojis'])] #[Title('Emojis - Modern POS')] class extends Component {
    use WithPagination;

    public $name = '';
    public $icon = '';
    public $editingEmojiId = null;

    protected function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'icon' => 'required|string|max:255',
        ];
    }

    public function with()
    {
        return [
            'emojis' => Emoji::where('tenant_id', auth()->id())
                ->orWhereNull('tenant_id')
                ->orderBy('created_at', 'desc')
                ->paginate(24),
        ];
    }

    public function create()
    {
        $this->reset(['name', 'icon', 'editingEmojiId']);
        $this->dispatch('open-modal', 'emoji-modal');
    }

    public function edit($id)
    {
        $emoji = Emoji::findOrFail($id);
        // Check ownership if needed, but for now we allow editing if it's visible?
        // Usually global items shouldn't be edited by users.
        if (!$emoji->tenant_id && $emoji->tenant_id !== auth()->id()) {
             // If it's global (null) and user tries to edit, maybe we should prevent it or clone it?
             // For now, let's assume users can only edit their own.
             // But for the sake of the exercise "based on the user who inputs", I will enforce ownership for update/delete.
        }

        $this->editingEmojiId = $emoji->id;
        $this->name = $emoji->name;
        $this->icon = $emoji->icon;

        $this->dispatch('open-modal', 'emoji-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'icon' => $this->icon,
            'tenant_id' => auth()->id(),
            'user_id' => auth()->user()->id,
        ];

        if ($this->editingEmojiId) {
            $emoji = Emoji::findOrFail($this->editingEmojiId);
            if ($emoji->tenant_id !== auth()->id() && $emoji->tenant_id !== null) {
                // If it belongs to another user (shouldn't happen with query)
                abort(403);
            }
            // If it is global (null), user creates a copy or we forbid?
            // Let's assume user edits their own.
             if ($emoji->tenant_id === null) {
                 // Creating a new user-specific version of a global emoji? Or just updating global?
                 // Let's create a new one if it was global, effectively "overriding" or "forking"?
                 // Or just fail.
                 // For simplicity, let's allow updating if we are admin, or if we want to allow users to add their own.
                 // The prompt says "based on the user who inputs".
                 // So I will assume this CRUD is for managing USER's emojis.
                 // If editing a global one, we should probably duplicate it as user's?
                 // Let's just update for now, assuming the user has rights.
                 // Actually, best practice: Only allow editing own records.
                 if ($emoji->tenant_id === null) {
                     $this->dispatch('notify-error', 'You cannot edit global emojis.');
                     return;
                 }
            }

            $emoji->update($data);
            $message = 'Emoji updated successfully!';
        } else {
            Emoji::create($data);
            $message = 'Emoji created successfully!';
        }

        $this->dispatch('close-modal', 'emoji-modal');
        $this->reset(['name', 'icon', 'editingEmojiId']);
        $this->dispatch('notify', $message);
    }

    #[On('delete')]
    public function delete($id)
    {
        $emoji = Emoji::findOrFail($id);
        if ($emoji->tenant_id !== auth()->id()) {
             $this->dispatch('notify-error', 'You cannot delete global emojis.');
             return;
        }
        $emoji->delete();
        $this->dispatch('notify', 'Emoji deleted successfully!');
    }

    public function exportExcel()
    {
        return Excel::download(new EmojisExport, 'emojis.xlsx');
    }

    public function exportPdf()
    {
        $emojis = Emoji::where('tenant_id', auth()->id())
                ->orWhereNull('tenant_id')
                ->orderBy('created_at', 'desc')
                ->get();

        $pdf = Pdf::loadView('pdf.emojis', compact('emojis'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'emojis.pdf');
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 space-y-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">Emojis</h2>
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
                <span>Add Emoji</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        @forelse($emojis as $emoji)
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center group hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                
                @if($emoji->tenant_id === auth()->id())
                <div class="absolute top-3 right-3 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full p-1 shadow-sm">
                    <button wire:click="edit('{{ $emoji->id }}')"
                        class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-1.5" title="Edit">
                        <i class="fas fa-edit text-xs"></i>
                    </button>
                    <button type="button"
                        x-on:click="$dispatch('swal:confirm', {
                        title: 'Delete Emoji?',
                        text: 'Are you sure you want to delete this emoji?',
                        icon: 'warning',
                        method: 'delete',
                        params: ['{{ $emoji->id }}'],
                        componentId: '{{ $this->getId() }}'
                    })"
                        class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors p-1.5" title="Delete">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
                @endif

                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform duration-300 filter drop-shadow-sm">
                    {{ $emoji->icon }}
                </div>
                
                <h3 class="font-bold text-gray-800 dark:text-white text-sm truncate w-full mb-1">{{ $emoji->name ?? 'Unnamed' }}</h3>
                
                @if(!$emoji->tenant_id)
                    <span class="absolute top-3 left-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">Global</span>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4">
                    <i class="fas fa-icons text-5xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">No emojis found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new emoji.</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                        <i class="fas fa-plus -ml-0.5 mr-2" aria-hidden="true"></i>
                        Add Emoji
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $emojis->links() }}
    </div>

    <x-modal name="emoji-modal" focusable>
        <form
            x-on:submit.prevent="$dispatch('swal:confirm', {
            title: '{{ $editingEmojiId ? 'Update Emoji?' : 'Create Emoji?' }}',
            text: '{{ $editingEmojiId ? 'Are you sure you want to update this emoji?' : 'Are you sure you want to create this new emoji?' }}',
            icon: 'question',
            confirmButtonText: '{{ $editingEmojiId ? 'Yes, update it!' : 'Yes, create it!' }}',
            method: 'save',
            params: [],
            componentId: '{{ $this->getId() }}'
        })"
            class="p-6 dark:bg-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                {{ $editingEmojiId ? 'Edit Emoji' : 'Create New Emoji' }}
            </h2>

            <div class="space-y-6">
                <div>
                    <x-input-label for="icon" value="Icon (Emoji)" />
                    <x-text-input wire:model="icon" id="icon" class="block mt-1 w-full text-4xl text-center py-4" type="text"
                        placeholder="e.g. 🚀" />
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 text-center">Paste an emoji above.</p>
                    <x-input-error :messages="$errors->get('icon')" class="mt-2 text-center" />
                </div>

                <div>
                    <x-input-label for="name" value="Name (Optional)" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. Rocket" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button>
                    {{ $editingEmojiId ? 'Update Emoji' : 'Create Emoji' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
