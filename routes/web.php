<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->middleware('auth')->group(function () {
        Route::get('/', function () {
           return view('welcome');
        });




        // your actual routes
    });
    Route::middleware('auth')->group(function () {
        Volt::route('/dashboard', 'dashboard')->name('dashboard');

        // Admin Routes
        Volt::route('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
        Volt::route('/admin/users', 'admin.users')->name('admin.users');
        Volt::route('/admin/roles', 'admin.roles')->name('admin.roles');
        Volt::route('/admin/branches', 'admin.branches')->name('admin.branches');
        Volt::route('/admin/audit-logs', 'admin.audit-logs')->name('admin.audit-logs');
        Volt::route('/admin/system-health', 'admin.system-health')->name('admin.system-health');
        Volt::route('/pos/visual', 'pos.visual')->name('pos.visual');
        Volt::route('/pos/minimarket', 'pos.minimarket')->name('pos.minimarket');
        Volt::route('/pos/terminal', 'pos.terminal')->name('pos.terminal');
        Volt::route('/pos/payment', 'pos.payment')->name('pos.payment');
        Volt::route('/pos/receipt', 'pos.receipt')->name('pos.receipt');
        Route::get('/pos/receipt/{sale}/print', [ReceiptController::class, 'print'])->name('pos.receipt.print');
        Volt::route('/analytics/growth', 'analytics.growth')->name('analytics.growth');
        Volt::route('/analytics/overview', 'analytics.overview')->name('analytics.overview');
        Volt::route('/analytics/profit-loss', 'analytics.profit-loss')->name('analytics.profit-loss');
        Volt::route('/analytics/cash-flow', 'analytics.cash-flow')->name('analytics.cash-flow');
        Volt::route('/analytics/tax-report', 'analytics.tax-report')->name('analytics.tax-report');
        Volt::route('/inventory/products', 'inventory.products')->name('inventory.products');
        Volt::route('/inventory/categories', 'inventory.categories')->name('inventory.categories');
        Volt::route('/inventory/stock', 'inventory.stock')->name('inventory.stock');
        Volt::route('/inventory/emojis', 'inventory.emojis')->name('inventory.emojis');
        Volt::route('/inventory/colors', 'inventory.colors')->name('inventory.colors');
        Volt::route('/sales/sales', 'sales.sales')->name('sales.sales');
        Volt::route('/people/customers', 'people.customers')->name('people.customers');
        Volt::route('/people/employees', 'people.employees')->name('people.employees');
        Volt::route('/settings/profile', 'settings.profile')->name('settings.profile');
        Volt::route('/settings/general', 'settings.general')->name('settings.general');
        Volt::route('/settings/payment', 'settings.payment')->name('settings.payment');
        Volt::route('/settings/receipt', 'settings.receipt')->name('settings.receipt');
        Volt::route('/settings/notifications', 'settings.notifications')->name('settings.notifications');
        Volt::route('/settings/integrations', 'settings.integrations')->name('settings.integrations');
        Volt::route('/settings/api-keys', 'settings.api-keys')->name('settings.api-keys');
        Volt::route('/settings/backup', 'settings.backup')->name('settings.backup');
        Volt::route('/settings/taxes', 'settings.taxes')->name('settings.taxes');
        Volt::route('/profile', 'settings.profile')->name('profile');

        // Reports Routes
        Volt::route('/reports/sales', 'reports.report-sales')->name('reports.sales');
        Volt::route('/reports/inventory', 'reports.report-inventory')->name('reports.inventory');
        Volt::route('/reports/expenses', 'reports.report-expenses')->name('reports.expenses');
    });
}

require __DIR__ . '/auth.php';
