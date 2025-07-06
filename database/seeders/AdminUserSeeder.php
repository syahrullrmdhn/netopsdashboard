<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // jika belum ada, buat
        if (! User::where('email','syahrul@abhinawa.co.id')->exists()) {
            $user = User::create([
                'name'     => 'Administrator',
                'email'    => 'syahrul@abhinawa.co.id',
                'password' => Hash::make('syahrul123'),
            ]);

            // assign role (pastikan RoleSeeder sudah jalan)
            $user->assignRole('admin');
        }
    }
}
