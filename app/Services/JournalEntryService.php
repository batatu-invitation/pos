<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalEntryService
{
    /**
     * Create journal entry from a sale transaction
     */
    public function createFromSale(Sale $sale): ?JournalEntry
    {
        if (!config('accounting.auto_create_journal_entries', true)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($sale) {
                // Get the cash/bank account based on payment method
                $cashAccount = $this->getAccountForPaymentMethod($sale->payment_method);
                $salesRevenueAccount = $this->getAccountByCode(config('accounting.default_account_codes.sales_revenue'));
                $taxPayableAccount = $this->getAccountByCode(config('accounting.default_account_codes.tax_payable'));

                if (!$cashAccount || !$salesRevenueAccount) {
                    Log::warning("Missing accounts for journal entry creation from sale #{$sale->invoice_number}");
                    return null;
                }

                // Create journal entry header
                $journalEntry = JournalEntry::create([
                    'date' => $sale->created_at,
                    'reference' => $sale->invoice_number,
                    'description' => "Sale - {$sale->invoice_number}" . ($sale->customer ? " - {$sale->customer->name}" : ''),
                    'type' => 'sale',
                    'status' => 'posted',
                    'user_id' => $sale->user_id,
                ]);

                // Debit: Cash/Bank Account (total amount received)
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $cashAccount->id,
                    'debit' => $sale->total_amount,
                    'credit' => 0,
                ]);

                // Credit: Sales Revenue (subtotal - discount)
                $revenueAmount = $sale->subtotal - $sale->discount;
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $salesRevenueAccount->id,
                    'debit' => 0,
                    'credit' => $revenueAmount,
                ]);

                // Credit: Tax Payable (if tax exists)
                if ($sale->tax > 0 && $taxPayableAccount) {
                    JournalEntryItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $taxPayableAccount->id,
                        'debit' => 0,
                        'credit' => $sale->tax,
                    ]);
                }

                return $journalEntry;
            });
        } catch (\Exception $e) {
            Log::error("Failed to create journal entry from sale: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create journal entry from a transaction
     */
    public function createFromTransaction(Transaction $transaction): ?JournalEntry
    {
        if (!config('accounting.auto_create_journal_entries', true)) {
            return null;
        }

        // Don't create journal entries for auto-generated transactions from sales
        // (they already have journal entries created via createFromSale)
        if ($transaction->source_type === 'sale') {
            return null;
        }

        try {
            return DB::transaction(function () use ($transaction) {
                $cashAccount = $this->getAccountForPaymentMethod($transaction->payment_method ?? 'cash');
                
                if (!$cashAccount) {
                    Log::warning("Missing cash account for journal entry creation from transaction #{$transaction->id}");
                    return null;
                }

                // Create journal entry header
                $journalEntry = JournalEntry::create([
                    'date' => $transaction->date,
                    'reference' => $transaction->reference_number ?? 'TXN-' . $transaction->id,
                    'description' => $transaction->description,
                    'type' => $transaction->type,
                    'status' => 'posted',
                    'user_id' => $transaction->user_id,
                ]);

                if ($transaction->type === 'income') {
                    // Income Transaction
                    $incomeAccount = $this->getAccountByCategory($transaction->category, 'income');

                    // Debit: Cash/Bank
                    JournalEntryItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $cashAccount->id,
                        'debit' => $transaction->amount,
                        'credit' => 0,
                    ]);

                    // Credit: Income Account
                    if ($incomeAccount) {
                        JournalEntryItem::create([
                            'journal_entry_id' => $journalEntry->id,
                            'account_id' => $incomeAccount->id,
                            'debit' => 0,
                            'credit' => $transaction->amount,
                        ]);
                    }
                } else {
                    // Expense Transaction
                    $expenseAccount = $this->getAccountByCategory($transaction->category, 'expense');

                    // Debit: Expense Account
                    if ($expenseAccount) {
                        JournalEntryItem::create([
                            'journal_entry_id' => $journalEntry->id,
                            'account_id' => $expenseAccount->id,
                            'debit' => $transaction->amount,
                            'credit' => 0,
                        ]);
                    }

                    // Credit: Cash/Bank
                    JournalEntryItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $cashAccount->id,
                        'debit' => 0,
                        'credit' => $transaction->amount,
                    ]);
                }

                return $journalEntry;
            });
        } catch (\Exception $e) {
            Log::error("Failed to create journal entry from transaction: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get account for payment method
     */
    protected function getAccountForPaymentMethod(string $paymentMethod): ?Account
    {
        $accountCode = config("accounting.payment_method_accounts.{$paymentMethod}");
        
        if (!$accountCode) {
            // Default to cash account if payment method not mapped
            $accountCode = config('accounting.default_account_codes.cash');
        }

        return $this->getAccountByCode($accountCode);
    }

    /**
     * Get account by code
     */
    protected function getAccountByCode(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    /**
     * Get account by category
     */
    protected function getAccountByCategory(string $category, string $type): ?Account
    {
        // First try to find exact match in config
        $accountCode = config("accounting.category_accounts.{$category}");
        
        if ($accountCode) {
            return $this->getAccountByCode($accountCode);
        }

        // Fallback to default accounts
        if ($type === 'income') {
            $accountCode = config('accounting.default_account_codes.other_income');
        } else {
            // For expenses, try to find first expense account or use a default
            $accountCode = config('accounting.category_accounts.Other Expense', '5900');
        }

        return $this->getAccountByCode($accountCode);
    }

    /**
     * Delete journal entry associated with a sale
     */
    public function deleteFromSale(Sale $sale): void
    {
        JournalEntry::where('reference', $sale->invoice_number)
            ->where('type', 'sale')
            ->delete();
    }

    /**
     * Delete journal entry associated with a transaction
     */
    public function deleteFromTransaction(Transaction $transaction): void
    {
        $reference = $transaction->reference_number ?? 'TXN-' . $transaction->id;
        
        JournalEntry::where('reference', $reference)
            ->where('type', $transaction->type)
            ->delete();
    }
}
