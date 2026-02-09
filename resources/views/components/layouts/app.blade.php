<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($currentTheme ?? 'system') === 'dark' ? 'dark' : '' }}">

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
        // Fix SweetAlert2 layout shift issues
        window.Swal = window.Swal.mixin({
            heightAuto: false,
            scrollbarPadding: false
        });
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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

<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 font-sans antialiased">

    <!-- Page Transition Loader -->
    <div x-data="{ loading: false }" 
         x-on:livewire:navigating.window="loading = true" 
         x-on:livewire:navigated.window="loading = false" 
         x-show="loading"
         x-transition.opacity.duration.200ms
         class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" 
         style="display: none;">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl flex flex-col items-center transform scale-100 transition-transform">
            <div class="relative w-16 h-16">
                <div class="absolute top-0 left-0 w-full h-full border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                <div class="absolute top-0 left-0 w-full h-full border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <p class="mt-4 text-sm font-semibold text-gray-700 dark:text-gray-200 animate-pulse">{{ __('Loading...') }}</p>
        </div>
    </div>

    <div class="h-screen flex flex-col overflow-hidden">
        <!-- Top Navigation -->
        <nav x-data="{ mobileMenuOpen: false }" class="bg-secondary text-white shadow-lg sticky top-0 z-50 border-b border-gray-700">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo & Desktop Nav -->
                    <div class="flex items-center flex-1">
                        <div class="flex-shrink-0 flex items-center mr-8">
                            <i class="fas fa-cube text-2xl text-indigo-500 mr-2"></i>
                            <span class="text-xl font-bold tracking-wide">Modern<span class="text-indigo-500">POS</span></span>
                        </div>
                        
                        <!-- Desktop Menu -->
                        <div class="hidden xl:block">
                            <div class="flex items-baseline space-x-2">
                                
                                <!-- Operations Dropdown -->
                                <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                    <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white flex items-center">
                                        <i class="fas fa-briefcase mr-1"></i> {{ __('Operations') }} <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                    </button>
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         class="absolute left-0 mt-0 w-56 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 dark:bg-gray-800 focus:outline-none z-50 max-h-[80vh] overflow-y-auto"
                                         style="display: none;">
                                        
                                        <!-- Dashboard -->
                                        <a wire:navigate href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 font-semibold border-b border-gray-100 dark:border-gray-700">
                                            <i class="fas fa-home mr-2 w-4"></i> {{ __('Dashboard') }}
                                        </a>

                                        @role(['Super Admin', 'Manager', 'Cashier'])
                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('POS') }}</div>
                                        <a wire:navigate href="{{ route('pos.visual') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Visual POS') }}</a>
                                        <a wire:navigate href="{{ route('pos.minimarket') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Mini Market POS') }}</a>
                                        <a wire:navigate href="{{ route('pos.terminal') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('POS Terminal') }}</a>
                                        @endrole

                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Sales') }}</div>
                                        <a wire:navigate href="{{ route('sales.sales') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Transactions') }}</a>

                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('People') }}</div>
                                        <a wire:navigate href="{{ route('people.customers') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Customers') }}</a>
                                        <a wire:navigate href="{{ route('people.employees') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Employees') }}</a>
                                    </div>
                                </div>

                                <!-- Management Dropdown -->
                                <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                    <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white flex items-center">
                                        <i class="fas fa-chart-line mr-1"></i> {{ __('Management') }} <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                    </button>
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         class="absolute left-0 mt-0 w-64 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 dark:bg-gray-800 focus:outline-none z-50 max-h-[80vh] overflow-y-auto"
                                         style="display: none;">
                                        
                                        @role(['Super Admin', 'Manager', 'Inventory Manager'])
                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Inventory') }}</div>
                                        <a wire:navigate href="{{ route('inventory.products') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Products') }}</a>
                                        <a wire:navigate href="{{ route('inventory.categories') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Categories') }}</a>
                                        <a wire:navigate href="{{ route('inventory.stock') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Stock') }}</a>
                                        <a wire:navigate href="{{ route('inventory.emojis') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Icons') }}</a>
                                        <a wire:navigate href="{{ route('inventory.colors') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Colors') }}</a>
                                        <a wire:navigate href="{{ route('admin.suppliers') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Suppliers') }}</a>
                                        @endrole

                                        @role(['Super Admin', 'Manager'])
                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Finance') }}</div>
                                        <a wire:navigate href="{{ route('finance.transactions') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Income & Expense') }}</a>
                                        @endrole

                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Analytics') }}</div>
                                        <a wire:navigate href="{{ route('analytics.overview') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Overview') }}</a>
                                        <a wire:navigate href="{{ route('analytics.growth') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Growth') }}</a>
                                        <a wire:navigate href="{{ route('analytics.profit-loss') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('P & L') }}</a>
                                        <a wire:navigate href="{{ route('analytics.cash-flow') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Cash Flow') }}</a>
                                        <a wire:navigate href="{{ route('analytics.balance-sheet') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Balance Sheet') }}</a>
                                        @role(['Super Admin', 'Manager'])
                                        <a wire:navigate href="{{ route('analytics.tax-report') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Tax Report') }}</a>
                                        @endrole
                                        <a wire:navigate href="{{ route('reports.sales') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Reports') }}</a>
                                    </div>
                                </div>

                                <!-- System Dropdown -->
                                <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                    <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white flex items-center">
                                        <i class="fas fa-cogs mr-1"></i> {{ __('System') }} <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                    </button>
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         class="absolute left-0 mt-0 w-64 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 dark:bg-gray-800 focus:outline-none z-50 max-h-[80vh] overflow-y-auto"
                                         style="display: none;">
                                        
                                        @role('Super Admin')
                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Admin') }}</div>
                                        <a wire:navigate href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Admin Panel') }}</a>
                                        <a wire:navigate href="{{ route('admin.users') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Users') }}</a>
                                        <a wire:navigate href="{{ route('admin.roles') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Roles') }}</a>
                                        <a wire:navigate href="{{ route('admin.branches') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Branches') }}</a>
                                        <a wire:navigate href="{{ route('admin.audit-logs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Audit Logs') }}</a>
                                        <a wire:navigate href="{{ route('admin.system-health') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('System Health') }}</a>
                                        @endrole

                                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Settings') }}</div>
                                        <a wire:navigate href="{{ route('settings.general') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('General') }}</a>
                                        <a wire:navigate href="{{ route('settings.receipt') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Receipt') }}</a>
                                        @role('Super Admin')
                                        <a wire:navigate href="{{ route('settings.payment') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Payment Methods') }}</a>
                                        <a wire:navigate href="{{ route('settings.notifications') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Notifications') }}</a>
                                        <a wire:navigate href="{{ route('settings.integrations') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Integrations') }}</a>
                                        <a wire:navigate href="{{ route('settings.api-keys') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('API Keys') }}</a>
                                        <a wire:navigate href="{{ route('settings.backup') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Backups') }}</a>
                                        @endrole
                                        <a wire:navigate href="{{ route('settings.taxes') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('Taxes') }}</a>
                                        <a wire:navigate href="{{ route('settings.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 ml-2 border-l-2 border-transparent hover:border-indigo-500">{{ __('My Profile') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Controls -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Open POS Button -->
                        <a href="{{ route('pos.visual') }}"
                            class="hidden lg:flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            <i class="fas fa-cash-register mr-2"></i>
                            {{ __('Open POS') }}
                        </a>

                        <!-- Search -->
                        <div class="relative hidden lg:block">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-500"></i>
                            </span>
                            <input type="text"
                                class="w-48 py-1.5 pl-10 pr-4 bg-gray-800 border border-gray-600 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:bg-gray-700 text-gray-300 placeholder-gray-500 transition-colors"
                                placeholder="{{ __('Search...') }}">
                        </div>

                        <livewire:components.device-toolbar />

                        <!-- Theme Switcher -->
                        <div class="relative" x-data="{
                            open: false,
                            theme: '{{ $currentTheme ?? "system" }}',
                            setTheme(val) {
                                this.theme = val;
                                localStorage.setItem('theme', val);
                                document.cookie = 'theme=' + val + '; path=/; max-age=31536000; SameSite=Lax';
                                if (val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                                this.open = false;
                            },
                            init() {
                                if (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                                    document.documentElement.classList.add('dark');
                                }
                                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                                    if (this.theme === 'system') {
                                        if (e.matches) document.documentElement.classList.add('dark');
                                        else document.documentElement.classList.remove('dark');
                                    }
                                });
                            }
                        }">
                            <button @click="open = !open"
                                class="text-gray-400 hover:text-white transition-colors focus:outline-none p-1">
                                <template x-if="theme === 'light'"><i class="fas fa-sun text-lg"></i></template>
                                <template x-if="theme === 'dark'"><i class="fas fa-moon text-lg"></i></template>
                                <template x-if="theme === 'system'"><i class="fas fa-desktop text-lg"></i></template>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 mt-2 w-36 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
                                style="display: none;">
                                <button @click="setTheme('light')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="fas fa-sun w-5 text-center mr-2"></i> {{ __('Light') }}
                                </button>
                                <button @click="setTheme('dark')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="fas fa-moon w-5 text-center mr-2"></i> {{ __('Dark') }}
                                </button>
                                <button @click="setTheme('system')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="fas fa-desktop w-5 text-center mr-2"></i> {{ __('System') }}
                                </button>
                            </div>
                        </div>

                        <!-- Language Switcher -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-400 hover:text-white transition-colors focus:outline-none p-1 flex items-center">
                                <i class="fas fa-globe text-lg"></i>
                                <span class="text-xs font-bold ml-1 uppercase">{{ app()->getLocale() == 'id' ? 'ID' : 'EN' }}</span>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
                                style="display: none;">
                                <a wire:navigate href="{{ route('lang.switch', 'en') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <span class="mr-2">🇺🇸</span> English
                                </a>
                                <a wire:navigate href="{{ route('lang.switch', 'id') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <span class="mr-2">🇮🇩</span> Indonesia
                                </a>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative ml-3" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" class="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                                    <img class="h-8 w-8 rounded-full border border-gray-600" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4f46e5&color=fff" alt="">
                                </button>
                            </div>
                            <div x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 dark:bg-gray-800 focus:outline-none z-50"
                                style="display: none;">
                                <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name ?? 'Guest' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                                </div>
                                <a wire:navigate href="{{ route('settings.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Your Profile') }}</a>
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf
                                    <a href="#" @click.prevent="$root.submit();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 text-red-600 dark:text-red-400">
                                        {{ __('Sign out') }}
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="-mr-2 flex xl:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none">
                            <i class="fas fa-bars text-xl" x-show="!mobileMenuOpen"></i>
                            <i class="fas fa-times text-xl" x-show="mobileMenuOpen" style="display: none;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" class="xl:hidden border-t border-gray-700 bg-secondary max-h-[80vh] overflow-y-auto" style="display: none;">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    
                    <!-- Operations -->
                    <div class="px-3 pt-2 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Operations') }}</div>
                    <a wire:navigate href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-gray-900 ml-2">{{ __('Dashboard') }}</a>
                    
                    @role(['Super Admin', 'Manager', 'Cashier'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('POS') }}</p>
                        <a href="{{ route('pos.visual') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Visual POS') }}</a>
                        <a href="{{ route('pos.minimarket') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Mini Market POS') }}</a>
                        <a href="{{ route('pos.terminal') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('POS Terminal') }}</a>
                    </div>
                    @endrole

                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Sales') }}</p>
                        <a wire:navigate href="{{ route('sales.sales') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Transactions') }}</a>
                    </div>

                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('People') }}</p>
                        <a wire:navigate href="{{ route('people.customers') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Customers') }}</a>
                        <a wire:navigate href="{{ route('people.employees') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Employees') }}</a>
                    </div>

                    <!-- Management -->
                    <div class="px-3 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-t border-gray-700 mt-2">{{ __('Management') }}</div>
                    
                    @role(['Super Admin', 'Manager', 'Inventory Manager'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Inventory') }}</p>
                        <a wire:navigate href="{{ route('inventory.products') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Products') }}</a>
                        <a wire:navigate href="{{ route('inventory.categories') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Categories') }}</a>
                        <a wire:navigate href="{{ route('inventory.stock') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Stock') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Finance') }}</p>
                        <a wire:navigate href="{{ route('finance.transactions') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Income & Expense') }}</a>
                    </div>
                    @endrole

                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Analytics') }}</p>
                        <a wire:navigate href="{{ route('analytics.overview') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Overview') }}</a>
                        <a wire:navigate href="{{ route('analytics.growth') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Growth') }}</a>
                        <a wire:navigate href="{{ route('analytics.profit-loss') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('P & L') }}</a>
                        <a wire:navigate href="{{ route('analytics.cash-flow') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Cash Flow') }}</a>
                        <a wire:navigate href="{{ route('analytics.balance-sheet') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Balance Sheet') }}</a>
                        @role(['Super Admin', 'Manager'])
                        <a wire:navigate href="{{ route('analytics.tax-report') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Tax Report') }}</a>
                        @endrole
                        <a wire:navigate href="{{ route('reports.sales') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Reports') }}</a>
                    </div>

                    <!-- System -->
                    <div class="px-3 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-t border-gray-700 mt-2">{{ __('System') }}</div>
                    
                    @role('Super Admin')
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Admin') }}</p>
                        <a wire:navigate href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Admin Panel') }}</a>
                        <a wire:navigate href="{{ route('admin.users') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Users') }}</a>
                        <a wire:navigate href="{{ route('admin.roles') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Roles') }}</a>
                    </div>
                    @endrole

                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Settings') }}</p>
                        <a wire:navigate href="{{ route('settings.general') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('General') }}</a>
                        <a wire:navigate href="{{ route('settings.receipt') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Receipt') }}</a>
                        @role('Super Admin')
                        <a wire:navigate href="{{ route('settings.payment') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Payment Methods') }}</a>
                        <a wire:navigate href="{{ route('settings.notifications') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('Notifications') }}</a>
                        @endrole
                        <a wire:navigate href="{{ route('settings.profile') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 ml-2">{{ __('My Profile') }}</a>
                    </div>

                    <div class="pt-4 pb-3 border-t border-gray-700 mt-4">
                        <div class="flex items-center px-5">
                            <div class="flex-shrink-0">
                                <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4f46e5&color=fff" alt="">
                            </div>
                            <div class="ml-3">
                                <div class="text-base font-medium leading-none text-white">{{ auth()->user()->name ?? 'Guest' }}</div>
                                <div class="text-sm font-medium leading-none text-gray-400">{{ auth()->user()->email ?? '' }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                                @csrf
                                <button type="submit" class="flex-shrink-0 p-1 rounded-full text-gray-400 hover:text-white focus:outline-none">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
            {{ $slot }}
        </main>

        <!-- Status Footer -->
        <footer x-data="deviceManager" class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-1 px-4 text-xs text-gray-600 dark:text-gray-400 z-40 shrink-0">
            <div class="flex items-center justify-between h-6">
                <div class="flex items-center space-x-6">
                    <!-- Printer Status -->
                    <div class="flex items-center cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" 
                         @click="connectPrinter" 
                         title="{{ __('Click to connect printer via Web Serial') }}">
                        <i class="fas fa-print mr-2 text-indigo-500"></i>
                        <span class="font-medium">{{ __('Printers Connected') }}: <span class="font-bold text-gray-900 dark:text-white" x-text="printerCount"></span></span>
                    </div>
                    
                    <!-- Scanner Status -->
                    <div class="flex items-center cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" 
                         @click="connectScanner" 
                         title="{{ __('Click to connect scanner via WebHID (or type to test)') }}">
                        <i class="fas fa-barcode mr-2 text-indigo-500"></i>
                        <span class="font-medium">{{ __('Scanner') }}: <span class="font-bold text-green-500" x-text="scannerStatus"></span></span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('System Online') }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="{{ asset('js/main.js') }}" defer></script>
    <script src="{{ asset('js/pos-devices.js') }}" defer></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            initSweetAlert();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initSweetAlert();
        });

        function initSweetAlert() {
            window.addEventListener('notify', event => {
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
