<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'price',
        'stock',
        'status',
        'icon',
        'tenant_id',
        'user_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
