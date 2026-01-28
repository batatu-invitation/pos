<?php

namespace App\Models;

use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Category extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivityGeneric;

    protected $fillable = [
        'name',
        'icon',
        'color',
        'description',
        'tenant_id',
        'user_id',
    ];
}
