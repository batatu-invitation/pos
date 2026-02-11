<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Emoji extends Model
{
    use HasFactory, HasUuids, \App\Traits\UserScoped;

    protected $table = 'emojis';

    protected $fillable = [
        'icon',
        'name',
        'tenant_id',
        'user_id',
        'input_id',
    ];
}
