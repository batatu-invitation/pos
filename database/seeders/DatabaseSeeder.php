<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $roles = [
            'Super Admin',
            'Admin',
            'Manager',
            'Cashier',
            'Inventory Manager',
            'Customer Support',
            'Analyst',
            'Developer',
            'Editor',
            'Viewer',
        ];

        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }

        // Create Super Admin User
        $superAdmin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'Super Admin', // Keeping the legacy column for now as well
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $superAdmin->assignRole('Super Admin');
    }
}
