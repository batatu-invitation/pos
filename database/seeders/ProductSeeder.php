<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Double Burger', 'sku' => 'BUR-001', 'category' => 'Food', 'price' => 12.00, 'stock' => 120, 'status' => 'Active', 'icon' => '🍔'],
            ['name' => 'Cola Zero', 'sku' => 'DRK-001', 'category' => 'Drinks', 'price' => 3.00, 'stock' => 50, 'status' => 'Active', 'icon' => '🥤'],
            ['name' => 'Cheese Burger', 'sku' => 'BUR-002', 'category' => 'Food', 'price' => 10.00, 'stock' => 100, 'status' => 'Active', 'icon' => '🍔'],
            ['name' => 'Chicken Nuggets', 'sku' => 'FOD-001', 'category' => 'Food', 'price' => 8.00, 'stock' => 60, 'status' => 'Active', 'icon' => '🍗'],
            ['name' => 'French Fries', 'sku' => 'SNK-001', 'category' => 'Food', 'price' => 4.00, 'stock' => 80, 'status' => 'Active', 'icon' => '🍟'],
            ['name' => 'Vanilla Shake', 'sku' => 'DRK-002', 'category' => 'Drinks', 'price' => 5.00, 'stock' => 40, 'status' => 'Active', 'icon' => '🥤'],
            ['name' => 'Chocolate Cake', 'sku' => 'DES-001', 'category' => 'Desserts', 'price' => 6.00, 'stock' => 20, 'status' => 'Active', 'icon' => '🍰'],
            ['name' => 'Coffee', 'sku' => 'DRK-003', 'category' => 'Drinks', 'price' => 3.50, 'stock' => 100, 'status' => 'Active', 'icon' => '☕'],
            ['name' => 'Tea', 'sku' => 'DRK-004', 'category' => 'Drinks', 'price' => 3.00, 'stock' => 100, 'status' => 'Active', 'icon' => '🍵'],
            ['name' => 'Salad', 'sku' => 'FOD-002', 'category' => 'Food', 'price' => 9.00, 'stock' => 30, 'status' => 'Active', 'icon' => '🥗'],
            ['name' => 'Pizza Slice', 'sku' => 'FOD-003', 'category' => 'Food', 'price' => 5.00, 'stock' => 40, 'status' => 'Active', 'icon' => '🍕'],
            ['name' => 'Ice Cream', 'sku' => 'DES-002', 'category' => 'Desserts', 'price' => 4.50, 'stock' => 50, 'status' => 'Active', 'icon' => '🍦'],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            // Fallback if category not seeded yet (though CategorySeeder should run first)
            if (!$category) {
                 $category = Category::create([
                    'name' => $item['category'],
                    'icon' => '📝',
                    'color' => 'bg-gray-100'
                ]);
            }

            Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'category_id' => $category->id,
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'status' => $item['status'],
                    'icon' => $item['icon'],
                ]
            );
        }
    }
}
