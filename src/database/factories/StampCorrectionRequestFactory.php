<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'attendance_id' => \App\Models\Attendance::factory(),
            'status' => 0,
            'date' => now()->format('Y-m-d'),
            'start_time' => now()->format('Y-m-d 09:00:00'),
            'end_time'   => now()->format('Y-m-d 18:00:00'),
            'remarks'    => 'テスト申請です',
        ];
    }
}
