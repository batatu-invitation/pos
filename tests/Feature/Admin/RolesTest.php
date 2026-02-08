<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Livewire\Livewire;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin role and user
        Role::create(['name' => 'Super Admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');
    }

    public function test_admin_can_access_roles_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles'));
        $response->assertStatus(200);
        $response->assertSee('Roles');
    }

    public function test_roles_page_contains_livewire_component()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.roles'))
            ->assertSeeLivewire('admin.roles');
    }

    public function test_roles_page_avoid_n_plus_one_queries()
    {
        // Create roles and permissions
        $permissions = Permission::create(['name' => 'test permission']);
        $roles = collect();
        for ($i = 0; $i < 10; $i++) {
            $role = Role::create(['name' => "Role $i"]);
            $role->givePermissionTo($permissions);
            $roles->push($role);
        }

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)->get(route('admin.roles'));

        $queryCount = count(DB::getQueryLog());

        // We expect eager loading of permissions
        // N+1 would be 10 roles * 1 permission query = 10 queries
        // Optimized: 1 roles + 1 permissions (IN) = ~2 queries
        
        $this->assertLessThan(10, $queryCount, "Query count $queryCount indicates potential N+1 problem on Roles page");
    }

    public function test_roles_ui_elements_presence()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles'));
        
        $response->assertSee('Total Roles');
        $response->assertSee('Create New Role');
    }
}
