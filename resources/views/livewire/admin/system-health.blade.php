<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('System Health - Modern POS')]
class extends Component
{
    public function with()
    {
        // 1. Server Status (Generic check)
        $serverStatus = __('Online');

        // 2. CPU Load (If available, otherwise 0)
        $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpuUsage = isset($cpuLoad[0]) ? round($cpuLoad[0] * 100 / 4) : 0; // Assuming 4 cores as per UI, heuristic

        // 3. Memory Usage (PHP Process)
        $memoryUsage = memory_get_usage(true);
        $memoryLimitStr = ini_get('memory_limit');
        $memoryLimit = $this->convertToBytes($memoryLimitStr);
        // If memory limit is -1 (unlimited), assume a safe upper bound like 2GB for visualization or just show usage
        if ($memoryLimit <= 0) $memoryLimit = 2 * 1024 * 1024 * 1024;
        $memoryPercentage = round(($memoryUsage / $memoryLimit) * 100, 1);
        $memoryUsageFormatted = round($memoryUsage / 1024 / 1024, 2) . ' MB';
        $memoryLimitFormatted = round($memoryLimit / 1024 / 1024) . ' MB';

        // 4. Disk Space
        $diskTotal = disk_total_space('.');
        $diskFree = disk_free_space('.');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercentage = round(($diskUsed / $diskTotal) * 100);
        $diskFreeFormatted = round($diskFree / 1024 / 1024 / 1024, 2) . ' GB';
        $diskTotalFormatted = round($diskTotal / 1024 / 1024 / 1024, 2) . ' GB';

        // 5. Services Check
        $services = [
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkStorage(),
            $this->checkQueue(),
        ];

        // 6. Recent Alerts (From Activity Log, specifically errors or warnings if any, or just recent actions)
        // We'll map recent activity logs as "alerts" for now since we don't have a dedicated alerts table
        $alerts = Activity::with('causer')
            ->latest()
            ->take(8)
            ->get()
            ->map(function($log) {
                return [
                    'time' => $log->created_at->format('h:i A'),
                    'service' => $log->log_name ?: __('System'),
                    'type' => $log->event === 'deleted' ? __('Warning') : __('Info'),
                    'type_color' => $log->event === 'deleted' ? 'yellow' : 'blue',
                    'message' => ucfirst($log->description),
                    'status' => __('Logged')
                ];
            });

        // 7. Chart Data: Activity Volume (Last 10 hours)
        $chartLabels = [];
        $chartData = [];
        for ($i = 9; $i >= 0; $i--) {
            $time = now()->subHours($i);
            $chartLabels[] = $time->format('H:00');
            $chartData[] = Activity::whereBetween('created_at', [
                $time->copy()->startOfHour(),
                $time->copy()->endOfHour()
            ])->count();
        }

        return [
            'serverStatus' => $serverStatus,
            'cpuUsage' => $cpuUsage,
            'memoryUsage' => $memoryUsageFormatted,
            'memoryLimit' => $memoryLimitFormatted,
            'memoryPercentage' => $memoryPercentage,
            'diskFree' => $diskFreeFormatted,
            'diskTotal' => $diskTotalFormatted,
            'diskPercentage' => $diskPercentage,
            'services' => $services,
            'alerts' => $alerts,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ];
    }

    private function convertToBytes($value) {
        $number = (int) $value;
        $unit = strtoupper(substr($value, -1));
        switch ($unit) {
            case 'G': $number *= 1024;
            case 'M': $number *= 1024;
            case 'K': $number *= 1024;
        }
        return $number;
    }

    private function checkDatabase() {
        try {
            DB::connection()->getPdo();
            return ['name' => __('Database'), 'icon' => 'database', 'status' => __('Healthy'), 'status_color' => 'green'];
        } catch (\Exception $e) {
            return ['name' => __('Database'), 'icon' => 'database', 'status' => __('Error'), 'status_color' => 'red'];
        }
    }

    private function checkCache() {
        try {
            Cache::store()->put('health_check', 'ok', 5);
            $val = Cache::store()->get('health_check');
            return ['name' => __('Cache System'), 'icon' => 'server', 'status' => ($val === 'ok' ? __('Healthy') : __('Issues')), 'status_color' => ($val === 'ok' ? 'green' : 'yellow')];
        } catch (\Exception $e) {
            return ['name' => __('Cache System'), 'icon' => 'server', 'status' => __('Error'), 'status_color' => 'red'];
        }
    }

