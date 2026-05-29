<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 1]);
    }

    /** @test */
    public function 管理者が全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $staff = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'role' => 1
        ]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('test@example.com');
    }

    /** @test */
    public function 選択したユーザーの勤怠情報が正しく表示される()
    {
        $staff = User::factory()->create(['role' => 0]);
        Attendance::factory()->create(['user_id' => $staff->id, 'start_time' => '09:00:00']);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.staff.attendance', $staff->id));
        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    /** @test */
    public function 前月の勤怠情報に遷移できる()
    {
        $staff = User::factory()->create(['role' => 0]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.staff.attendance', $staff->id) . '?month=2026-05');
        $response->assertStatus(200);
        $response->assertSee('month=2026-04');
    }

    /** @test */
    public function 翌月の勤怠情報に遷移できる()
    {
        $staff = User::factory()->create(['role' => 0]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.staff.attendance', $staff->id) . '?month=2026-05');
        $response->assertStatus(200);
        $response->assertSee('month=2026-06');
    }

    /** @test */
    public function 詳細ボタンを押すと勤怠詳細画面に遷移する()
    {
        $staff = User::factory()->create(['role' => 0]);
        $attendance = Attendance::factory()->create(['user_id' => $staff->id]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.staff.attendance', $staff->id));
        $response->assertSee(route('admin.attendance.show', $attendance->id));
    }
}