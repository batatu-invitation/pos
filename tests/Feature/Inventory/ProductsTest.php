<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Livewire\Livewire;

class ProductsTest extends TestCase
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

    public function test_admin_can_access_products_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.products'));
        $response->assertStatus(200);
        $response->assertSee('Products');
    }

    public function test_products_page_contains_livewire_component()
    {
        $this->actingAs($this->adminUser)
            ->get(route('inventory.products'))
            ->assertSeeLivewire('inventory.products');
    }

    public function test_products_page_avoid_n_plus_one_queries()
    {
        // Create dependencies
        $category = Category::create(['name' => 'Test Category']);
        
        // Create products
        Product::factory()->count(10)->create([
            'category_id' => $category->id
        ]);

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)->get(route('inventory.products'));

        $queryCount = count(DB::getQueryLog());

        // We expect efficient querying.
        // N+1 would be 10 products * (1 category + 1 supplier) = 20 extra queries.
        // Eager loaded: 1 products + 1 categories (IN) + 1 suppliers (IN) = ~3 main queries.
        
        $this->assertLessThan(15, $queryCount, "Query count $queryCount indicates potential N+1 problem on Products page");
    }

    public function test_products_ui_elements_presence()
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.products'));
        
        $response->assertSee('Total Products');
        $response->assertSee('Low Stock');
        $response->assertSee('Add Product');
    }
}
