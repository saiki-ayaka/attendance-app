@extends('layouts.app')

@section('css')
{{-- 🛠️ 作成した外部CSSファイルを読み込みます --}}
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify">
    <div class="verify__inner">
        <p class="verify__text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        {{-- 認証はこちらからボタン --}}
        <a href="http://localhost:8025" target="_blank" class="verify__button">認証はこちらから</a>

        {{-- 認証メールを再送信するボタン --}}
        <form class="verify__resend-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verify__resend-button">
                認証メールを再送信する
            </button>
        </form>

        {{-- 再送信成功時のステータスメッセージ --}}
        {{-- @if (session('status') == 'verification-link-sent') --}}
            {{-- <p class="verify__status-message">新しい認証メールを送信しました。</p> --}}
        {{-- @endif --}}
    </div>
</div>
@endsection