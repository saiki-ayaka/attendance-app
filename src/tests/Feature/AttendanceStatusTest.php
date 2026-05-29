<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 現在の日時が表示されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee(now()->format('Y年m月d日'));
    }

    /** @test */
    public function 出勤中の場合ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->working()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d')
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->resting()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 勤務外の場合ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('勤務外');
    }

    /** @test */
    public function 退勤済の場合ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->attendanceEnd()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d')
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('退勤済');
    }
}
