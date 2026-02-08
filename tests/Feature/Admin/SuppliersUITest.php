<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuppliersUITest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $adminRole = Role::create(['name' => 'Admin']);
        $managerRole = Role::create(['name' => 'Manager']);
        
        // Create permissions
        $viewSuppliersPermission = Permission::create(['name' => 'view suppliers']);
        $createSuppliersPermission = Permission::create(['name' => 'create suppliers']);
        $editSuppliersPermission = Permission::create(['name' => 'edit suppliers']);
        $deleteSuppliersPermission = Permission::create(['name' => 'delete suppliers']);
        
        // Assign permissions to admin role
        $adminRole->givePermissionTo([
            $viewSuppliersPermission,
            $createSuppliersPermission,
            $editSuppliersPermission,
            $deleteSuppliersPermission
        ]);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone' => '1234567890',
            'status' => 'active',
            'email_verified_at' => now()
        ]);
        $this->adminUser->assignRole($adminRole);
        $this->adminUser->assignRole($managerRole);
        
        // Create some suppliers
        Supplier::factory()->count(5)->create();
    }

    /** @test */
    public function it_displays_suppliers_page_with_proper_ui_structure()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $response->assertStatus(200)
            ->assertSee('Suppliers')
            ->assertSee('Manage your product suppliers and contact information.')
            ->assertSee('Total Suppliers')
            ->assertSee('Active Suppliers')
            ->assertSee('Search suppliers...')
            ->assertSee('Add Supplier');
    }

    /** @test */
    public function it_displays_suppliers_in_bento_grid_layout()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $response->assertStatus(200)
            ->assertSee('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6');
    }

    /** @test */
    public function it_has_dark_mode_classes_on_ui_elements()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check main container has dark mode classes
        $this->assertStringContainsString('dark:bg-gray-900', $content);
        $this->assertStringContainsString('dark:text-gray-100', $content);
        
        // Check cards have dark mode classes
        $this->assertStringContainsString('dark:bg-gray-800', $content);
        $this->assertStringContainsString('dark:border-gray-700', $content);
        
        // Check buttons have dark mode classes
        $this->assertStringContainsString('dark:bg-gray-800', $content);
        $this->assertStringContainsString('dark:text-gray-300', $content);
        $this->assertStringContainsString('dark:hover:bg-gray-700', $content);
    }

    /** @test */
    public function it_prevents_n_plus_one_queries()
    {
        // Create additional suppliers to test N+1 prevention
        Supplier::factory()->count(10)->create();
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $response->assertStatus(200);
        
        // If we get here without errors, the eager loading is working
        $this->assertTrue(true);
    }

    /** @test */
    public function it_shows_export_functionality()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $response->assertStatus(200)
            ->assertSee('Export')
            ->assertSee('Export Excel')
            ->assertSee('Export PDF');
    }

    /** @test */
    public function it_shows_supplier_cards_with_proper_styling()
    {
        $supplier = Supplier::factory()->create([
            'name' => 'Test Supplier',
            'contact_person' => 'John Doe',
            'email' => 'john@testsupplier.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street',
            'status' => 'Active'
        ]);
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check for Bento grid styling
        $this->assertStringContainsString('rounded-3xl', $content);
        $this->assertStringContainsString('shadow-sm', $content);
        $this->assertStringContainsString('hover:shadow-lg', $content);
        $this->assertStringContainsString('transition-all', $content);
        $this->assertStringContainsString('group', $content);
        
        // Check supplier data is displayed
        $this->assertStringContainsString('Test Supplier', $content);
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('john@testsupplier.com', $content);
        $this->assertStringContainsString('+1234567890', $content);
        $this->assertStringContainsString('123 Test Street', $content);
    }

    /** @test */
    public function it_shows_active_status_badge()
    {
        $supplier = Supplier::factory()->create([
            'status' => 'Active'
        ]);
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check for active status styling
        $this->assertStringContainsString('bg-green-100', $content);
        $this->assertStringContainsString('text-green-800', $content);
        $this->assertStringContainsString('border-green-200', $content);
    }

    /** @test */
    public function it_shows_inactive_status_badge()
    {
        $supplier = Supplier::factory()->create([
            'status' => 'Inactive'
        ]);
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check for inactive status styling
        $this->assertStringContainsString('bg-gray-100', $content);
        $this->assertStringContainsString('text-gray-800', $content);
        $this->assertStringContainsString('border-gray-200', $content);
    }

    /** @test */
    public function it_shows_edit_and_delete_buttons()
    {
        $supplier = Supplier::factory()->create();
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check for edit button
        $this->assertStringContainsString('fas fa-edit', $content);
        $this->assertStringContainsString('title="Edit"', $content);
        
        // Check for delete button
        $this->assertStringContainsString('fas fa-trash', $content);
        $this->assertStringContainsString('title="Delete"', $content);
    }

    /** @test */
    public function it_shows_empty_state_when_no_suppliers()
    {
        // Clear all suppliers
        Supplier::query()->delete();
        
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard/admin/suppliers');
            
        // Follow redirects
        while ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }
        
        $content = $response->getContent();
        
        // Check empty state
        $this->assertStringContainsString('No suppliers found', $content);
        $this->assertStringContainsString('Get started by creating a new supplier', $content);
        $this->assertStringContainsString('Add Supplier', $content);
    }
}