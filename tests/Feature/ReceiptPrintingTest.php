<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_printing_sends_to_printer_and_redirects()
    {
        // Create user manually since factory definition might mismatch migration
        $user = new User();
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->email = 'testuser@example.com';
        $user->password = bcrypt('password');
        $user->save();

        // Create customer (if factory exists, use it, otherwise create manually)
        $customer = new Customer();
        $customer->name = 'Test Customer';
        $customer->email = 'customer@test.com';
        $customer->phone = '123456789';
        $customer->save();

        // Create product
        $product = new Product();
        $product->name = 'Test Product';
        $product->sku = 'TEST-SKU';
        $product->price = 10000;
        $product->stock = 100;
        $product->user_id = $user->id;
        $product->save();

        // Create sale
        $sale = new Sale();
        $sale->invoice_number = 'INV-TEST-001';
        $sale->user_id = $user->id;
        $sale->customer_id = $customer->id;
        $sale->subtotal = 10000;
        $sale->tax = 1000;
        $sale->discount = 0;
        $sale->total_amount = 11000;
        $sale->cash_received = 20000;
        $sale->change_amount = 9000;
        $sale->payment_method = 'cash';
        $sale->status = 'completed';
        $sale->save();

        // Create sale item
        $item = new SaleItem();
        $item->sale_id = $sale->id;
        $item->product_id = $product->id;
        $item->product_name = $product->name;
        $item->price = $product->price;
        $item->quantity = 1;
        $item->total_price = 10000;
        $item->save();

        // Act
        // Request as JSON to get a clear JSON response, or standard for redirect
        // Let's test standard redirect flow as implied by typical controller usage
        $response = $this->actingAs($user)
            ->get(route('pos.receipt.print', $sale->id));

        // Assert
        // Expect a redirect back with success message
        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Receipt sent to printer');
    }
}
