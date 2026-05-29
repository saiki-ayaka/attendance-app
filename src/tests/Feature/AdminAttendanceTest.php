<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 1]);
    }

    /** @test */
    public function 管理者が勤怠一覧画面で全ユーザーの情報を確認できる()
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);
        Attendance::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d')]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
    }

    /** @test */
    public function 勤怠一覧画面に現在の日付が表示されている()
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.list'));
        $response->assertSee(now()->format('Y/m/d'));
    }

    /** @test */
    public function 前日の日付の勤怠情報に遷移できる()
    {
        $yesterday = now()->subDay()->format('Y-m-d');
        Attendance::factory()->create(['date' => $yesterday]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.list', ['date' => $yesterday]));
        $response->assertStatus(200);
        $response->assertSee($yesterday);
    }

    /** @test */
    public function 翌日の日付の勤怠情報に遷移できる()
    {
        $tomorrow = now()->addDay()->format('Y-m-d');
        Attendance::factory()->create(['date' => $tomorrow]);
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.attendance.list', ['date' => $tomorrow]));
        $response->assertStatus(200);
        $response->assertSee($tomorrow);
    }
}