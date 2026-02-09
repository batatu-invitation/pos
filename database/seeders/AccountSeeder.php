<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1001', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true],
            ['code' => '1002', 'name' => 'Bank', 'type' => 'asset', 'is_active' => true],
            ['code' => '1003', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => true],
            ['code' => '1004', 'name' => 'Inventory', 'type' => 'asset', 'is_active' => true],
            
            // Liabilities
            ['code' => '2001', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true],
            ['code' => '2002', 'name' => 'Tax Payable', 'type' => 'liability', 'is_active' => true],
            
            // Equity
            ['code' => '3001', 'name' => 'Capital', 'type' => 'equity', 'is_active' => true],
            ['code' => '3002', 'name' => 'Retained Earnings', 'type' => 'equity', 'is_active' => true],
            
            // Income
            ['code' => '4001', 'name' => 'Sales Revenue', 'type' => 'revenue', 'is_active' => true],
            
            // Expenses
            ['code' => '5001', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_active' => true],
            ['code' => '5002', 'name' => 'Rent Expense', 'type' => 'expense', 'is_active' => true],
            ['code' => '5003', 'name' => 'Utilities Expense', 'type' => 'expense', 'is_active' => true],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(['code' => $account['code']], $account);
        }
    }
}
