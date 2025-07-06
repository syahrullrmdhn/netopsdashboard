<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'customerdb';
    public $timestamps = false;
    protected $table = 'suppliers';
    protected $primaryKey = 'kdsupplier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kdsupplier', 'nama_supplier'];
    
}
