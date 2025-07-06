<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverLog extends Model
{
    protected $fillable = [
        'date','shift','from_user_id','to_user_id','issues','notes'
    ];

    // relasi ke User
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
