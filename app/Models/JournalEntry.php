<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JournalEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'date',
        'reference',
        'description',
        'type',
        'status',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
