<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthValidationTest extends TestCase
{
    use RefreshDatabase;

    private function getValidData()
    {
        return [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    /** @test */
    public function 名前が未入力だとエラーになる()
    {
        $data = $this->getValidData();
        $data['name'] = '';
        $response = $this->post('/register', $data);
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /** @test */
    public function メールアドレスが未入力だとエラーになる()
    {
        $data = $this->getValidData();
        $data['email'] = '';
        $response = $this->post('/register', $data);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function パスワードが8文字未満だとエラーになる()
    {
        $data = $this->getValidData();
        $data['password'] = '1234567'; // 7文字
        $data['password_confirmation'] = '1234567';
        $response = $this->post('/register', $data);
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /** @test */
    public function パスワードが一致しないとエラーになる()
    {
        $data = $this->getValidData();
        $data['password'] = 'password123';
        $data['password_confirmation'] = 'different';
        $response = $this->post('/register', $data);
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /** @test */
    public function パスワードが未入力だとエラーになる()
    {
        $data = $this->getValidData();
        $data['password'] = '';
        $data['password_confirmation'] = '';
        $response = $this->post('/register', $data);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function 正常に会員登録ができる()
    {
        $response = $this->post('/register', $this->getValidData());
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
