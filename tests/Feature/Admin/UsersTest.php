<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Livewire\Livewire;

class UsersTest extends TestCase
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

    public function test_admin_can_access_users_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users'));
        $response->assertStatus(200);
        $response->assertSee('Users');
    }

    public function test_users_page_contains_livewire_component()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.users'))
            ->assertSeeLivewire('admin.users');
    }

    public function test_users_page_avoid_n_plus_one_queries()
    {
        // Create some users with roles
        $users = User::factory()->count(10)->create();
        $role = Role::create(['name' => 'Editor']);
        
        foreach ($users as $user) {
            $user->assignRole('Editor');
        }

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)->get(route('admin.users'));

        $queryCount = count(DB::getQueryLog());

        // Assert that the query count is reasonable (e.g., less than 10 for basic load + pagination + auth)
        // With N+1, it would be 10 (users) * queries per user (roles) + base queries
        // Without N+1, it should be constant regardless of user count (e.g. 1 for users, 1 for roles via eager load)
        
        // We expect roughly: 
        // 1. Auth check
        // 2. Count total users
        // 3. Count active/role users (for stats)
        // 4. Get paginated users
        // 5. Get roles for these users (eager loaded)
        
        $this->assertLessThan(15, $queryCount, "Query count $queryCount indicates potential N+1 problem");
    }

    public function test_users_ui_elements_presence()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users'));
        
        $response->assertSee('Total Users');
        $response->assertSee('New User');
        $response->assertSee('Export');
    }
}
