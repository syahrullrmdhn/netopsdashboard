<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // 1) Buat roles
        collect(['admin','manager','engineer','viewer'])
            ->each(fn($r)=>Role::firstOrCreate(['name'=>$r]));

        // 2) Buat permissions
        collect([
            'manage customers',
            'manage tickets',
            'view reports',
            'view sla',
            'view performance',
        ])->each(fn($p)=>Permission::firstOrCreate(['name'=>$p]));

        // 3) Assign contoh: admin semua permission
        Role::findByName('admin')
            ->givePermissionTo(Permission::all());
    }
}
