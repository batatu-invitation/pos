<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard - Modern POS' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#1e293b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-secondary text-white transition-transform transform -translate-x-full md:translate-x-0 md:static md:inset-0 flex flex-col">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 border-b border-gray-700 bg-secondary shadow-md">
                <i class="fas fa-cube text-2xl text-indigo-500 mr-3"></i>
                <span class="text-xl font-bold tracking-wide">Modern<span class="text-indigo-500">POS</span></span>
            </div>

            <!-- Nav Links -->
            <div class="flex-1 overflow-y-auto py-4">
                <nav class="px-2 space-y-1">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-2">Main</p>
                    <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-home w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a wire:navigate href="{{ route('pos.visual') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('pos.visual') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-shopping-cart w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Visual POS</span>
                    </a>
                    <a wire:navigate href="{{ route('pos.minimarket') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('pos.minimarket') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-barcode w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Mini Market POS</span>
                    </a>
                    <a wire:navigate href="{{ route('pos.terminal') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('pos.terminal') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-desktop w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">POS Terminal</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Inventory</p>
                    <a wire:navigate href="{{ route('inventory.products') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('inventory.products') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-box w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Products</span>
                    </a>
                    <a wire:navigate href="{{ route('inventory.categories') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('inventory.categories') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-tags w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Categories</span>
                    </a>
                    <a wire:navigate href="{{ route('inventory.stock') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('inventory.stock') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-warehouse w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Stock</span>
                    </a>
                    <a wire:navigate href="{{ route('inventory.emojis') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('inventory.emojis') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-icons w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Icons</span>
                    </a>
                    <a wire:navigate href="{{ route('inventory.colors') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('inventory.colors') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-palette w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Colors</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Sales</p>
                    <a wire:navigate href="{{ route('sales.sales') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('sales.sales') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-receipt w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Transactions</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">People</p>
                    <a wire:navigate href="{{ route('people.customers') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('people.customers') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-users w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Customers</span>
                    </a>
                    <a wire:navigate href="{{ route('people.employees') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('people.employees') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-user-tie w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Employees</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Analytics</p>
                    <a wire:navigate href="{{ route('analytics.overview') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('analytics.overview') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-chart-line w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Overview</span>
                    </a>
                    <a wire:navigate href="{{ route('analytics.growth') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('analytics.growth') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-chart-area w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Growth</span>
                    </a>
                    <a wire:navigate href="{{ route('analytics.profit-loss') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('analytics.profit-loss') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-file-invoice-dollar w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">P & L</span>
                    </a>
                    <a wire:navigate href="{{ route('analytics.cash-flow') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('analytics.cash-flow') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-money-bill-wave w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Cash Flow</span>
                    </a>
                    <a wire:navigate href="{{ route('analytics.tax-report') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('analytics.tax-report') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-university w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Tax Report</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Super Admin</p>
                    <a wire:navigate href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-shield-alt w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Admin Panel</span>
                    </a>
                    <a wire:navigate href="{{ route('admin.users') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.users') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-user-shield w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Users</span>
                    </a>
                    <a wire:navigate href="{{ route('admin.roles') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.roles') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-user-tag w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Roles</span>
                    </a>
                    <a wire:navigate href="{{ route('admin.branches') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.branches') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-store w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Branches</span>
                    </a>
                    <a wire:navigate href="{{ route('admin.audit-logs') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.audit-logs') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-history w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Audit Logs</span>
                    </a>
                    <a wire:navigate href="{{ route('admin.system-health') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.system-health') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-heartbeat w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">System Health</span>
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Management</p>
                    <a wire:navigate href="{{ route('reports.sales') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('reports.*') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-chart-bar w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Reports</span>
                    </a>
                    <a wire:navigate href="{{ route('settings.taxes') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('settings.taxes') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-percent w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Taxes</span>
                    </a>
                    <a wire:navigate href="{{ route('settings.profile') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('settings.profile') ? 'bg-gray-700 text-white border-l-4 border-indigo-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-lg group transition-colors">
                        <i class="fas fa-cog w-6 text-center mr-2 text-gray-400 group-hover:text-indigo-400"></i>
                        <span class="font-medium">Settings</span>
                    </a>
                </nav>
            </div>

            <!-- User Profile (Bottom) -->
            <div class="p-4 border-t border-gray-700 bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin User') }}&background=4f46e5&color=fff" class="w-8 h-8 rounded-full border border-gray-600">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">{{ auth()->user()->name ?? 'Guest' }}</p>
                            <p class="text-xs text-gray-400">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" x-data x-on:submit.prevent="
                        Swal.fire({
                            title: 'Logout?',
                            text: 'Are you sure you want to logout?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#ef4444',
                            confirmButtonText: 'Yes, logout!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $el.submit();
                            }
                        })
                    ">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors p-2 rounded-lg hover:bg-gray-800" title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden"></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <header class="flex items-center justify-between px-6 py-4 bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()" class="text-gray-500 focus:outline-none mr-4">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800">{{ $header ?? 'Dashboard' }}</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative hidden md:block">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" class="w-64 py-2 pl-10 pr-4 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors" placeholder="Search...">
                    </div>

                    <button class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
                    </button>

                    <a wire:navigate href="{{ route('pos.visual') }}" class="hidden sm:flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                        <i class="fas fa-cash-register mr-2"></i>
                        Open POS
                    </a>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}" defer></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            initSweetAlert();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initSweetAlert();
        });

        function initSweetAlert() {
            window.addEventListener('notify', event => {
                const message = event.detail; // Livewire dispatch sends detail as the data
                // If the detail is an array (standard CustomEvent), accessing [0] might be needed depending on how it's dispatched
                // But Livewire 3 usually sends the params directly in detail if it's a simple value, or as an array.
                // Let's handle both.
                const msg = Array.isArray(event.detail) ? event.detail[0] : event.detail;

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: msg,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });

            // Also listen for error events if needed
            window.addEventListener('notify-error', event => {
                const msg = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                 Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: msg,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });

            // Listen for confirmation events
            window.addEventListener('swal:confirm', event => {
                const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                const { title, text, icon, confirmButtonText, method, params, componentId } = detail;

                Swal.fire({
                    title: title || 'Are you sure?',
                    text: text || "You won't be able to revert this!",
                    icon: icon || 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: confirmButtonText || 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.find(componentId).call(method, ...params);
                    }
                });
            });
        }
    </script>
</body>
</html>
