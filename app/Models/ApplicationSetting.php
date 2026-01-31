<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApplicationSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];
}
