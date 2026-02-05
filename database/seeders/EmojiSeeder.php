<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Emoji;
use App\Models\User;

class EmojiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'superadmin@example.com')->first();

        $emojis = [
            ['icon' => '🍔', 'name' => 'Burger'],
            ['icon' => '🥤', 'name' => 'Drink'],
            ['icon' => '🍰', 'name' => 'Cake'],
            ['icon' => '🔌', 'name' => 'Plug'],
            ['icon' => '🍿', 'name' => 'Popcorn'],
            ['icon' => '☕', 'name' => 'Coffee'],
            ['icon' => '🍎', 'name' => 'Apple'],
            ['icon' => '🥦', 'name' => 'Broccoli'],
            ['icon' => '🥩', 'name' => 'Meat'],
            ['icon' => '🦐', 'name' => 'Shrimp'],
            ['icon' => '🥐', 'name' => 'Croissant'],
            ['icon' => '🍦', 'name' => 'Ice Cream'],
            ['icon' => '🏠', 'name' => 'House'],
            ['icon' => '✏️', 'name' => 'Pencil'],
            ['icon' => '📝', 'name' => 'Memo'],
        ];

        foreach ($emojis as $data) {
            Emoji::firstOrCreate(
                ['icon' => $data['icon']],
                [
                    'name' => $data['name'],
                    // 'tenant_id' => 'default' // Optional: set a default tenant or leave null for global
                    'user_id' => $user?->id,
                ]
            );
        }
    }
}
