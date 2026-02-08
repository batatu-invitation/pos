<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Spatie\Activitylog\Models\Activity;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditLogsExport;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function exportExcel()
    {
        return Excel::download(new AuditLogsExport($this->search, $this->actionFilter), 'audit-logs.xlsx');
    }

    public function exportPdf()
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('event', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->actionFilter && $this->actionFilter !== 'All Actions') {
            $filter = strtolower($this->actionFilter);
            if ($filter === 'update') $filter = 'updated';
            if ($filter === 'create') $filter = 'created';
            if ($filter === 'delete') $filter = 'deleted';

            $query->where('event', $filter);
        }

        $logs = $query->get();
        $pdf = Pdf::loadView('pdf.audit-logs', compact('logs'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'audit-logs.pdf');
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

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('System Audit Logs') }}</h2>
        <div class="flex space-x-3">
            <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors">
                <i class="fas fa-filter mr-2"></i> {{ __('Filter') }}
            </button>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors">
                    <i class="fas fa-download mr-2"></i> {{ __('Export') }}
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg z-50 py-1 border border-gray-200 dark:border-gray-700" style="display: none;">
                    <button wire:click="exportExcel" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-excel mr-2 text-green-600"></i> Excel
                    </button>
                    <button wire:click="exportPdf" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input wire:model.live.debounce.300ms="search" type="text" class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 w-64 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" placeholder="{{ __('Search logs...') }}">
            </div>
            <select wire:model.live="actionFilter" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                <option>{{ __('All Actions') }}</option>
                <option value="login">{{ __('Login') }}</option>
                <option value="created">{{ __('Create') }}</option>
                <option value="updated">{{ __('Update') }}</option>
                <option value="deleted">{{ __('Delete') }}</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4 font-semibold">{{ __('Timestamp') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('User') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('Action') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('Details') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('Resource') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('IP Address') }}</th>
                        <th class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($auditLogs as $log)
                        @php
                            $actionColor = $this->getActionColor($log->event);
                            $status = $log->getExtraProperty('status') ?? __('Success');
                            $statusColor = $status === __('Success') ? 'green' : 'red';
                            $statusIcon = $status === __('Success') ? 'check-circle' : 'times-circle';
                        @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                            <div class="flex items-center">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($log->causer?->name ?? 'System') }}&background=random" class="w-6 h-6 rounded-full mr-2">
                                {{ $log->causer?->name ?? 'System' }}
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-{{ $actionColor }}-100 dark:bg-{{ $actionColor }}-900 text-{{ $actionColor }}-700 dark:text-{{ $actionColor }}-300 rounded text-xs font-semibold uppercase">{{ $log->event }}</span></td>
                        <td class="px-6 py-4">
                            <button wire:click="viewDetails({{ $log->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm">
                                <i class="fas fa-eye mr-1"></i> {{ __('View') }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            @if($log->subject_type)
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            @else
                                {{ $log->description }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->getExtraProperty('ip') ?? 'N/A' }}</td>
                        <td class="px-6 py-4"><span class="text-{{ $statusColor }}-600 dark:text-{{ $statusColor }}-400"><i class="fas fa-{{ $statusIcon }} mr-1"></i> {{ $status }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No audit logs found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $auditLogs->links() }}
        </div>
    </div>

    <!-- Audit Detail Modal -->
    <x-modal name="audit-detail-modal" focusable>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ __('Audit Log Details') }}
            </h2>

            @if($selectedLog)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('User') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedLog->causer?->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Event') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 uppercase">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-semibold">{{ $selectedLog->event }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date & Time') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedLog->created_at->format('F j, Y g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('IP Address') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedLog->getExtraProperty('ip') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Description') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedLog->description }}</p>
                        </div>
                        @if($selectedLog->subject_type)
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Subject') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedLog->subject_type }} #{{ $selectedLog->subject_id }}</p>
                        </div>
                        @endif
                    </div>

                    @if($selectedLog->properties && $selectedLog->properties->count() > 0)
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Properties') }}</p>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ json_encode($selectedLog->properties, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button x-on:click="$dispatch('close-modal', 'audit-detail-modal')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        {{ __('Close') }}
                    </button>
                </div>
            @endif
        </div>
    </x-modal>
</div>
