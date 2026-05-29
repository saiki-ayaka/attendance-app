<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertSee('テスト太郎');
    }

    /** @test */
    public function 勤怠詳細画面の日付が選択した日付になっている()
    {
        $user = User::factory()->create();
        $date = '2026-05-30';
        $attendance = Attendance::factory()->create(['user_id' => $user->id, 'date' => $date]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertSee($date);
    }

    /** @test */
    public function 出勤・退勤時間が打刻と一致している()
    {
        $user = User::factory()->create();
        $start = '2026-05-30 09:00:00';
        $end = '2026-05-30 18:00:00';
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => $start,
            'end_time' => $end
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 休憩時間が打刻と一致している()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        RestTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-05-30 12:00:00',
            'end_time' => '2026-05-30 13:00:00'
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
