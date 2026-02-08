<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by module
        $permissions = [
            'dashboard' => ['view'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'categories' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'adjust', 'transfer'],
            'suppliers' => ['view', 'create', 'edit', 'delete'],
            'customers' => ['view', 'create', 'edit', 'delete'],
            'sales' => ['view', 'create', 'void'],
            'reports' => ['view_sales', 'view_inventory', 'view_expenses'],
            'settings' => ['view', 'edit_general', 'edit_system'],
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}_{$action}"]);
            }
        }

        // Assign all permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
        }

        // Assign basic permissions to Manager (Example)
        $manager = Role::where('name', 'Manager')->first();
        if ($manager) {
            $manager->givePermissionTo(Permission::where('name', 'like', 'products_%')
                ->orWhere('name', 'like', 'inventory_%')
                ->orWhere('name', 'like', 'sales_%')
                ->orWhere('name', 'like', 'reports_%')
                ->get());
        }
    }
}
