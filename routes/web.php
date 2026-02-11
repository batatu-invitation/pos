<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome');
        });
    });
    Route::middleware(['auth', 'check.setup'])->prefix('dashboard')->group(function () {
        Route::get('lang/{locale}', function ($locale) {
            if (in_array($locale, ['en', 'id'])) {
                session(['locale' => $locale]);
            }
            return redirect()->back();
        })->name('lang.switch');
        Route::get('/setup', App\Livewire\Setup::class)->name('setup');
        Volt::route('/', 'dashboard')->name('dashboard');
        Volt::route('/settings/profile', 'settings.profile')->name('settings.profile');
        Volt::route('/profile', 'settings.profile')->name('profile');

        // Super Admin Exclusive
        Route::middleware(['role:Super Admin'])->group(function () {
            Volt::route('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
            Volt::route('/admin/users', 'admin.users')->name('admin.users');
            Volt::route('/admin/roles', 'admin.roles')->name('admin.roles');
            Volt::route('/admin/branches', 'admin.branches')->name('admin.branches');
            Volt::route('/admin/audit-logs', 'admin.audit-logs')->name('admin.audit-logs');
            Volt::route('/admin/system-health', 'admin.system-health')->name('admin.system-health');
            Volt::route('/settings/payment', 'settings.payment')->name('settings.payment');
            Volt::route('/settings/notifications', 'settings.notifications')->name('settings.notifications');
            Volt::route('/settings/integrations', 'settings.integrations')->name('settings.integrations');
            Volt::route('/settings/api-keys', 'settings.api-keys')->name('settings.api-keys');
            Volt::route('/settings/backup', 'settings.backup')->name('settings.backup');
            Volt::route('/settings/taxes', 'settings.taxes')->name('settings.taxes');
        });

        // Inventory Group
        Route::middleware(['role:Super Admin|Manager|Inventory Manager|Analyst'])->group(function () {
            Volt::route('/inventory/products', 'inventory.products')->name('inventory.products');
            Volt::route('/inventory/categories', 'inventory.categories')->name('inventory.categories');
            Volt::route('/inventory/stock', 'inventory.stock')->name('inventory.stock');
            Volt::route('/inventory/emojis', 'inventory.emojis')->name('inventory.emojis');
            Volt::route('/inventory/colors', 'inventory.colors')->name('inventory.colors');
        });

        // Suppliers
        Route::middleware(['role:Super Admin|Manager|Analyst'])->group(function () {
            Volt::route('/admin/suppliers', 'admin.suppliers')->name('admin.suppliers');
        });

        // POS (Super Admin, Manager, Cashier)
        Route::middleware(['role:Super Admin|Manager|Cashier'])->group(function () {
            Volt::route('/pos/visual', 'pos.visual')->name('pos.visual');
            Volt::route('/pos/minimarket', 'pos.minimarket')->name('pos.minimarket');
            Volt::route('/pos/terminal', 'pos.terminal')->name('pos.terminal');
            Volt::route('/pos/payment', 'pos.payment')->name('pos.payment');
            Volt::route('/pos/receipt', 'pos.receipt')->name('pos.receipt');
            Route::get('/pos/receipt/{sale}/print', [ReceiptController::class, 'print'])->name('pos.receipt.print');
        });

        // Sales Transactions (Includes Analyst)
        Route::middleware(['role:Super Admin|Manager|Cashier|Analyst'])->group(function () {
            Volt::route('/sales/sales', 'sales.sales')->name('sales.sales');
        });

        // People
        Route::middleware(['role:Super Admin|Manager|Customer Support'])->group(function () {
            Volt::route('/people/customers', 'people.customers')->name('people.customers');
            Volt::route('/people/employees', 'people.employees')->name('people.employees');
        });

        // Analytics & Reports
        Route::middleware(['role:Super Admin|Manager|Analyst'])->group(function () {
            Volt::route('/analytics/growth', 'analytics.growth')->name('analytics.growth');
            Volt::route('/analytics/overview', 'analytics.overview')->name('analytics.overview');
            
            // Financial Statements
            Volt::route('/analytics/profit-loss', 'analytics.profit-loss')->name('analytics.profit-loss');
            Volt::route('/analytics/balance-sheet', 'analytics.balance-sheet')->name('analytics.balance-sheet');
            Volt::route('/analytics/trial-balance', 'analytics.trial-balance')->name('analytics.trial-balance');
            
            // Financial Records
            Volt::route('/analytics/cash-bank-records', 'analytics.cash-bank-records')->name('analytics.cash-bank-records');
            Volt::route('/analytics/accounts-receivable', 'analytics.accounts-receivable')->name('analytics.accounts-receivable');
            Volt::route('/analytics/accounts-payable', 'analytics.accounts-payable')->name('analytics.accounts-payable');
            
            // Bookkeeping
            Volt::route('/analytics/chart-of-accounts', 'accounting.chart-of-accounts')->name('analytics.chart-of-accounts');
            Volt::route('/analytics/journal', 'analytics.journal')->name('analytics.journal');
            Volt::route('/analytics/general-ledger', 'analytics.general-ledger')->name('analytics.general-ledger');
            Volt::route('/analytics/memo', 'analytics.memo')->name('analytics.memo');

            Volt::route('/analytics/cash-flow', 'analytics.cash-flow')->name('analytics.cash-flow');
            Route::get('/analytics/inventory-capital', \App\Livewire\Analytics\InventoryCapital::class)->name('analytics.inventory-capital');

            Volt::route('/reports/sales', 'reports.report-sales')->name('reports.sales');
            Volt::route('/reports/inventory', 'reports.report-inventory')->name('reports.inventory');
            Volt::route('/reports/expenses', 'reports.report-expenses')->name('reports.expenses');
        });

        // Finance & Tax & General Settings
        Route::middleware(['role:Super Admin|Manager'])->group(function () {
            Volt::route('/finance/transactions', 'finance.transactions')->name('finance.transactions');
            Volt::route('/analytics/tax-report', 'analytics.tax-report')->name('analytics.tax-report');
            Volt::route('/settings/general', 'settings.general')->name('settings.general');
            Volt::route('/settings/receipt', 'settings.receipt')->name('settings.receipt');
        });
    });
}

require __DIR__ . '/auth.php';
