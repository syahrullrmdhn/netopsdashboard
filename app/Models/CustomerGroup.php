<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    protected $fillable = ['group_name'];
    protected $connection = 'customerdb';
    public $timestamps = false; 
    protected $table = 'customer_groups';

    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_group_id');
    }
     public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
