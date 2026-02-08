<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Livewire\Livewire;

class SuppliersTest extends TestCase
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

    public function test_admin_can_access_suppliers_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.suppliers'));
        $response->assertStatus(200);
        $response->assertSee('Suppliers');
    }

    public function test_suppliers_page_contains_livewire_component()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.suppliers'))
            ->assertSeeLivewire('admin.suppliers');
    }

    public function test_suppliers_page_avoid_n_plus_one_queries()
    {
        // Create suppliers
        Supplier::factory()->count(10)->create();

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)->get(route('admin.suppliers'));

        $queryCount = count(DB::getQueryLog());

        // Simple table load, should be very few queries
        $this->assertLessThan(10, $queryCount, "Query count $queryCount indicates potential N+1 problem on Suppliers page");
    }

    public function test_suppliers_ui_elements_presence()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.suppliers'));
        
        $response->assertSee('Total Suppliers');
        $response->assertSee('Active Suppliers');
        $response->assertSee('Add Supplier');
    }
}
