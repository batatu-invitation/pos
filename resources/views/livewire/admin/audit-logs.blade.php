<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Spatie\Activitylog\Models\Activity;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app', ['header' => 'Audit Logs'])]
#[Title('Audit Logs - Modern POS')]
class extends Component
{
    use WithPagination;

    public $search = '';
    public $actionFilter = '';
    public $selectedLog;

    public function viewDetails($id)
    {
        $this->selectedLog = Activity::with(['causer', 'subject'])->find($id);
        $this->dispatch('open-modal', 'audit-detail-modal');
    }

    public function with()
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('event', 'like', '%' . $this->search . '%')
                  ;
            });
        }

        if ($this->actionFilter && $this->actionFilter !== 'All Actions') {
            // Map generic UI filter terms to Spatie events if needed, or just use loose matching
            $filter = strtolower($this->actionFilter);
            if ($filter === 'update') $filter = 'updated';
            if ($filter === 'create') $filter = 'created';
            if ($filter === 'delete') $filter = 'deleted';

            $query->where('event', $filter);
        }

        return [
            'auditLogs' => $query->paginate(10),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function getActionColor($event)
    {
        return match (strtolower($event)) {
            'created', 'create' => 'blue',
            'updated', 'update' => 'indigo',
            'deleted', 'delete' => 'red',
            'login' => 'green',
            'logout' => 'gray',
            default => 'gray',
        };
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">System Audit Logs</h2>
        <div class="flex space-x-3">
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 shadow-sm">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 shadow-sm">
                <i class="fas fa-download mr-2"></i> Export
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input wire:model.live.debounce.300ms="search" type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-64" placeholder="Search logs...">
            </div>
            <select wire:model.live="actionFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                <option>All Actions</option>
                <option value="login">Login</option>
                <option value="created">Create</option>
                <option value="updated">Update</option>
                <option value="deleted">Delete</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Timestamp</th>
                        <th class="px-6 py-4 font-semibold">User</th>
                        <th class="px-6 py-4 font-semibold">Action</th>
                        <th class="px-6 py-4 font-semibold">Details</th>
                        <th class="px-6 py-4 font-semibold">Resource</th>
                        <th class="px-6 py-4 font-semibold">IP Address</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($auditLogs as $log)
                        @php
                            $actionColor = $this->getActionColor($log->event);
                            $status = $log->getExtraProperty('status') ?? 'Success';
                            $statusColor = $status === 'Success' ? 'green' : 'red';
                            $statusIcon = $status === 'Success' ? 'check-circle' : 'times-circle';
                        @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <div class="flex items-center">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($log->causer?->name ?? 'System') }}&background=random" class="w-6 h-6 rounded-full mr-2">
                                {{ $log->causer?->name ?? 'System' }}
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-{{ $actionColor }}-100 text-{{ $actionColor }}-700 rounded text-xs font-semibold uppercase">{{ $log->event }}</span></td>
                        <td class="px-6 py-4">
                            <button wire:click="viewDetails({{ $log->id }})" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->subject_type)
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            @else
                                {{ $log->description }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $log->getExtraProperty('ip') ?? 'N/A' }}</td>
                        <td class="px-6 py-4"><span class="text-{{ $statusColor }}-600"><i class="fas fa-{{ $statusIcon }} mr-1"></i> {{ $status }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No audit logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $auditLogs->links() }}
        </div>
    </div>

    <!-- Audit Detail Modal -->
    <x-modal name="audit-detail-modal" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                Audit Log Details
            </h2>

            @if($selectedLog)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">User</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->causer?->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Event</p>
                            <p class="mt-1 text-sm text-gray-900 uppercase">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs font-semibold">{{ $selectedLog->event }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Date & Time</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->created_at->format('F j, Y g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">IP Address</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->getExtraProperty('ip') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->description }}</p>
                        </div>
                        @if($selectedLog->subject_type)
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-gray-500">Subject</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->subject_type }} #{{ $selectedLog->subject_id }}</p>
                        </div>
                        @endif
                    </div>

                    @if($selectedLog->properties && $selectedLog->properties->count() > 0)
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-500 mb-2">Properties</p>
                            <div class="bg-gray-50 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ json_encode($selectedLog->properties, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button x-on:click="$dispatch('close-modal', 'audit-detail-modal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Close
                    </button>
                </div>
            @endif
        </div>
    </x-modal>
</div>
