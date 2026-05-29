<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 1)->get();
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::yesterday();
        $reasons = [
            '交通機関の遅延により遅刻しました',
            '忘れ物を取りに一時帰宅しました',
            '体調不良のため早退しました',
            '直行のため出勤時間の修正をお願いします',
            '残業に伴う退勤時間の修正です'
        ];
        foreach ($users as $user) {
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                if ($current->isWeekday()) {
                    $dateStr = $current->format('Y-m-d');
                    $remarksValue = (rand(1, 2) === 1) ? '残業のため対応' : null;
                    $attendance = Attendance::create([
                        'user_id'    => $user->id,
                        'date'       => $dateStr,
                        'start_time' => $dateStr . ' 09:00:00',
                        'end_time'   => $dateStr . ' 18:00:00',
                        'remarks'    => $remarksValue,
                    ]);
                    RestTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time'    => $dateStr . ' 12:00:00',
                        'end_time'      => $dateStr . ' 13:00:00',
                    ]);
                    if (rand(1, 10) <= 3) {
                        StampCorrectionRequest::create([
                            'user_id'       => $user->id,
                            'attendance_id' => $attendance->id,
                            'date'          => $attendance->date,
                            'start_time'    => $attendance->start_time,
                            'end_time'      => $attendance->end_time,
                            'status'        => rand(0, 1),
                            'remarks'       => $reasons[array_rand($reasons)],
                        ]);
                    }
                }
                $current->addDay();
            }
        }
    }
}
