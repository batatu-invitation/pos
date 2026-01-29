<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new
#[Layout('components.layouts.app')]
#[Title('Admin Dashboard - Modern POS')]
class extends Component
{
    public function with()
    {
        // Stats
        $totalUsers = User::count();
        $newUsersCount = User::where('created_at', '>=', now()->startOfMonth())->count();

        $activeBranches = Tenant::where('status', 'Active')->count();

        // Audit Logs (Last 24 hours)
        $auditLogsCount = Activity::where('created_at', '>=', now()->subDay())->count();

        // System Health (Mock: Active Branches Percentage)
        $totalBranches = Tenant::count();
        $healthPercentage = $totalBranches > 0 ? round(($activeBranches / $totalBranches) * 100, 1) : 100;

        // Recent Logs
        $recentAuditLogs = Activity::with('causer')->latest()->take(10)->get()->map(function($log) {
            return [
                'user' => $log->causer ? $log->causer->name : __('System'),
                'action' => ucfirst($log->event),
                'action_color' => $this->getActionColor($log->event),
                'module' => $this->getModuleName($log->subject_type),
                'time' => $log->created_at->diffForHumans(),
            ];
        });

        // Branch Statuses
        $branchStatuses = Tenant::latest()->take(12)->get()->map(function($branch) {
            return [
                'name' => $branch->name,
                'status' => $branch->status,
                'status_color' => $this->getStatusColor($branch->status),
                'dot_color' => $this->getStatusColor($branch->status) . '-500',
            ];
        });

        // Chart Data: User Roles
        try {
            $rolesData = Role::withCount('users')->get();
            if ($rolesData->isEmpty()) {
                $roleLabels = [__('Users')];
                $roleCounts = [$totalUsers];
            } else {
                $roleLabels = $rolesData->pluck('name')->toArray();
                $roleCounts = $rolesData->pluck('users_count')->toArray();
            }
        } catch (\Exception $e) {
            // Fallback if Spatie Roles are not fully set up
            $roleLabels = [__('Users')];
            $roleCounts = [$totalUsers];
        }

        // Colors for Roles
        $baseColors = ['#6366f1', '#34d399', '#fb923c', '#f43f5e', '#8b5cf6', '#3b82f6', '#ec4899'];
        $roleColors = array_slice($baseColors, 0, count($roleLabels));
        // Pad if not enough
        while (count($roleColors) < count($roleLabels)) {
            $roleColors[] = '#' . dechex(rand(0x000000, 0xFFFFFF));
        }

        // Activity Chart Data (Today, 4-hour buckets)
        $activityLabels = ['00-04', '04-08', '08-12', '12-16', '16-20', '20-24'];
        $activityData = [];
        $startOfDay = Carbon::now()->startOfDay();

        for ($i = 0; $i < 6; $i++) {
            $start = $startOfDay->copy()->addHours($i * 4);
            $end = $start->copy()->addHours(4);
            $activityData[] = Activity::whereBetween('created_at', [$start, $end])->count();
        }

        return [
            'totalUsers' => $totalUsers,
            'newUsersCount' => $newUsersCount,
            'activeBranches' => $activeBranches,
            'auditLogsCount' => $auditLogsCount,
            'healthPercentage' => $healthPercentage,
            'recentAuditLogs' => $recentAuditLogs,
            'branchStatuses' => $branchStatuses,
            'roleLabels' => $roleLabels,
            'roleCounts' => $roleCounts,
            'roleColors' => $roleColors,
            'activityLabels' => $activityLabels,
            'activityData' => $activityData,
        ];
    }

