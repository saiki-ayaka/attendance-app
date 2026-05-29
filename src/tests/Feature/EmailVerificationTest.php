<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録時に確認メールが送信される()
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証完了後に勤怠登録画面へ遷移する()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        $response = $this->get($url);
        $response->assertRedirect('/attendance');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function メール認証誘導画面からメール認証サイトに遷移する()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);
        $response = $this->get(route('verification.notice'));
        $response->assertStatus(200);
        $response = $this->post(route('verification.send'));
        $response->assertStatus(302);
    }
}
