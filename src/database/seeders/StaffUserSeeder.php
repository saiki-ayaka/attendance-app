<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 代表的な一般スタッフを手動で作成
        User::create([
            'name' => 'テスト 勤怠',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // 2. 一般スタッフをFactoryで10名作成
        // UserFactoryの設定がrole=1になっている前提です
        User::factory()->count(10)->create();
    }
}
