<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('API Keys - Modern POS')]
class extends Component
{
    public $name = '';
    public $plainTextToken = null;

    public function with()
    {
        return [
            'tokens' => auth()->user()->tokens()->orderBy('created_at', 'desc')->get()
        ];
    }

    public function createToken()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = auth()->user()->createToken($this->name);
        $this->plainTextToken = $token->plainTextToken;
        $this->name = '';

        $this->dispatch('open-modal', 'token-modal');
        $this->dispatch('notify', 'API Token created successfully.');
    }

    public function deleteToken($id)
    {
        auth()->user()->tokens()->where('id', $id)->delete();
        $this->dispatch('notify', 'API Token deleted successfully.');
    }

    public function closeTokenModal()
    {
        $this->plainTextToken = null;
        $this->dispatch('close-modal', 'token-modal');
    }
}; ?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Developer API Keys</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('settings.general') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">General</a>
            <a href="{{ route('settings.receipt') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Receipt</a>
            <a href="{{ route('settings.payment') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Payment Methods</a>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Notifications</a>
            <a href="{{ route('settings.integrations') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Integrations</a>
            <button class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium text-sm whitespace-nowrap">API Keys</button>
            <a href="{{ route('settings.backup') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Backups</a>
            <a href="{{ route('settings.taxes') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">Taxes</a>
            <a href="{{ route('settings.profile') }}" wire:navigate class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap">My Profile</a>
        </div>

        <div class="p-6">
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Manage Access Tokens</h3>
                <p class="text-gray-500 text-sm">Create and manage API tokens for accessing the API.</p>
            </div>

            <!-- Create Form -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 mb-8">
                <h4 class="text-base font-semibold text-gray-900 mb-4">Generate New Token</h4>
                <form wire:submit="createToken" class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <x-text-input wire:model="name" type="text" placeholder="Token Name (e.g. Mobile App)" class="w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-sm whitespace-nowrap">
                        Generate Token
                    </button>
                </form>
            </div>

            <!-- Token List -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Last Used</th>
                            <th class="px-6 py-3">Created</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $token)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $token->name }}</td>
                                <td class="px-6 py-4">
                                    {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                                </td>
                                <td class="px-6 py-4">{{ $token->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('swal:confirm', {
                                            title: 'Delete Token?',
                                            text: 'Are you sure you want to delete this token? This action cannot be undone.',
                                            icon: 'warning',
                                            method: 'deleteToken',
                                            params: ['{{ $token->id }}'],
                                            componentId: '{{ $this->getId() }}'
                                        })"
                                        class="text-red-600 hover:text-red-900 font-medium">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-key text-gray-300 text-4xl mb-3"></i>
                                        <p>No API keys found. Generate one to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Token Modal -->
    <x-modal name="token-modal" :show="$plainTextToken" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                API Token Generated
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Please copy your new API token. For your security, it won't be shown again.
            </p>

            <div class="relative group">
                <div class="bg-gray-100 p-4 rounded-lg break-all font-mono text-sm mb-6 select-all border border-gray-200">
                    {{ $plainTextToken }}
                </div>
            </div>

            <div class="flex justify-end">
                <x-secondary-button wire:click="closeTokenModal">
                    Close
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
