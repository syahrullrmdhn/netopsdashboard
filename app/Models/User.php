<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name','email','password','is_admin',
    ];

    protected $hidden = [
        'password','remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean',
    ];
}
