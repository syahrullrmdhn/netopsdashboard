<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
Permission::insert([
    ['name'=>'dashboard'],
    ['name'=>'customers'],
    ['name'=>'tickets'],
    ['name'=>'reports'],
    ['name'=>'sla'],
    ['name'=>'performance'],
    ['name'=>'users'],
]);