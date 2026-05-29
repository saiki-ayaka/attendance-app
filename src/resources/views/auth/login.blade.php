@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-container">
    <h2 class="login-title">ログイン</h2>
    <form class="login-form" action="{{ route('login') }}" method="POST" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <span class="error-msg">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required>
            @error('password') <span class="error-msg">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="login-button">ログインする</button>
    </form>
    <div class="register-link">
        <a href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection