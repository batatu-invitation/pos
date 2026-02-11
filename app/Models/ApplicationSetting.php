<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ApplicationSetting extends Model
{
    use HasUuids, BelongsToTenant, \App\Traits\UserScoped;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'input_id',
        'key',
        'value',
    ];
}
