<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Account Codes
    |--------------------------------------------------------------------------
    |
    | These are the default account codes used for automatic journal entries.
    | Update these codes to match your chart of accounts.
    |
    */
    'default_account_codes' => [
        'cash' => '1010',           // Cash account
        'bank' => '1020',           // Bank account
        'accounts_receivable' => '1030', // Accounts Receivable
        'sales_revenue' => '4010',  // Sales Revenue
        'tax_payable' => '2010',    // Tax Payable (VAT/Sales Tax)
        'other_income' => '4020',   // Other Income
        'cost_of_goods_sold' => '5010', // COGS
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Method to Account Mapping
    |--------------------------------------------------------------------------
    |
    | Maps payment methods to their corresponding account codes.
    | This determines which account gets debited when receiving payments.
    |
    */
    'payment_method_accounts' => [
        'cash' => '1010',    // Cash account
        'card' => '1020',    // Bank account (credit/debit cards)
        'qr' => '1020',      // Bank account (QR payments)
        'ewallet' => '1020', // Bank account (e-wallet)
        'bank_transfer' => '1020', // Bank account
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Category to Account Mapping
    |--------------------------------------------------------------------------
    |
    | Maps transaction categories to their corresponding account codes.
    | Add your custom categories here.
    |
    */
    'category_accounts' => [
        // Income categories
        'Sales' => '4010',
        'Service Revenue' => '4011',
        'Other Income' => '4020',
        
        // Expense categories
        'Rent' => '5100',
        'Utilities' => '5200',
        'Salaries' => '5300',
        'Marketing' => '5400',
        'Office Supplies' => '5500',
        'Maintenance' => '5600',
        'Other Expense' => '5900',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-create Journal Entries
    |--------------------------------------------------------------------------
    |
    | Enable/disable automatic journal entry creation from sales and transactions.
    |
    */
    'auto_create_journal_entries' => true,
];
