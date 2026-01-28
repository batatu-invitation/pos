<?php

namespace App\Models;

use App\Models\Tenant;
use App\Traits\LogsActivityGeneric;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    use SoftDeletes, LogsActivityGeneric;
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
