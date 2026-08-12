<?php

namespace Database\Seeders;

use App\Models\Administrator;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = User::create([
            'user_name' => 'mgr_admin_1953',
            'full_name' => 'Admin System',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);

        // إنشاء الادمن المرتبط بنفس المستخدم
        Administrator::create([
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }
}
