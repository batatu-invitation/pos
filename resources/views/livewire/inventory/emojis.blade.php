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
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800"></h2>
        <button wire:click="create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Emoji
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        @forelse($emojis as $emoji)
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center group hover:shadow-md transition-shadow relative">

                @if($emoji->tenant_id === auth()->id())
                <div class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button wire:click="edit('{{ $emoji->id }}')"
                        class="text-gray-400 hover:text-indigo-600 transition-colors p-1" title="Edit">
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
                        class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Delete">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
                @endif

                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">
                    {{ $emoji->icon }}
                </div>
                <h3 class="font-medium text-gray-800 text-sm truncate w-full">{{ $emoji->name ?? 'Unnamed' }}</h3>
                @if(!$emoji->tenant_id)
                    <span class="text-xs text-gray-400 mt-1 bg-gray-100 px-2 py-0.5 rounded-full">Global</span>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-icons text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No emojis</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new emoji.</p>
                <div class="mt-6">
                    <button wire:click="create"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <i class="fas fa-plus -ml-0.5 mr-1.5" aria-hidden="true"></i>
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
            class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">
                {{ $editingEmojiId ? 'Edit Emoji' : 'Create New Emoji' }}
            </h2>

            <div class="space-y-6">
                <div>
                    <x-input-label for="icon" value="Icon (Emoji)" />
                    <x-text-input wire:model="icon" id="icon" class="block mt-1 w-full text-2xl" type="text"
                        placeholder="e.g. 🚀" />
                    <p class="text-sm text-gray-500 mt-1">Paste an emoji here.</p>
                    <x-input-error :messages="$errors->get('icon')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" value="Name (Optional)" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. Rocket" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ $editingEmojiId ? 'Update Emoji' : 'Create Emoji' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
