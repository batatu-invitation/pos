<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    use HasFactory, \App\Traits\UserScoped;

    protected $fillable = [
        'user_id',
        'input_id',
        'amount',
        'type',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
