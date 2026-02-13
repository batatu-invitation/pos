<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, HasUuids, SoftDeletes, \App\Traits\UserScoped;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'customer_id',
        'subtotal',
        'tax_id',
        'tax',
        'discount',
        'total_amount',
        'cash_received',
        'change_amount',
        'payment_method',
        'status',
        'payment_status',
        'due_date',
        'notes',
        'tenant_id',
        'input_id'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get journal entries for this sale
     */
    public function journalEntries()
    {
        return JournalEntry::where('reference', $this->invoice_number)
            ->where('type', 'sale');
    }

    /**
     * Get the transaction associated with this sale (auto-generated)
     */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source');
    }

    /**
     * Boot the model and add event listeners for automatic transaction sync
     */
    protected static function booted()
    {
        static::created(function ($sale) {
            // Auto-create transaction for completed sales
            if ($sale->status === 'completed') {
                self::createTransactionFromSale($sale);
                
                // Auto-create journal entry for general ledger
                app(\App\Services\JournalEntryService::class)->createFromSale($sale);
            }
        });

        static::updated(function ($sale) {
            // Update corresponding transaction if sale changes
            if ($sale->isDirty('total_amount') || $sale->isDirty('status') || $sale->isDirty('payment_status')) {
                self::updateTransactionFromSale($sale);
            }
        });

        static::deleting(function ($sale) {
            // Delete associated transaction when sale is deleted
            $sale->transaction()->delete();
            
            // Delete associated journal entry
            app(\App\Services\JournalEntryService::class)->deleteFromSale($sale);
        });
    }

    /**
     * Create a transaction from a sale
     */
    protected static function createTransactionFromSale(Sale $sale): Transaction
    {
        return Transaction::create([
            'type' => 'income',
            'amount' => $sale->total_amount,
            'category' => 'Sales',
            'description' => "Sale #{$sale->invoice_number} - {$sale->customer?->name}",
            'date' => $sale->created_at->format('Y-m-d'),
            'reference_number' => $sale->invoice_number,
            'payment_method' => $sale->payment_method,
            'status' => self::mapSaleStatusToTransaction($sale->status, $sale->payment_status),
            'source_type' => 'sale',
            'source_id' => $sale->id,
            'user_id' => $sale->user_id,
            'customer_id' => $sale->customer_id,
            'tenant_id' => $sale->tenant_id,
        ]);
    }

    /**
     * Update transaction from sale changes
     */
    protected static function updateTransactionFromSale(Sale $sale): void
    {
        $transaction = $sale->transaction;
        
        if ($transaction) {
            $transaction->update([
                'amount' => $sale->total_amount,
                'status' => self::mapSaleStatusToTransaction($sale->status, $sale->payment_status),
                'payment_method' => $sale->payment_method,
                'date' => $sale->created_at->format('Y-m-d'),
            ]);
        } elseif ($sale->status === 'completed') {
            // Create transaction if sale was updated to completed
            self::createTransactionFromSale($sale);
        }
    }

    /**
     * Map sale status to transaction status
     */
    protected static function mapSaleStatusToTransaction(string $saleStatus, ?string $paymentStatus): string
    {
        if ($saleStatus === 'refunded') {
            return 'cancelled';
        }
        
        if ($paymentStatus === 'unpaid') {
            return 'pending';
        }
        
        return 'completed';
    }
}