    public function getActionColor($event)
    {
        return match (strtolower($event)) {
            'created', 'create' => 'green',
            'updated', 'update' => 'indigo',
            'deleted', 'delete' => 'red',
            'login' => 'blue',
            'logout' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusColor($status)
    {
        return match (strtolower($status)) {
            'active' => 'green',
            'maintenance' => 'yellow',
            'inactive', 'closed' => 'red',
            default => 'gray',
        };
    }

    public function getModuleName($model)
    {
        return $model ? class_basename($model) : __('System');
    }
};
?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6"
     x-data="dashboardCharts({
        activityLabels: {{ json_encode($activityLabels) }},
        activityData: {{ json_encode($activityData) }},
        roleLabels: {{ json_encode($roleLabels) }},
        roleCounts: {{ json_encode($roleCounts) }},
        roleColors: {{ json_encode($roleColors) }},
        translations: {
            activity: '{{ __('Activity') }}'
        }
     })"
     x-init="initCharts(); Livewire.hook('morph.updated', () => { initCharts(); });">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Admin Dashboard') }}</h2>
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ __('Last updated: Just now') }}</span>
            <button wire:click="$refresh" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:text-indigo-600 shadow-sm transition-colors">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium">{{ __('Total Users') }}</h3>
                <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $totalUsers }}</p>
                <p class="text-xs text-gray-500 mt-1">+{{ $newUsersCount }} {{ __('this month') }}</p>
            </div>
            <div class="p-3 bg-indigo-50 rounded-full">
                <i class="fas fa-users text-indigo-600 text-xl"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium">{{ __('Active Branches') }}</h3>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $activeBranches }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ __('Operational') }}</p>
            </div>
            <div class="p-3 bg-green-50 rounded-full">
                <i class="fas fa-store text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium">{{ __('System Health') }}</h3>
                <p class="text-3xl font-bold text-green-500 mt-2">{{ $healthPercentage }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ __('Branch Uptime') }}</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-full">
                <i class="fas fa-heartbeat text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium">{{ __('Audit Logs') }}</h3>
                <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $auditLogsCount }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ __('Last 24 hours') }}</p>
            </div>
            <div class="p-3 bg-purple-50 rounded-full">
                <i class="fas fa-history text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- System Activity Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">{{ __('System Activity (Today)') }}</h3>
            </div>
            <div class="h-80">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- User Role Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('User Roles') }}</h3>
            <div class="h-64 relative">
                <canvas id="rolesChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($roleLabels as $index => $label)
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $roleColors[$index] }}"></span>
                        <span class="text-gray-600">{{ $label }}</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $roleCounts[$index] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Logs and Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">{{ __('Recent Audit Logs') }}</h3>
                <a href="{{ route('admin.audit-logs') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View All') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">{{ __('User') }}</th>
                            <th class="px-6 py-3">{{ __('Action') }}</th>
                            <th class="px-6 py-3">{{ __('Module') }}</th>
                            <th class="px-6 py-3">{{ __('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentAuditLogs as $log)
                        <tr class="bg-white hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $log['user'] }}</td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-{{ $log['action_color'] }}-100 text-{{ $log['action_color'] }}-800 rounded-full text-xs">{{ $log['action'] }}</span></td>
                            <td class="px-6 py-4 text-gray-500">{{ $log['module'] }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $log['time'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Branch Status -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">{{ __('Branch Status') }}</h3>
                <button wire:click="$refresh" class="text-sm text-gray-500 hover:text-indigo-600"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                @foreach($branchStatuses as $branch)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-2 h-2 rounded-full bg-{{ $branch['dot_color'] }} mr-3"></div>
                        <span class="font-medium text-gray-700">{{ $branch['name'] }}</span>
                    </div>
                    <span class="text-xs font-semibold text-{{ $branch['status_color'] }}-600 bg-{{ $branch['status_color'] }}-100 px-2 py-1 rounded">{{ __($branch['status']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function dashboardCharts(data) {
            return {
                activityChartInstance: null,
                rolesChartInstance: null,
                data: data,

                initCharts() {
                    // Wait for next tick to ensure DOM is ready
                    this.$nextTick(() => {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js library is not loaded');
                            return;
                        }

                        // --- Activity Chart ---
                        const activityCanvas = document.getElementById('activityChart');
                        if (activityCanvas) {
                            // Destroy existing instance if it exists
                            if (this.activityChartInstance) {
                                this.activityChartInstance.destroy();
                                this.activityChartInstance = null;
                            }

                            const activityCtx = activityCanvas.getContext('2d');
                            this.activityChartInstance = new Chart(activityCtx, {
                                type: 'line',
                                data: {
                                    labels: this.data.activityLabels,
                                    datasets: [{
                                        label: this.data.translations.activity,
                                        data: this.data.activityData,
                                        borderColor: '#4f46e5',
                                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 3,
                                        pointBackgroundColor: '#fff',
                                        pointBorderColor: '#4f46e5',
                                        pointBorderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false,
                                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                            titleColor: '#1f2937',
                                            bodyColor: '#4b5563',
                                            borderColor: '#e5e7eb',
                                            borderWidth: 1,
                                            padding: 10,
                                            displayColors: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { borderDash: [2, 4], color: '#f3f4f6', drawBorder: false },
                                            ticks: { font: { size: 11 }, color: '#9ca3af' }
                                        },
                                        x: {
                                            grid: { display: false, drawBorder: false },
                                            ticks: { font: { size: 11 }, color: '#9ca3af' }
                                        }
                                    },
                                    interaction: { mode: 'nearest', axis: 'x', intersect: false }
                                }
                            });
                        }

                        // --- Roles Chart ---
                        const rolesCanvas = document.getElementById('rolesChart');
                        if (rolesCanvas) {
                            // Destroy existing instance if it exists
                            if (this.rolesChartInstance) {
                                this.rolesChartInstance.destroy();
                                this.rolesChartInstance = null;
                            }

                            const rolesCtx = rolesCanvas.getContext('2d');
                            this.rolesChartInstance = new Chart(rolesCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: this.data.roleLabels,
                                    datasets: [{
                                        data: this.data.roleCounts,
                                        backgroundColor: this.data.roleColors,
                                        borderWidth: 0,
                                        hoverOffset: 4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '75%',
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                            titleColor: '#1f2937',
                                            bodyColor: '#4b5563',
                                            borderColor: '#e5e7eb',
                                            borderWidth: 1,
                                            padding: 10,
                                            callbacks: {
                                                label: function(context) { return ' ' + context.label + ': ' + context.raw; }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
            };
        }
    </script>
</div>
