<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => '管理者太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'), // パスワードは任意のものにしてください
            'role' => 2, // ここが重要！
            'email_verified_at' => now(), // 認証済みにしておくと便利
        ]);
    }
}
