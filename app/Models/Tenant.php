<?php

namespace App\Models;

use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes, LogsActivityGeneric;



    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'type',
            'initial',
            'initial_color',
            'location',
            'manager',
            'phone',
            'email',
            'status',
            'status_color',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }
}
