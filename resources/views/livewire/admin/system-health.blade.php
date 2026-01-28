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
        $serverStatus = 'Online';

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
                    'service' => $log->log_name ?: 'System',
                    'type' => $log->event === 'deleted' ? 'Warning' : 'Info',
                    'type_color' => $log->event === 'deleted' ? 'yellow' : 'blue',
                    'message' => ucfirst($log->description),
                    'status' => 'Logged'
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
            return ['name' => 'Database', 'icon' => 'database', 'status' => 'Healthy', 'status_color' => 'green'];
        } catch (\Exception $e) {
            return ['name' => 'Database', 'icon' => 'database', 'status' => 'Error', 'status_color' => 'red'];
        }
    }

    private function checkCache() {
        try {
            Cache::store()->put('health_check', 'ok', 5);
            $val = Cache::store()->get('health_check');
            return ['name' => 'Cache System', 'icon' => 'server', 'status' => ($val === 'ok' ? 'Healthy' : 'Issues'), 'status_color' => ($val === 'ok' ? 'green' : 'yellow')];
        } catch (\Exception $e) {
            return ['name' => 'Cache System', 'icon' => 'server', 'status' => 'Error', 'status_color' => 'red'];
        }
    }

    private function checkStorage() {
        if (is_writable(storage_path())) {
            return ['name' => 'Storage', 'icon' => 'hdd', 'status' => 'Writable', 'status_color' => 'green'];
        }
        return ['name' => 'Storage', 'icon' => 'hdd', 'status' => 'Read-Only', 'status_color' => 'red'];
    }

    private function checkQueue() {
        // Simple check: failed jobs count
        try {
            $failed = DB::table('failed_jobs')->count();
            if ($failed > 0) {
                 return ['name' => 'Queue Worker', 'icon' => 'tasks', 'status' => "$failed Failed", 'status_color' => 'yellow'];
            }
            return ['name' => 'Queue Worker', 'icon' => 'tasks', 'status' => 'Healthy', 'status_color' => 'green'];
        } catch (\Exception $e) {
            // Table might not exist
             return ['name' => 'Queue Worker', 'icon' => 'tasks', 'status' => 'Unknown', 'status_color' => 'gray'];
        }
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6"
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

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {{ json_encode($chartLabels) }},
                        datasets: [{
                            label: 'Activity Volume (Events)',
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
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }
         }
     }"
     x-init="init(); Livewire.hook('morph.updated', () => { initCharts(); });">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">System Health</h1>
        <div class="flex items-center space-x-4">
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                <i class="fas fa-check-circle mr-1"></i> System Online
            </span>
            <button wire:click="$refresh" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors" title="Refresh Data">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Server Status</h3>
                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">{{ $serverStatus }}</span>
            </div>
            <div class="flex items-center text-green-600">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="font-medium">Laravel v{{ app()->version() }}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1">PHP v{{ phpversion() }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">CPU Load (1m)</h3>
                <span class="text-xs text-gray-400">Est. Usage</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2" >{{ $cpuUsage }}%</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $cpuUsage }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Memory (PHP)</h3>
                <span class="text-xs text-gray-400">Limit: {{ $memoryLimit }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2">{{ $memoryUsage }}</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ $memoryPercentage }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Disk Space</h3>
                <span class="text-xs text-gray-400">{{ $diskTotal }} Total</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-2">{{ $diskFree }} Free</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $diskPercentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Activity Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Activity Volume (Last 10 Hours)</h3>
            <div class="h-64">
                <canvas id="activityVolumeChart"></canvas>
            </div>
        </div>

        <!-- System Services Health -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Service Status</h3>
            <div class="space-y-4 max-h-64 overflow-y-auto pr-2">
                @foreach($services as $service)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-{{ $service['icon'] }} text-indigo-500 w-6"></i>
                        <span class="font-medium text-gray-900 ml-2">{{ $service['name'] }}</span>
                    </div>
                    <span class="px-2 py-1 bg-{{ $service['status_color'] }}-100 text-{{ $service['status_color'] }}-700 text-xs font-semibold rounded">{{ $service['status'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent System Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Recent System Activity</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-semibold">Time</th>
                        <th class="px-6 py-3 font-semibold">Source</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($alerts as $alert)
                    <tr>
                        <td class="px-6 py-4 text-gray-500">{{ $alert['time'] }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $alert['service'] }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-{{ $alert['type_color'] }}-100 text-{{ $alert['type_color'] }}-700 rounded text-xs">{{ $alert['type'] }}</span></td>
                        <td class="px-6 py-4 text-gray-600">{{ $alert['message'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent activity found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
