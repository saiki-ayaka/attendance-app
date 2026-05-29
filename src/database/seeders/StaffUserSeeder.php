<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'テスト 勤怠',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 1,
            'email_verified_at' => now(),
        ]);
        User::factory()->count(10)->create();
    }
}
