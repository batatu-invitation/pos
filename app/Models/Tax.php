<?php

namespace App\Models;

use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasUuids, SoftDeletes, LogsActivityGeneric;

    protected $fillable = [
        'name',
        'rate',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
