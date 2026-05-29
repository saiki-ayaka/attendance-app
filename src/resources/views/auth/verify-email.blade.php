@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify">
    <div class="verify__inner">
        <p class="verify__text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        <a href="http://localhost:8025" target="_blank" class="verify__button">認証はこちらから</a>
        <form class="verify__resend-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verify__resend-button">
                認証メールを再送信する
            </button>
        </form>
    </div>
</div>
@endsection