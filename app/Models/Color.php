<?php

namespace App\Models;

use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Color extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivityGeneric, \App\Traits\UserScoped;

    protected $fillable = [
        'name',
        'class',
        'tenant_id',
        'user_id',
        'input_id',
    ];
}
