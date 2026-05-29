<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__logo">
                @if(auth()->check())
                    {{-- ログイン中 --}}
                    <a href="{{ auth()->user()->role == 2 ? route('admin.attendance.list') : route('attendance.index') }}" class="header__logo-link">COACHTECH</a>
                @else
                    {{-- 未ログイン時 --}}
                    <a href="{{ request()->is('admin*') ? route('admin.login') : route('login') }}" class="header__logo-link">COACHTECH</a>
                @endif
            </h1>
        
            {{-- ログイン中 かつ メール認証画面以外の場合のみメニューを表示 --}}
            @if(auth()->check() && !Request::is('email/verify'))
                <nav class="header__nav">
                    <ul class="header__nav-list">
                        @if(auth()->user()->role == 2)
                            {{-- 管理者専用メニュー --}}
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('admin.request.list') }}">申請一覧</a></li>
                            <li class="header__nav-item">
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button class="header__nav-button" type="submit">ログアウト</button>
                                </form>
                            </li>
                        @else
                            {{-- 一般ユーザーメニュー --}}
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('attendance.index') }}">勤怠</a></li>
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('attendance.list') }}">勤怠一覧</a></li>
                            <li class="header__nav-item"><a class="header__nav-link" href="{{ route('stamp_correction.index') }}">申請</a></li>
                            <li class="header__nav-item">
                                <form action="{{ route('logout') }}" method="post"> @csrf
                                    <button class="header__nav-button" type="submit">ログアウト</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </nav>
            @endif
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>