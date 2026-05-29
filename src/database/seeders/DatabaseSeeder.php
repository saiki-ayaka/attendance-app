<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminUserSeeder::class, // 先に管理者
            StaffUserSeeder::class, // 次にスタッフ
            AttendanceSeeder::class, // 最後に勤怠データ（スタッフ作成後に実行）
        ]);
    }
}
