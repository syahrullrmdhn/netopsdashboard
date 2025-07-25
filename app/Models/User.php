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
        'name',
        'email',
        'password',
        'is_admin',
        'google_token',          // JSON array of the full access token
        'google_refresh_token',  // string refresh_token
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'is_admin'            => 'boolean',
        'google_token'        => 'array',   // otomatis cast JSON ↔ array
        'google_refresh_token'=> 'string',
    ];
}
