<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationLevel extends Model
{
    protected $primaryKey = 'level';
    public $incrementing = false;
    protected $fillable = ['level','label','name','phone','email'];
}
