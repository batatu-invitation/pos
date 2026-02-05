<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Color;
use App\Models\User;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'superadmin@example.com')->first();

        $colors = [
            ['class' => 'bg-orange-100', 'name' => 'Orange'],
            ['class' => 'bg-blue-100', 'name' => 'Blue'],
            ['class' => 'bg-pink-100', 'name' => 'Pink'],
            ['class' => 'bg-purple-100', 'name' => 'Purple'],
            ['class' => 'bg-yellow-100', 'name' => 'Yellow'],
            ['class' => 'bg-amber-100', 'name' => 'Amber'],
            ['class' => 'bg-red-100', 'name' => 'Red'],
            ['class' => 'bg-green-100', 'name' => 'Green'],
            ['class' => 'bg-rose-100', 'name' => 'Rose'],
            ['class' => 'bg-cyan-100', 'name' => 'Cyan'],
            ['class' => 'bg-sky-100', 'name' => 'Sky'],
            ['class' => 'bg-indigo-100', 'name' => 'Indigo'],
            ['class' => 'bg-teal-100', 'name' => 'Teal'],
            ['class' => 'bg-gray-100', 'name' => 'Gray'],
            ['class' => 'bg-gray-200', 'name' => 'Dark Gray'],
        ];

        foreach ($colors as $data) {
            Color::firstOrCreate(
                ['class' => $data['class']],
                [
                    'name' => $data['name'],
                    // 'tenant_id' => 'default'
                    'user_id' => $user?->id,
                ]
            );
        }
    }
}
