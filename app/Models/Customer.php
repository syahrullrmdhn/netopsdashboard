<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // pakai koneksi customerdb
    protected $connection = 'customerdb';
    public $timestamps = false; // tabelmu nggak ada created_at/updated_at
    protected $table = 'customers';

    protected $fillable = [
        'customer',
        'cid_abh',
        'kdsupplier',
        'start_date',
        'end_date',
        'contract_period',
        'auto_renewal',
        'extra_desc',
        'status',
        'customer_group_id',
        'service_type_id',
        'vlan',
        'ip_address',
        'prefix',
        'xconnect_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kdsupplier', 'kdsupplier');
    }

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    // helper untuk contract_period apabila mau dihitung otomatis
    public function getContractPeriodAttribute($value)
    {
        if ($this->start_date && $this->end_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end   = \Carbon\Carbon::parse($this->end_date);
            return $start->diffInMonths($end).' bulan';
        }
        return $value;
    }
     public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'customer_id', 'id');
    }
}
