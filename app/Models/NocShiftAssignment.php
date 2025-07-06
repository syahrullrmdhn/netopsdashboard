<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NocShiftAssignment extends Model
{
    protected $fillable = ['date','shift','user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
