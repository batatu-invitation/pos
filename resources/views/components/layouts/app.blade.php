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
                                
                               <!-- Operations Dropdown - Only show if user has access to at least one item inside -->
@hasanyrole(['Super Admin', 'Manager', 'Cashier', 'Analyst', 'Customer Support'])
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
        
        <!-- Dashboard - All authenticated users -->
        <a wire:navigate href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} font-semibold border-b border-gray-100 dark:border-gray-700">
            <i class="fas fa-home mr-2 w-4"></i> {{ __('Dashboard') }}
        </a>

        @role(['Super Admin', 'Manager', 'Cashier'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('POS') }}</div>
        <a wire:navigate href="{{ route('pos.visual') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('pos.visual') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Visual POS') }}</a>
        <a wire:navigate href="{{ route('pos.minimarket') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('pos.minimarket') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Mini Market POS') }}</a>
        <a wire:navigate href="{{ route('pos.terminal') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('pos.terminal') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('POS Terminal') }}</a>
        @endrole

        @role(['Super Admin', 'Manager', 'Cashier', 'Analyst'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Sales') }}</div>
        <a wire:navigate href="{{ route('sales.sales') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('sales.sales') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Transactions') }}</a>
        @endrole

        @role(['Super Admin', 'Manager', 'Customer Support'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('People') }}</div>
        <a wire:navigate href="{{ route('people.customers') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.customers') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Customers') }}</a>
        <a wire:navigate href="{{ route('people.employees') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.employees') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Employees') }}</a>
        @endrole
    </div>
</div>
@endhasanyrole

<!-- Management Dropdown - Only show if user has access to at least one item inside -->
@hasanyrole(['Super Admin', 'Manager', 'Inventory Manager', 'Analyst'])
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
        
        @role(['Super Admin', 'Manager', 'Inventory Manager', 'Analyst'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Inventory') }}</div>
        <a wire:navigate href="{{ route('inventory.products') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('inventory.products') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Products') }}</a>
        <a wire:navigate href="{{ route('inventory.stock') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('inventory.stock') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Stock') }}</a>
        <a wire:navigate href="{{ route('inventory.categories') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('inventory.categories') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Categories') }}</a>
        <a wire:navigate href="{{ route('inventory.emojis') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('inventory.emojis') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Icons') }}</a>
        <a wire:navigate href="{{ route('inventory.colors') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('inventory.colors') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Colors') }}</a>
        @endrole

        @role(['Super Admin', 'Manager', 'Analyst'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Suppliers') }}</div>
        <a wire:navigate href="{{ route('admin.suppliers') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.suppliers') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Suppliers') }}</a>
        @endrole

        @role(['Super Admin', 'Manager'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Finance') }}</div>
        <a wire:navigate href="{{ route('finance.transactions') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('finance.transactions') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Income & Expense') }}</a>
        <a wire:navigate href="{{ route('analytics.cash-bank-records') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.cash-bank-records') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Cash & Bank') }}</a>
        <a wire:navigate href="{{ route('analytics.accounts-receivable') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.accounts-receivable') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Accounts Receivable') }}</a>
        <a wire:navigate href="{{ route('analytics.accounts-payable') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.accounts-payable') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Accounts Payable') }}</a>

        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Bookkeeping') }}</div>
        <a wire:navigate href="{{ route('analytics.chart-of-accounts') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.chart-of-accounts') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Chart of Accounts') }}</a>
        <a wire:navigate href="{{ route('analytics.journal') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.journal') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Journal Entries') }}</a>
        <a wire:navigate href="{{ route('analytics.general-ledger') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.general-ledger') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('General Ledger') }}</a>
        <a wire:navigate href="{{ route('analytics.memo') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.memo') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Memos / Adjustments') }}</a>
        @endrole

        @role(['Super Admin', 'Manager', 'Analyst'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Analytics') }}</div>
        <a wire:navigate href="{{ route('analytics.overview') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.overview') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Overview') }}</a>
        <a wire:navigate href="{{ route('analytics.growth') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.growth') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Growth') }}</a>
        <a wire:navigate href="{{ route('analytics.profit-loss') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.profit-loss') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('P & L') }}</a>
        <a wire:navigate href="{{ route('analytics.cash-flow') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.cash-flow') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Cash Flow') }}</a>
        <a wire:navigate href="{{ route('analytics.balance-sheet') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.balance-sheet') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Balance Sheet') }}</a>
        <a wire:navigate href="{{ route('analytics.inventory-capital') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.inventory-capital') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Inventory Capital') }}</a>
        <a wire:navigate href="{{ route('analytics.trial-balance') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.trial-balance') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Trial Balance') }}</a>
        @role(['Super Admin', 'Manager'])
        <a wire:navigate href="{{ route('analytics.tax-report') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('analytics.tax-report') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Tax Report') }}</a>
        @endrole
        <a wire:navigate href="{{ route('reports.sales') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('reports.sales') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Reports') }}</a>
        <a wire:navigate href="{{ route('reports.inventory') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('reports.inventory') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Inventory Report') }}</a>
        <a wire:navigate href="{{ route('reports.expenses') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('reports.expenses') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Expenses Report') }}</a>
        @endrole
    </div>
</div>
@endhasanyrole

<!-- System Dropdown - Only show if user has access to at least one item inside -->
@hasanyrole(['Super Admin', 'Manager'])
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
        <a wire:navigate href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Admin Panel') }}</a>
        <a wire:navigate href="{{ route('admin.users') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.users') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Users') }}</a>
        <a wire:navigate href="{{ route('admin.roles') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.roles') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Roles') }}</a>
        <a wire:navigate href="{{ route('admin.branches') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.branches') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Branches') }}</a>
        <a wire:navigate href="{{ route('admin.audit-logs') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.audit-logs') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Audit Logs') }}</a>
        <a wire:navigate href="{{ route('admin.system-health') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.system-health') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('System Health') }}</a>
        @endrole

        @role(['Super Admin', 'Manager'])
        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-2">{{ __('Settings') }}</div>
        <a wire:navigate href="{{ route('settings.general') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.general') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('General') }}</a>
        <a wire:navigate href="{{ route('settings.receipt') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.receipt') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Receipt') }}</a>
        <a wire:navigate href="{{ route('settings.taxes') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.taxes') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Taxes') }}</a>
        @role('Super Admin')
        <a wire:navigate href="{{ route('settings.payment') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.payment') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Payment Methods') }}</a>
        <a wire:navigate href="{{ route('settings.notifications') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.notifications') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Notifications') }}</a>
        <a wire:navigate href="{{ route('settings.integrations') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.integrations') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Integrations') }}</a>
        <a wire:navigate href="{{ route('settings.api-keys') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.api-keys') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('API Keys') }}</a>
        <a wire:navigate href="{{ route('settings.backup') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.backup') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('Backups') }}</a>
        @endrole
        <a wire:navigate href="{{ route('settings.profile') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('settings.profile') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 border-transparent hover:border-indigo-500' }} ml-2 border-l-2">{{ __('My Profile') }}</a>
        @endrole
    </div>
</div>
@endhasanyrole
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Controls -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Open POS Button -->
                        @role(['Super Admin', 'Manager', 'Cashier'])
                        <a href="{{ route('pos.visual') }}"
                            class="hidden lg:flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            <i class="fas fa-cash-register mr-2"></i>
                            {{ __('Open POS') }}
                        </a>
                        @endrole

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
                    <a wire:navigate href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Dashboard') }}</a>
                    
                    @role(['Super Admin', 'Manager', 'Cashier'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('POS') }}</p>
                        <a href="{{ route('pos.visual') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('pos.visual') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Visual POS') }}</a>
                        <a href="{{ route('pos.minimarket') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('pos.minimarket') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Mini Market POS') }}</a>
                        <a href="{{ route('pos.terminal') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('pos.terminal') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('POS Terminal') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager', 'Cashier', 'Analyst'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Sales') }}</p>
                        <a wire:navigate href="{{ route('sales.sales') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('sales.sales') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Transactions') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager', 'Customer Support'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('People') }}</p>
                        <a wire:navigate href="{{ route('people.customers') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('people.customers') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Customers') }}</a>
                        <a wire:navigate href="{{ route('people.employees') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('people.employees') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Employees') }}</a>
                    </div>
                    @endrole

                    <!-- Management -->
                    <div class="px-3 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-t border-gray-700 mt-2">{{ __('Management') }}</div>
                    
                    @role(['Super Admin', 'Manager', 'Inventory Manager', 'Analyst'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Inventory') }}</p>
                        <a wire:navigate href="{{ route('inventory.products') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('inventory.products') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Products') }}</a>
                        <a wire:navigate href="{{ route('inventory.categories') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('inventory.categories') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Categories') }}</a>
                        <a wire:navigate href="{{ route('inventory.stock') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('inventory.stock') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Stock') }}</a>
                        <a wire:navigate href="{{ route('inventory.emojis') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('inventory.emojis') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Icons') }}</a>
                        <a wire:navigate href="{{ route('inventory.colors') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('inventory.colors') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Colors') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager', 'Analyst'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Suppliers') }}</p>
                        <a wire:navigate href="{{ route('admin.suppliers') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.suppliers') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Suppliers') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Finance') }}</p>
                        <a wire:navigate href="{{ route('finance.transactions') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('finance.transactions') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Income & Expense') }}</a>
                        <a wire:navigate href="{{ route('analytics.cash-bank-records') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.cash-bank-records') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Cash & Bank') }}</a>
                        <a wire:navigate href="{{ route('analytics.accounts-receivable') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.accounts-receivable') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Accounts Receivable') }}</a>
                        <a wire:navigate href="{{ route('analytics.accounts-payable') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.accounts-payable') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Accounts Payable') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager', 'Analyst'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Bookkeeping') }}</p>
                        <a wire:navigate href="{{ route('analytics.chart-of-accounts') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.chart-of-accounts') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Chart of Accounts') }}</a>
                        <a wire:navigate href="{{ route('analytics.journal') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.journal') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Journal Entries') }}</a>
                        <a wire:navigate href="{{ route('analytics.general-ledger') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.general-ledger') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('General Ledger') }}</a>
                        <a wire:navigate href="{{ route('analytics.memo') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.memo') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Memos / Adjustments') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager', 'Analyst'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Analytics') }}</p>
                        <a wire:navigate href="{{ route('analytics.overview') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.overview') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Overview') }}</a>
                        <a wire:navigate href="{{ route('analytics.growth') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.growth') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Growth') }}</a>
                        <a wire:navigate href="{{ route('analytics.profit-loss') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.profit-loss') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('P & L') }}</a>
                        <a wire:navigate href="{{ route('analytics.cash-flow') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.cash-flow') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Cash Flow') }}</a>
                        <a wire:navigate href="{{ route('analytics.balance-sheet') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.balance-sheet') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Balance Sheet') }}</a>
                        <a wire:navigate href="{{ route('analytics.inventory-capital') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.inventory-capital') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Inventory Capital') }}</a>
                        <a wire:navigate href="{{ route('analytics.trial-balance') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.trial-balance') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Trial Balance') }}</a>
                        @role(['Super Admin', 'Manager'])
                        <a wire:navigate href="{{ route('analytics.tax-report') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.tax-report') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Tax Report') }}</a>
                        @endrole
                        <a wire:navigate href="{{ route('reports.sales') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('reports.sales') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Sales Reports') }}</a>
                        <a wire:navigate href="{{ route('reports.inventory') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('reports.inventory') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Inventory Reports') }}</a>
                        <a wire:navigate href="{{ route('reports.expenses') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('reports.expenses') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Expenses Reports') }}</a>
                    </div>
                    @endrole

                    <!-- System -->
                    <div class="px-3 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-t border-gray-700 mt-2">{{ __('System') }}</div>
                    
                    @role('Super Admin')
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Admin') }}</p>
                        <a wire:navigate href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.dashboard') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Admin Panel') }}</a>
                        <a wire:navigate href="{{ route('admin.users') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.users') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Users') }}</a>
                        <a wire:navigate href="{{ route('admin.roles') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.roles') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Roles') }}</a>
                        <a wire:navigate href="{{ route('admin.branches') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.branches') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Branches') }}</a>
                        <a wire:navigate href="{{ route('admin.audit-logs') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.audit-logs') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Audit Logs') }}</a>
                        <a wire:navigate href="{{ route('admin.system-health') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.system-health') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('System Health') }}</a>
                    </div>
                    @endrole

                    @role(['Super Admin', 'Manager'])
                    <div class="space-y-1 pl-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2 mb-1">{{ __('Settings') }}</p>
                        <a wire:navigate href="{{ route('settings.general') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.general') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('General') }}</a>
                        <a wire:navigate href="{{ route('settings.receipt') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.receipt') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Receipt') }}</a>
                        <a wire:navigate href="{{ route('settings.taxes') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.taxes') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Taxes') }}</a>
                        @role('Super Admin')
                        <a wire:navigate href="{{ route('settings.payment') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.payment') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Payment Methods') }}</a>
                        <a wire:navigate href="{{ route('settings.notifications') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.notifications') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Notifications') }}</a>
                        <a wire:navigate href="{{ route('settings.integrations') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.integrations') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Integrations') }}</a>
                        <a wire:navigate href="{{ route('settings.api-keys') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.api-keys') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('API Keys') }}</a>
                        <a wire:navigate href="{{ route('settings.backup') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.backup') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('Backups') }}</a>
                        @endrole
                        <a wire:navigate href="{{ route('settings.profile') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('settings.profile') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-700' }} ml-2">{{ __('My Profile') }}</a>
                    </div>
                    @endrole

                    <div class="pt-4 pb-3 border-t border-gray-700 mt-4">
                        <div class="flex items-center px-5">
                            <div class="flex-shrink-0">
                                <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4f46e5&color=fff" alt="">
                            </div>
                            <div class="ml-3">
                                <div class="text-base font-medium leading-none text-white">{{ auth()->user()->name ?? __('Guest') }}</div>
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
                    title: title || @json(__('Are you sure?')),
                    text: text || @json(__("You won't be able to revert this!")),
                    icon: icon || 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: confirmButtonText || @json(__('Yes, delete it!'))
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