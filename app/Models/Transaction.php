<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'type',
        'amount',
        'category',
        'description',
        'date',
        'reference_number',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];
}
