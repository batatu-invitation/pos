<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Livewire\Livewire;

class CategoriesTest extends TestCase
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

    public function test_admin_can_access_categories_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.categories'));
        $response->assertStatus(200);
        $response->assertSee('Categories');
    }

    public function test_categories_page_contains_livewire_component()
    {
        $this->actingAs($this->adminUser)
            ->get(route('inventory.categories'))
            ->assertSeeLivewire('inventory.categories');
    }

    public function test_categories_page_avoid_n_plus_one_queries()
    {
        // Create categories
        Category::factory()->count(10)->create();

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)->get(route('inventory.categories'));

        $queryCount = count(DB::getQueryLog());

        // With count of products, it should use withCount which is 1 query (subquery) or similar, not N+1
        $this->assertLessThan(10, $queryCount, "Query count $queryCount indicates potential N+1 problem on Categories page");
    }

    public function test_categories_ui_elements_presence()
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.categories'));
        
        $response->assertSee('Total Categories');
        $response->assertSee('Add Category');
    }
}
