<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class DefaultAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1010', 'name' => 'Cash', 'type' => 'asset', 'subtype' => 'current_asset', 'description' => 'Cash on hand'],
            ['code' => '1020', 'name' => 'Bank Account', 'type' => 'asset', 'subtype' => 'current_asset', 'description' => 'Bank deposits and checking accounts'],
            ['code' => '1030', 'name' => 'Accounts Receivable', 'type' => 'asset', 'subtype' => 'current_asset', 'description' => 'Money owed by customers'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'subtype' => 'current_asset', 'description' => 'Goods for sale'],
            
            // Liabilities
            ['code' => '2010', 'name' => 'Tax Payable', 'type' => 'liability', 'subtype' => 'current_liability', 'description' => 'Sales tax and VAT payable'],
            ['code' => '2020', 'name' => 'Accounts Payable', 'type' => 'liability', 'subtype' => 'current_liability', 'description' => 'Money owed to suppliers'],
            
            // Equity
            ['code' => '3010', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'subtype' => 'equity', 'description' => 'Owner\'s capital'],
            ['code' => '3020', 'name' => 'Retained Earnings', 'type' => 'equity', 'subtype' => 'equity', 'description' => 'Accumulated profits'],
            
            // Revenue/Income
            ['code' => '4010', 'name' => 'Sales Revenue', 'type' => 'revenue', 'subtype' => 'operating_revenue', 'description' => 'Revenue from product sales'],
            ['code' => '4011', 'name' => 'Service Revenue', 'type' => 'revenue', 'subtype' => 'operating_revenue', 'description' => 'Revenue from services'],
            ['code' => '4020', 'name' => 'Other Income', 'type' => 'revenue', 'subtype' => 'non_operating_revenue', 'description' => 'Miscellaneous income'],
            
            // Cost of Goods Sold
            ['code' => '5010', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'subtype' => 'cost_of_sales', 'description' => 'Direct costs of products sold'],
            
            // Operating Expenses
            ['code' => '5100', 'name' => 'Rent Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Rent and lease payments'],
            ['code' => '5200', 'name' => 'Utilities Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Electricity, water, internet'],
            ['code' => '5300', 'name' => 'Salaries Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Employee salaries and wages'],
            ['code' => '5400', 'name' => 'Marketing Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Advertising and marketing costs'],
            ['code' => '5500', 'name' => 'Office Supplies Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Office supplies and materials'],
            ['code' => '5600', 'name' => 'Maintenance Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Repairs and maintenance'],
            ['code' => '5900', 'name' => 'Other Expense', 'type' => 'expense', 'subtype' => 'operating_expense', 'description' => 'Miscellaneous expenses'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                array_merge($account, ['is_active' => true])
            );
        }

        $this->command->info('Default chart of accounts created successfully!');
    }
}