    private function checkStorage() {
        if (is_writable(storage_path())) {
            return ['name' => __('Storage'), 'icon' => 'hdd', 'status' => __('Writable'), 'status_color' => 'green'];
        }
        return ['name' => __('Storage'), 'icon' => 'hdd', 'status' => __('Read-Only'), 'status_color' => 'red'];
    }

    private function checkQueue() {
        // Simple check: failed jobs count
        try {
            $failed = DB::table('failed_jobs')->count();
            if ($failed > 0) {
                 return ['name' => __('Queue Worker'), 'icon' => 'tasks', 'status' => "$failed " . __('Failed'), 'status_color' => 'yellow'];
            }
            return ['name' => __('Queue Worker'), 'icon' => 'tasks', 'status' => __('Healthy'), 'status_color' => 'green'];
        } catch (\Exception $e) {
            // Table might not exist
             return ['name' => __('Queue Worker'), 'icon' => 'tasks', 'status' => __('Unknown'), 'status_color' => 'gray'];
        }
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 dark:bg-gray-900"
     x-data="{
         init() {
             this.$nextTick(() => {
                 this.initCharts();
             });
         },
         initCharts() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded');
                return;
            }

            const ctx = document.getElementById('activityVolumeChart');
            if (ctx) {
                // Destroy existing chart if it exists to prevent duplicates on refresh
                const existingChart = Chart.getChart(ctx);
                if (existingChart) existingChart.destroy();

                const isDarkMode = document.documentElement.classList.contains('dark');
                const gridColor = isDarkMode ? '#374151' : '#f3f4f6';
                const textColor = isDarkMode ? '#9ca3af' : '#4b5563';

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {{ json_encode($chartLabels) }},
                        datasets: [{
                            label: '{{ __('Activity Volume (Events)') }}',
                            data: {{ json_encode($chartData) }},
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                ticks: { precision: 0, color: textColor },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
         }
     }"
     x-init="init(); Livewire.hook('morph.updated', () => { initCharts(); });">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ __('System Health') }}</h1>
        <div class="flex items-center space-x-4">
            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm font-medium border border-green-200 dark:border-green-800">
                <i class="fas fa-check-circle mr-1"></i> {{ __('System Online') }}
            </span>
            <button wire:click="$refresh" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors dark:hover:text-indigo-400" title="{{ __('Refresh Data') }}">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Server Status') }}</h3>
                <span class="px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-semibold rounded-full">{{ $serverStatus }}</span>
            </div>
            <div class="flex items-center text-green-600 dark:text-green-400">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="font-medium">Laravel v{{ app()->version() }}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 dark:text-gray-500">PHP v{{ phpversion() }}</div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('CPU Load (1m)') }}</h3>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Est. Usage') }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100" >{{ $cpuUsage }}%</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $cpuUsage }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Memory (PHP)') }}</h3>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Limit:') }} {{ $memoryLimit }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100">{{ $memoryUsage }}</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ $memoryPercentage }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Disk Space') }}</h3>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $diskTotal }} {{ __('Total') }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100">{{ $diskFree }} {{ __('Free') }}</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $diskPercentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Activity Chart -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 mb-4 dark:text-gray-100">{{ __('Activity Volume (Last 10 Hours)') }}</h3>
            <div class="h-64">
                <canvas id="activityVolumeChart"></canvas>
            </div>
        </div>

        <!-- System Services Health -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 mb-4 dark:text-gray-100">{{ __('Service Status') }}</h3>
            <div class="space-y-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                @foreach($services as $service)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl dark:bg-gray-700/50">
                    <div class="flex items-center">
                        <i class="fas fa-{{ $service['icon'] }} text-indigo-500 w-6"></i>
                        <span class="font-medium text-gray-900 ml-2 dark:text-gray-200">{{ $service['name'] }}</span>
                    </div>
                    @php
                        $colorClass = match($service['status_color']) {
                            'green' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'red' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                        };
                    @endphp
                    <span class="px-2 py-1 {{ $colorClass }} text-xs font-semibold rounded-lg">{{ $service['status'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent System Activity -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Recent System Activity') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider dark:bg-gray-700/50 dark:text-gray-400">
                        <th class="px-6 py-3 font-semibold">{{ __('Time') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Source') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Type') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Message') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-700">
                    @forelse($alerts as $alert)
                    <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/30">
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $alert['time'] }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-200">{{ $alert['service'] }}</td>
                        <td class="px-6 py-4">
                            @php
                                $typeColor = match($alert['type_color']) {
                                    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                };
                            @endphp
                            <span class="px-2 py-1 {{ $typeColor }} rounded-lg text-xs">{{ $alert['type'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $alert['message'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('No recent activity found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
