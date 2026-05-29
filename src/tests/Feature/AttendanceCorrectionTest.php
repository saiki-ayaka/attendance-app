<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤時間が退勤時間より後の場合にエラーになる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->from(route('attendance.show', $attendance->id)) 
                        ->put(route('attendance.update', $attendance->id), [
                            'date' => $attendance->date,
                            'start_time' => '10:00',
                            'end_time' => '09:00',
                            'remarks' => 'テスト修正'
                        ]);
        $response->assertSessionHasErrors(['end_time']);
    }

    /** @test */
    public function 備考欄が未入力の場合にエラーになる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->from(route('attendance.show', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'date'       => $attendance->date,
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'remarks'    => null,
                'attendance' => [
                    1 => ['start_time' => null, 'end_time' => null],
                    2 => ['start_time' => null, 'end_time' => null]
                ]
            ]);
        $response->assertSessionHasErrors(['remarks']);
    }

    /** @test */
    public function 修正申請処理が実行されDBに保存される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->put(route('attendance.update', $attendance->id), [
            'date' => $attendance->date,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remarks' => '修正します',
            'attendance' => [
                1 => ['start_time' => null, 'end_time' => null],
                2 => ['start_time' => null, 'end_time' => null]
            ]
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'status' => 0
        ]);
    }

    /** @test */
    public function 申請一覧画面に自分の申請が表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'status' => 0,
            'start_time' => now()->format('Y-m-d') . ' 09:00:00',
            'end_time' => now()->format('Y-m-d') . ' 18:00:00',
            'remarks' => 'テスト申請'
        ]);
        $this->actingAs($user);
        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee('テスト申請');
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後の場合にエラーになる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->from(route('attendance.show', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'date' => $attendance->date,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remarks' => 'テスト',
                'attendance' => [
                    1 => ['start_time' => '19:00', 'end_time' => '20:00']
                ]
            ]);
        $response->assertSessionHasErrors(['attendance.1.start_time']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後の場合にエラーになる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->from(route('attendance.show', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'date' => $attendance->date,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remarks' => 'テスト',
                'attendance' => [
                    1 => ['start_time' => '10:00', 'end_time' => '20:00']
                ]
            ]);
        $response->assertSessionHasErrors(['attendance.1.end_time']);
    }

    /** @test */
    public function 承認待ちの申請が一覧画面に表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'status' => 0,
            'remarks' => '承認待ちテスト',
            'start_time' => now()->format('Y-m-d') . ' 09:00:00',
            'end_time' => now()->format('Y-m-d') . ' 18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get(route('stamp_correction.index'));
        $response->assertSee('承認待ちテスト');
    }

    /** @test */
    public function 承認済みの申請が一覧画面に表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'status' => 1,
            'remarks' => '承認済みテスト',
            'start_time' => now()->format('Y-m-d') . ' 09:00:00',
            'end_time' => now()->format('Y-m-d') . ' 18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get(route('stamp_correction.index') . '?tab=approved');
        $response->assertSee('承認済みテスト');
    }
}
