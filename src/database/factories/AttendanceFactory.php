<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->format('Y-m-d'),
            'start_time' => null,
            'end_time' => null,
            'status' => 0,
        ];
    }

    public function working()
    {
        return $this->state([
            'start_time' => now()->subHours(2),
            'work_status' => 1,
        ]);
    }

    public function resting()
    {
        return $this->state([
            'start_time' => now()->subHours(4),
            'work_status' => 2,
        ]);
    }

    public function attendanceEnd()
    {
        return $this->state([
            'start_time' => now()->subHours(8),
            'end_time' => now(),
            'work_status' => 3,
        ]);
    }
}
