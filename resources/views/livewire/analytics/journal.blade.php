<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\JournalEntry;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $startDate;
    public $endDate;
    public $search = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function with()
    {
        $query = JournalEntry::with(['items.account', 'user'])
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'entries' => $query->latest('date')->latest('created_at')->paginate(10),
        ];
    }
};
?>

<div class="p-6 space-y-6 transition-colors duration-300">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Journal Entries') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Chronological record of daily transactions.') }}
                </p>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Search') }}</label>
                    <input wire:model.live="search" type="text" placeholder="{{ __('Search reference or description...') }}" 
                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Start Date') }}</label>
                    <input wire:model.live="startDate" type="date" 
                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('End Date') }}</label>
                    <input wire:model.live="endDate" type="date" 
                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
            </div>
        </div>

    <!-- List -->
    <div class="space-y-6">
        @forelse($entries as $entry)
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-shadow hover:shadow-md">
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">{{ $entry->reference }}</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $entry->date->format('M d, Y') }}</span>
                    </div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                        {{ ucfirst($entry->status) }}
                    </div>
                </div>
                <div class="px-6 py-6">
                    <p class="text-gray-700 dark:text-gray-300 mb-6 font-medium">{{ $entry->description }}</p>
                    
                    <div class="overflow-x-auto custom-scrollbar rounded-xl border border-gray-100 dark:border-gray-700">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                                    <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">{{ __('Account') }}</th>
                                    <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Debit') }}</th>
                                    <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase text-right">{{ __('Credit') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($entry->items as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $item->account->code }} - {{ $item->account->name }}
                                        </td>
                                        <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                            {{ $item->debit > 0 ? 'Rp. ' . number_format($item->debit, 0, ',', '.') : '' }}
                                        </td>
                                        <td class="p-4 text-sm text-gray-700 dark:text-gray-300 text-right">
                                            {{ $item->credit > 0 ? 'Rp. ' . number_format($item->credit, 0, ',', '.') : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50/50 dark:bg-gray-700/50 font-bold text-gray-900 dark:text-white border-t border-gray-100 dark:border-gray-700">
                                <tr>
                                    <td class="p-4 text-right">{{ __('Total') }}</td>
                                    <td class="p-4 text-right text-indigo-600 dark:text-indigo-400">
                                        Rp. {{ number_format($entry->items->sum('debit'), 0, ',', '.') }}
                                    </td>
                                    <td class="p-4 text-right text-indigo-600 dark:text-indigo-400">
                                        Rp. {{ number_format($entry->items->sum('credit'), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('No Journal Entries') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ __('No journal entries found for the selected period.') }}</p>
            </div>
        @endforelse

        <div class="pt-4">
            {{ $entries->links() }}
        </div>
    </div>
</div>
