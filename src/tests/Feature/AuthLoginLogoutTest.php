<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールアドレス未入力でログイン失敗する()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワード未入力でログイン失敗する()
    {
        $user = User::factory()->create();
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function 正しい情報でログインでき勤怠一覧へ遷移する()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('attendance.list'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function ログインユーザーがログアウトできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function 管理者がログアウトして管理者ログイン画面へ戻る()
    {
        $admin = User::factory()->create(['role' => 2]);
        $this->actingAs($admin);
        $response = $this->withHeaders(['Referer' => route('admin.attendance.list')])
                            ->post('/logout');
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function ログアウト後にログインが必要なページへアクセスすると拒否される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/logout');
        $response = $this->get(route('attendance.list'));
        $response->assertRedirect('/login');
    }
}
