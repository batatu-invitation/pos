<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait UserScoped
{
    /**
     * Boot the UserScoped trait.
     */
    protected static function bootUserScoped(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                // user_id is the Owner (Parent)
                $model->user_id = $user->created_by ? $user->created_by : $user->id;
                // input_id is the Actor (Current User)
                $model->input_id = $user->id;
            }
        });

        static::addGlobalScope('user_scope', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();

                // Super Admin sees everything
                if ($user->hasRole('Super Admin')) {
                    return;
                }

                $ownerId = $user->created_by ? $user->created_by : $user->id;
                
                // Get the table name from the model to avoid ambiguity in joins
                $table = $builder->getModel()->getTable();
                
                $builder->where($table . '.user_id', $ownerId);
            }
        });
    }
}
