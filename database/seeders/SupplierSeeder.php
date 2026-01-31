<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'name' => 'PT. Food Supply Indonesia',
            'contact_person' => 'Budi Santoso',
            'email' => 'budi@foodsupply.id',
            'phone' => '081234567890',
            'address' => 'Jl. Industri No. 1, Jakarta',
            'status' => 'Active',
        ]);

        Supplier::create([
            'name' => 'CV. Berkah Makmur',
            'contact_person' => 'Siti Aminah',
            'email' => 'siti@berkah.com',
            'phone' => '081987654321',
            'address' => 'Jl. Pasar Besar No. 45, Surabaya',
            'status' => 'Active',
        ]);

        Supplier::create([
            'name' => 'Global Beverages Ltd',
            'contact_person' => 'Michael Chen',
            'email' => 'michael@globalbev.com',
            'phone' => '0215551234',
            'address' => 'Kawasan Industri Cikarang Blok B2',
            'status' => 'Active',
        ]);
    }
}
