<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $connection = 'customerdb';
    public $timestamps   = false;
    protected $table     = 'service_types';
    protected $fillable  = ['service_name', 'description', 'no'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'service_type_id');
    }
}
