<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceActionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤ボタンを押すと勤務開始し出勤中になる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post(route('attendance.workStart'));
        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_status' => 1,
            'date' => now()->format('Y-m-d')
        ]);
    }

    /** @test */
    public function すでに本日出勤している場合は出勤ボタンが表示されない()
    {
        $user = User::factory()->create();
        Attendance::factory()->working()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertDontSee('/attendance/work-start');
    }

    /** @test */
    public function 出勤時刻が勤怠一覧で確認できる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->working()->create([
            'user_id' => $user->id,
            'start_time' => now()
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee(now()->format('H:i'));
    }

    /** @test */
    public function 休憩開始ボタンを押すと休憩中になり休憩開始時刻が記録される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->working()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $this->post(route('attendance.restStart'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 2
        ]);
        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /** @test */
    public function 休憩戻ボタンを押すと出勤中に戻り休憩終了時刻が記録される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->resting()->create(['user_id' => $user->id]);
        $rest = \App\Models\RestTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now()->subMinutes(30)
        ]);
        $this->actingAs($user);
        $this->post(route('attendance.restEnd'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 1
        ]);
        $this->assertNotNull($rest->fresh()->end_time);
    }

    /** @test */
    public function 休憩時間は勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        \App\Models\RestTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now()->subMinutes(60),
            'end_time' => now()->subMinutes(30)
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('00:30');
    }

    /** @test */
    public function 退勤ボタンを押すと退勤処理が行われ退勤済になる()
    {
        $user = User::factory()->create();
        Attendance::factory()->working()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $this->post(route('attendance.workEnd'));
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_status' => 3
        ]);
        $this->assertNotNull(Attendance::where('user_id', $user->id)->first()->end_time);
    }

    /** @test */
    public function 退勤時刻が勤怠一覧で確認できる()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'end_time' => now()
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));
        $response->assertSee(now()->format('H:i'));
    }

    /** @test */
    public function 自分の勤怠情報が一覧に表示されている()
    {
        $user = User::factory()->create();
        Attendance::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d')]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee(now()->format('m/d'));
    }

    /** @test */
    public function 勤怠一覧画面に現在の月が表示されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));
        $response->assertSee(now()->format('Y/m'));
    }

    /** @test */
    public function 前月ボタン押下時に前月の情報が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $prevMonth = now()->subMonth()->format('Y-m');
        $response = $this->get(route('attendance.list', ['month' => $prevMonth]));
        $response->assertSee(now()->subMonth()->format('Y/m'));
    }

    /** @test */
    public function 翌月ボタン押下時に翌月の情報が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $nextMonth = now()->addMonth()->format('Y-m');
        $response = $this->get(route('attendance.list', ['month' => $nextMonth]));
        $response->assertSee(now()->addMonth()->format('Y/m'));
    }

    /** @test */
    public function 詳細ボタンを押すと勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.show', ['id' => $attendance->id]));
        $response->assertStatus(200);
    }
}