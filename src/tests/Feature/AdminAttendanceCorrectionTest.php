<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 1]);
    }

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものと一致する()
    {
        $attendance = Attendance::factory()->create(['start_time' => '09:00:00']);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    /** @test */
    public function 管理者画面で出勤時間が退勤時間より後の場合にエラーになる()
    {
        $attendance = Attendance::factory()->create();
        $this->actingAs($this->admin);

        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->patch(route('admin.attendance.update', $attendance->id), [
                'remarks' => 'テスト修正',
                'attendance' => [
                    0 => ['start_time' => '18:00', 'end_time' => '09:00']
                ]
            ]);
        $response->assertSessionHasErrors(['attendance.0.end_time']);
    }

    /** @test */
    public function 管理者画面で休憩開始時間が退勤時間より後の場合にエラーになる()
    {
        $attendance = Attendance::factory()->create();
        $this->actingAs($this->admin);
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->patch(route('admin.attendance.update', $attendance->id), [
                'remarks' => 'テスト',
                'attendance' => [
                    0 => ['start_time' => '09:00', 'end_time' => '18:00'],
                    1 => ['start_time' => '19:00', 'end_time' => '20:00']
                ]
            ]);
        $response->assertSessionHasErrors(['attendance.1.start_time']);
    }

    /** @test */
    public function 管理者画面で休憩終了時間が退勤時間より後の場合にエラーになる()
    {
        $attendance = Attendance::factory()->create();
        $this->actingAs($this->admin);
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->patch(route('admin.attendance.update', $attendance->id), [
                'date' => $attendance->date,
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'remarks'    => 'テスト',
                'attendance' => [1 => ['start_time' => '10:00', 'end_time' => '20:00']]
            ]);
        $response->assertSessionHasErrors(['attendance.1.end_time']);
    }

    /** @test */
    public function 管理者画面で備考欄が未入力の場合にエラーになる()
    {
        $attendance = Attendance::factory()->create();
        $this->actingAs($this->admin);
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->patch(route('admin.attendance.update', $attendance->id), [
                'date' => $attendance->date,
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'remarks'    => null
            ]);
        $response->assertSessionHasErrors(['remarks']);
    }

    /** @test */
    public function 承認待ちの修正申請が一覧に表示される()
    {
        $correction = \App\Models\StampCorrectionRequest::factory()->create(['status' => 0]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);
        $response->assertSee($correction->user->name);
    }

    /** @test */
    public function 修正申請の承認ボタンを押すとステータスが更新される()
    {
        $correction = \App\Models\StampCorrectionRequest::factory()->create(['status' => 0]);
        $this->actingAs($this->admin);
        $response = $this->post(route('admin.attendance.approve', ['id' => $correction->id]));
        $response->assertStatus(302);
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $correction->id,
            'status' => 1, 
        ]);
    }

    /** @test */
    public function 承認済みの修正申請が一覧に表示される()
    {
        $correction = \App\Models\StampCorrectionRequest::factory()->create(['status' => 1]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.request.list') . '?tab=approved');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($correction->user->name);
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示される()
    {
        $correction = \App\Models\StampCorrectionRequest::factory()->create();
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.approve.show', ['id' => $correction->id]));
        $response->assertStatus(200);
        $response->assertSee($correction->remarks);
    }
}