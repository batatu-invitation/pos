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

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Journal Entries</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Chronological record of daily transactions.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input wire:model.live="search" type="text" placeholder="Search reference or description..." 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
            <input wire:model.live="startDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
            <input wire:model.live="endDate" type="date" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
    </div>

    <!-- List -->
    <div class="space-y-4">
        @forelse($entries as $entry)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">{{ $entry->reference }}</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $entry->date->format('M d, Y') }}</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ ucfirst($entry->status) }}
                    </div>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $entry->description }}</p>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Debit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($entry->items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            {{ $item->account->code }} - {{ $item->account->name }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                            {{ $item->debit > 0 ? number_format($item->debit, 2) : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                            {{ $item->credit > 0 ? number_format($item->credit, 2) : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-900/50 font-bold">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right">Total</td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                        {{ number_format($entry->items->sum('debit'), 2) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-white">
                                        {{ number_format($entry->items->sum('credit'), 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
                No journal entries found for the selected period.
            </div>
        @endforelse

        <div class="pt-4">
            {{ $entries->links() }}
        </div>
    </div>
</div>
