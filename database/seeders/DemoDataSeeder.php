<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BalanceHistory;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have users
        $users = User::all();
        if ($users->count() === 0) {
            User::factory()->count(5)->create();
            $users = User::all();
        }

        // 1. Customers
        $customers = Customer::factory()->count(100)->create();

        // 2. Suppliers
        $suppliers = Supplier::factory()->count(100)->create();

        // 3. Products
        Product::factory()->count(100)->create();

        // 4. Taxes
        $taxes = Tax::factory()->count(5)->create();

        // Fetch Accounts for Accounting Logic
        $cashAccount = Account::where('code', '1001')->first();
        $salesAccount = Account::where('code', '4001')->first(); // Sales Revenue
        $taxPayableAccount = Account::where('code', '2002')->first(); // Tax Payable
        $inventoryAccount = Account::where('code', '1004')->first(); // Inventory
        $cogsAccount = Account::where('code', '5001')->first(); // Cost of Goods Sold
        $expenseAccount = Account::where('code', '5002')->first(); // Rent Expense (generic expense)

        // 5. Sales with Accounting
        Sale::factory()->count(100)->make()->each(function ($sale) use ($users, $customers, $taxes, $cashAccount, $salesAccount, $taxPayableAccount) {
            // Assign existing user and customer to avoid creating new ones (and potential unique errors)
            $sale->user_id = $users->random()->id;
            $sale->customer_id = $customers->random()->id;
            $sale->save();

            $items = SaleItem::factory()->count(rand(1, 5))->make([
                'sale_id' => $sale->id
            ]);
            $sale->items()->saveMany($items);
            
            $subtotal = $items->sum('total_price');
            $taxRate = 0.1;
            $taxId = null;
            
            if ($taxes->count() > 0) {
                $selectedTax = $taxes->random();
                $taxId = $selectedTax->id;
                $taxRate = $selectedTax->rate / 100;
            }

            $tax = $subtotal * $taxRate;
            
            $sale->update([
                'subtotal' => $subtotal,
                'tax_id' => $taxId,
                'tax' => $tax,
                'total_amount' => $subtotal + $tax,
                'cash_received' => $subtotal + $tax,
            ]);

            // Accounting: Revenue Recognition
            if ($cashAccount && $salesAccount) {
                $je = JournalEntry::create([
                    'date' => $sale->created_at,
                    'reference' => $sale->invoice_number,
                    'description' => 'Sale Invoice ' . $sale->invoice_number,
                    'type' => 'sale',
                    'user_id' => $sale->user_id,
                ]);

                // Debit Cash
                JournalEntryItem::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $cashAccount->id,
                    'debit' => $sale->total_amount,
                    'credit' => 0,
                ]);

                // Credit Sales Revenue
                JournalEntryItem::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => $sale->subtotal,
                ]);

                // Credit Tax Payable
                if ($sale->tax > 0 && $taxPayableAccount) {
                    JournalEntryItem::create([
                        'journal_entry_id' => $je->id,
                        'account_id' => $taxPayableAccount->id,
                        'debit' => 0,
                        'credit' => $sale->tax,
                    ]);
                }
            }
        });

        // 6. Purchases with Accounting
        Purchase::factory()->count(100)->make()->each(function ($purchase) use ($users, $suppliers, $cashAccount, $inventoryAccount) {
            $purchase->user_id = $users->random()->id;
            $purchase->supplier_id = $suppliers->random()->id;
            $purchase->save();

            // Accounting: Purchase Inventory
            if ($cashAccount && $inventoryAccount) {
                $je = JournalEntry::create([
                    'date' => $purchase->created_at,
                    'reference' => $purchase->invoice_number,
                    'description' => 'Purchase Invoice ' . $purchase->invoice_number,
                    'type' => 'purchase',
                    'user_id' => $purchase->user_id,
                ]);

                // Debit Inventory
                JournalEntryItem::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $inventoryAccount->id,
                    'debit' => $purchase->total_amount,
                    'credit' => 0,
                ]);

                // Credit Cash
                JournalEntryItem::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $cashAccount->id,
                    'debit' => 0,
                    'credit' => $purchase->total_amount,
                ]);
            }
        });

        // 7. Transactions with Accounting
        Transaction::factory()->count(100)->create()->each(function ($transaction) use ($users, $cashAccount, $salesAccount, $expenseAccount) {
            // Accounting: Cash In/Out
            if ($cashAccount) {
                $je = JournalEntry::create([
                    'date' => $transaction->date,
                    'reference' => $transaction->reference_number,
                    'description' => 'Transaction ' . $transaction->description,
                    'type' => 'general',
                    'user_id' => $users->random()->id, 
                ]);

                if ($transaction->type === 'income' && $salesAccount) {
                    // Debit Cash, Credit Revenue
                     JournalEntryItem::create([
                        'journal_entry_id' => $je->id,
                        'account_id' => $cashAccount->id,
                        'debit' => $transaction->amount,
                        'credit' => 0,
                    ]);
                    JournalEntryItem::create([
                        'journal_entry_id' => $je->id,
                        'account_id' => $salesAccount->id,
                        'debit' => 0,
                        'credit' => $transaction->amount,
                    ]);
                } elseif ($transaction->type === 'expense' && $expenseAccount) {
                    // Debit Expense, Credit Cash
                    JournalEntryItem::create([
                        'journal_entry_id' => $je->id,
                        'account_id' => $expenseAccount->id,
                        'debit' => $transaction->amount,
                        'credit' => 0,
                    ]);
                    JournalEntryItem::create([
                        'journal_entry_id' => $je->id,
                        'account_id' => $cashAccount->id,
                        'debit' => 0,
                        'credit' => $transaction->amount,
                    ]);
                }
            }
        });

        // 8. Random Manual Journal Entries
        $accounts = Account::all();
        if ($accounts->count() >= 2) {
            JournalEntry::factory()->count(50)->make()->each(function ($entry) use ($users, $accounts) {
                $entry->user_id = $users->random()->id;
                $entry->save();

                $amount = rand(100, 5000);
                $debitAccount = $accounts->random();
                $creditAccount = $accounts->where('id', '!=', $debitAccount->id)->random();

                JournalEntryItem::factory()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $debitAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                JournalEntryItem::factory()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                ]);
            });
        }

        // 9. Balance History
        if ($users->count() > 0) {
            BalanceHistory::factory()->count(100)->make()->each(function ($history) use ($users) {
                $user = $users->random();
                $history->user_id = $user->id;
                $history->save();

                if ($history->type === 'credit') {
                    $user->increment('balance', $history->amount);
                } else {
                    $user->decrement('balance', $history->amount);
                }
            });
        }
    }
}
