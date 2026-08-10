<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'user_name' => 'admin',
            'full_name' => 'Admin System',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);

    }
}