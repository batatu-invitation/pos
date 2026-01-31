<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::create([
            'type' => 'income',
            'amount' => 15000000.00,
            'category' => 'Sales',
            'description' => 'Weekly Sales Revenue',
            'date' => Carbon::now()->subDays(1),
            'payment_method' => 'Bank Transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'type' => 'expense',
            'amount' => 5000000.00,
            'category' => 'Rent',
            'description' => 'Monthly Store Rent',
            'date' => Carbon::now()->startOfMonth(),
            'payment_method' => 'Bank Transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'type' => 'expense',
            'amount' => 2500000.00,
            'category' => 'Utilities',
            'description' => 'Electricity and Water Bill',
            'date' => Carbon::now()->subDays(5),
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        
        Transaction::create([
            'type' => 'expense',
            'amount' => 1200000.00,
            'category' => 'Inventory',
            'description' => 'Restocking Soda',
            'date' => Carbon::now()->subDays(2),
            'payment_method' => 'Credit Card',
            'status' => 'completed',
        ]);
    }
}
