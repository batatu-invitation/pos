<?php

namespace App\Models;

use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes, LogsActivityGeneric;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'price',
        'cost',
        'margin',
        'stock',
        'status',
        'image',
        'icon',
        'tenant_id',
        'user_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
