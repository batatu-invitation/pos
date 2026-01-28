<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Food' => ['icon' => '🍔', 'color' => 'bg-orange-100', 'description' => 'Delicious food items'],
            'Drinks' => ['icon' => '🥤', 'color' => 'bg-blue-100', 'description' => 'Refreshing beverages'],
            'Desserts' => ['icon' => '🍰', 'color' => 'bg-pink-100', 'description' => 'Sweet treats'],
            'Electronics' => ['icon' => '🔌', 'color' => 'bg-purple-100', 'description' => 'Gadgets and devices'],
            'Snacks' => ['icon' => '🍿', 'color' => 'bg-yellow-100', 'description' => 'Light bites'],
            'Beverages' => ['icon' => '☕', 'color' => 'bg-amber-100', 'description' => 'Hot drinks'],
            'Fruits' => ['icon' => '🍎', 'color' => 'bg-red-100', 'description' => 'Fresh fruits'],
            'Vegetables' => ['icon' => '🥦', 'color' => 'bg-green-100', 'description' => 'Fresh vegetables'],
            'Meats' => ['icon' => '🥩', 'color' => 'bg-rose-100', 'description' => 'Fresh meat'],
            'Seafoods' => ['icon' => '🦐', 'color' => 'bg-cyan-100', 'description' => 'Fresh seafood'],
            'Bakery' => ['icon' => '🥐', 'color' => 'bg-sky-100', 'description' => 'Baked goods'],
            'Frozen' => ['icon' => '🍦', 'color' => 'bg-indigo-100', 'description' => 'Frozen foods'],
            'Households' => ['icon' => '🏠', 'color' => 'bg-teal-100', 'description' => 'Household items'],
            'Stationery' => ['icon' => '✏️', 'color' => 'bg-gray-100', 'description' => 'Office supplies'],
            'Others' => ['icon' => '📝', 'color' => 'bg-gray-200', 'description' => 'Miscellaneous items'],
        ];

        foreach ($categories as $name => $data) {
            Category::firstOrCreate(
                ['name' => $name],
                [
                    'icon' => $data['icon'],
                    'color' => $data['color'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
